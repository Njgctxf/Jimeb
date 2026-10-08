<?php

namespace App\Http\Controllers\SuperAdmin;

use Carbon\Carbon;
use App\Models\User;
use Razorpay\Api\Api;
use App\Models\Package;
use Razorpay\Api\Errors;
use App\Enums\PackageType;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use App\Models\GlobalInvoice;
use App\Models\GlobalCurrency;
use App\Models\GlobalSubscription;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Notifications\SubscriptionExpire;
use App\Models\SuperadminPaymentGateway;
use App\Notifications\RestaurantUpdatedPlan;
use Illuminate\Support\Facades\Notification;

class RazorpayWebhookController extends Controller
{

    const SUBSCRIPTION_CHARGED = 'subscription.charged';
    const PAYMENT_FAILED = 'payment.failed';
    const SUBSCRIPTION_CANCELLED = 'subscription.cancelled';
    const SUBSCRIPTION_HALTED = 'subscription.halted';
    const PAYMENT_PAID = 'payment.paid'; // for recurring payments

    /**
     * Events whose Razorpay payload has a subscription entity but no payment entity.
     */
    private const SUBSCRIPTION_ONLY_EVENTS = [
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_HALTED,
    ];

    public function saveInvoices(Request $request)
    {
        if (!isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'])) {
            echo ('Signature mismatched Razorpay webhook saas');

            return false;
        }

        $credential = SuperadminPaymentGateway::first();

        $apiKey = $credential->razorpay_key;
        $secretKey = $credential->razorpay_secret;
        $secretWebhook = $credential->razorpay_webhook_key;

        $post = file_get_contents('php://input');
        $requestData = json_decode($post, true);
        $event = $requestData['event'] ?? null;
        $notes = $requestData['payload']['payment']['entity']['notes']
            ?? $requestData['payload']['subscription']['entity']['notes']
            ?? null;

        if (is_null($notes) && !in_array($event, self::SUBSCRIPTION_ONLY_EVENTS, true)) {
            echo ('Notes Payload not found');
            return false;
        }


        if (is_array($notes) && isset($notes['webhook_hash']) && $notes['webhook_hash'] !== global_setting()->hash) {
            echo ('Main app hash mismatched: This indicates that the webhook for another app has been called.');

            return false;
        }

        $razorpayWebhookSecret = $secretWebhook;

        try {
            $api = new Api($apiKey, $secretKey);
            // $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'], ['razorpayWebhookSecret' => $razorpayWebhookSecret]
            $api->utility->verifyWebhookSignature($post, $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'], $razorpayWebhookSecret);
        } catch (Errors\SignatureVerificationError | \Exception $e) {

            return;
        }

        return match ($event) {
            self::SUBSCRIPTION_CHARGED => $this->paymentAuthorized($requestData),
            self::PAYMENT_FAILED => $this->paymentFailed($requestData),
            self::SUBSCRIPTION_CANCELLED, self::SUBSCRIPTION_HALTED => $this->subscriptionCancelled($requestData),
            default => null,
        };
    }

    /**
     * Recurring charge failed. Keep paid access through the current period and let
     * LicenseExpire revoke it after retries are exhausted / the period ends.
     * Immediate downgrade happens on subscription.cancelled / subscription.halted.
     */
    protected function paymentFailed(array $requestData): bool
    {
        $razorpaySubscription = $this->findActiveRazorpaySubscription($requestData);

        if (is_null($razorpaySubscription)) {
            return true;
        }

        $restaurant = Restaurant::find($razorpaySubscription->restaurant_id);

        if (is_null($restaurant)) {
            return true;
        }

        $invoice = GlobalInvoice::where('gateway_name', 'razorpay')
            ->where(function ($query) use ($razorpaySubscription) {
                $query->where('global_subscription_id', $razorpaySubscription->id);

                if ($razorpaySubscription->subscription_id) {
                    $query->orWhere('subscription_id', $razorpaySubscription->subscription_id);
                }
            })
            ->latest()
            ->first();

        $periodEnd = $invoice?->next_pay_date
            ? Carbon::parse($invoice->next_pay_date)
            : ($razorpaySubscription->ends_at ?: now());

        if ($periodEnd->isPast()) {
            $periodEnd = now();
        }

        $restaurant->license_expire_on = $periodEnd->format('Y-m-d');
        $restaurant->save();

        Log::warning('Razorpay recurring payment failed; access kept until period end', [
            'restaurant_id' => $restaurant->id,
            'subscription_id' => $razorpaySubscription->subscription_id,
            'payment_id' => $requestData['payload']['payment']['entity']['id'] ?? null,
            'license_expire_on' => $restaurant->license_expire_on,
        ]);

        return true;
    }

    /**
     * Subscription cancelled or halted: mark it inactive and downgrade the restaurant
     * to the Default package, matching LicenseExpire / in-app cancel.
     *
     * @param array $requestData Webhook Data
     */
    protected function subscriptionCancelled(array $requestData)
    {
        $subscriptionEntity = $requestData['payload']['subscription']['entity'] ?? [];
        $subscriptionId = $subscriptionEntity['id'] ?? null;

        if (!$subscriptionId) {
            return true;
        }

        $razorpaySubscription = GlobalSubscription::where('gateway_name', 'razorpay')
            ->where('subscription_status', 'active')
            ->where('subscription_id', $subscriptionId)
            ->first();

        if (is_null($razorpaySubscription)) {
            return true;
        }

        $endedAtTimestamp = $subscriptionEntity['ended_at'] ?? $subscriptionEntity['current_end'] ?? time();
        $endedAt = Carbon::createFromTimestamp($endedAtTimestamp);

        $this->downgradeRestaurantToDefault($razorpaySubscription, $endedAt);

        return true;
    }

    /**
     * @param array $requestData
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    protected function paymentAuthorized(array $requestData)
    {
        //
        // Order entity should be sent as part of the webhook payload
        //

        $subscriptionID = $requestData['payload']['subscription']['entity']['id'];

        $subscription = GlobalSubscription::where('gateway_name', 'razorpay')->where('subscription_id', $subscriptionID)->first() ?? null;

        if (!$subscription) {
            return response('Webhook Handled', 200);
        }

        $packageId = $subscription->package_id;
        $restaurantID = $subscription->restaurant_id;

        $plan = Package::find($packageId);
        $restaurant = Restaurant::findOrFail($restaurantID);

        // If it is already marked as paid, ignore the event
        $razorpayPaymentId = $requestData['payload']['payment']['entity']['id'];
        $credential = SuperadminPaymentGateway::first();

        if ($credential->razorpay_type == 'test') {
            $apiKey = $credential->test_razorpay_key;
            $secretKey = $credential->test_razorpay_secret;
        } else {
            $apiKey = $credential->live_razorpay_key;
            $secretKey = $credential->live_razorpay_secret;
        }

        try {
            $api = new Api($apiKey, $secretKey);
            $payment = $api->payment->fetch($razorpayPaymentId);
        } catch (\Exception $e) {

            return;
        }
        //
        // If the payment is only authorized, we capture it
        // If the merchant has enabled auto capture
        //
        try {

            $invoiceID = $requestData['payload']['payment']['entity']['invoice_id'];
            $customerID = $requestData['payload']['subscription']['entity']['customer_id'];
            $endTimeStamp = $requestData['payload']['subscription']['entity']['current_end'];
            $currencyCode = $requestData['payload']['payment']['entity']['currency'];
            $transactionId = $requestData['payload']['payment']['entity']['id'];
            $planId = $requestData['payload']['subscription']['entity']['plan_id'];

            $packageType = $subscription->package_type;

            $endDate = \Carbon\Carbon::createFromTimestamp($endTimeStamp)->format('Y-m-d');

            $currency = GlobalCurrency::where('currency_code', $currencyCode)->first();

            if ($currency) {
                $currencyID = $currency->id;
            } else {
                $currencyID = GlobalCurrency::where('currency_code', 'USD')->first()->id;
            }

            $razorpayInvoice = GlobalInvoice::where('gateway_name', 'razorpay')->where('invoice_id', $invoiceID)->first();

            // Store invoice details
            if (!$razorpayInvoice) {
                $razorpayInvoice = new GlobalInvoice();
                $razorpayInvoice->restaurant_id = $restaurant->id;
                $razorpayInvoice->currency_id = $currencyID;
                $razorpayInvoice->subscription_id = $subscriptionID;
                $razorpayInvoice->invoice_id = $invoiceID;
                $razorpayInvoice->transaction_id = $transactionId;
                $razorpayInvoice->amount = $payment->amount / 100;
                $razorpayInvoice->total = $payment->amount / 100;
                $razorpayInvoice->plan_id = $planId;
                $razorpayInvoice->package_type = $packageType;
                $razorpayInvoice->package_id = $packageId;
                $razorpayInvoice->pay_date = now()->format('Y-m-d');
                $razorpayInvoice->next_pay_date = $endDate;
                $razorpayInvoice->currency_id = $plan->currency_id;
                $razorpayInvoice->gateway_name = 'razorpay';
                $razorpayInvoice->global_subscription_id = $subscription->id;
                $razorpayInvoice->status = 'active';
                $razorpayInvoice->save();
            }



            $subscription = GlobalSubscription::where('gateway_name', 'razorpay')->where('subscription_id', $subscriptionID)->first();
            $subscription->customer_id = $customerID;
            $subscription->save();

            // Change restaurant status active after payment
            $restaurant->status = 'active';
            $restaurant->save();

            $generatedBy = User::whereNull('company_id')->get();

            Notification::send($generatedBy, new RestaurantUpdatedPlan($restaurant, $plan->id));



            return response('Webhook Handled', 200);
        } catch (\Exception $e) {
            //
            // Capture will fail if the payment is already captured
            //
            $log = array(
                'message' => $e->getMessage(),
                'payment_id' => $razorpayPaymentId,
                'event' => $requestData['event']
            );
            error_log(json_encode($log));
        }

        // Graceful exit since payment is now processed.
        exit;
    }

    /**
     * Locate the active Razorpay subscription for payment.failed (no subscription entity).
     */
    private function findActiveRazorpaySubscription(array $requestData): ?GlobalSubscription
    {
        $subscriptionId = $requestData['payload']['subscription']['entity']['id']
            ?? $requestData['payload']['payment']['entity']['subscription_id']
            ?? null;

        if ($subscriptionId) {
            $subscription = GlobalSubscription::where('gateway_name', 'razorpay')
                ->where('subscription_status', 'active')
                ->where('subscription_id', $subscriptionId)
                ->first();

            if ($subscription) {
                return $subscription;
            }
        }

        $invoiceId = $requestData['payload']['payment']['entity']['invoice_id'] ?? null;

        if ($invoiceId) {
            $invoice = GlobalInvoice::where('gateway_name', 'razorpay')
                ->where('invoice_id', $invoiceId)
                ->first();

            if ($invoice) {
                $subscription = $this->activeRazorpaySubscriptionFromInvoice($invoice);

                if ($subscription) {
                    return $subscription;
                }
            }
        }

        $paymentId = $requestData['payload']['payment']['entity']['id'] ?? null;

        if ($paymentId) {
            $subscription = GlobalSubscription::where('gateway_name', 'razorpay')
                ->where('subscription_status', 'active')
                ->where('transaction_id', $paymentId)
                ->first();

            if ($subscription) {
                return $subscription;
            }

            $invoice = GlobalInvoice::where('gateway_name', 'razorpay')
                ->where('transaction_id', $paymentId)
                ->first();

            if ($invoice) {
                $subscription = $this->activeRazorpaySubscriptionFromInvoice($invoice);

                if ($subscription) {
                    return $subscription;
                }
            }
        }

        $restaurantId = $requestData['payload']['payment']['entity']['notes']['restaurant_id'] ?? null;

        if ($restaurantId) {
            return GlobalSubscription::where('gateway_name', 'razorpay')
                ->where('restaurant_id', $restaurantId)
                ->where('subscription_status', 'active')
                ->latest()
                ->first();
        }

        return null;
    }

    private function activeRazorpaySubscriptionFromInvoice(GlobalInvoice $invoice): ?GlobalSubscription
    {
        if ($invoice->global_subscription_id) {
            $subscription = GlobalSubscription::where('id', $invoice->global_subscription_id)
                ->where('gateway_name', 'razorpay')
                ->where('subscription_status', 'active')
                ->first();

            if ($subscription) {
                return $subscription;
            }
        }

        if ($invoice->subscription_id) {
            return GlobalSubscription::where('gateway_name', 'razorpay')
                ->where('subscription_id', $invoice->subscription_id)
                ->where('subscription_status', 'active')
                ->first();
        }

        return null;
    }

    /**
     * Switch the restaurant off the paid Razorpay plan onto the Default package.
     */
    private function downgradeRestaurantToDefault(GlobalSubscription $razorpaySubscription, Carbon $endedAt): void
    {
        $restaurant = Restaurant::find($razorpaySubscription->restaurant_id);

        if (is_null($restaurant)) {
            $razorpaySubscription->subscription_status = 'inactive';
            $razorpaySubscription->ends_at = $endedAt->format('Y-m-d');
            $razorpaySubscription->save();

            return;
        }

        $this->deactivateRazorpayBilling($razorpaySubscription, $endedAt);

        $defaultPackage = Package::where('package_type', PackageType::DEFAULT)->first();

        if (is_null($defaultPackage)) {
            Log::error('Razorpay webhook: default package not found; subscription marked inactive without package switch', [
                'restaurant_id' => $restaurant->id,
                'subscription_id' => $razorpaySubscription->subscription_id,
            ]);

            return;
        }

        if ((int) $restaurant->package_id === (int) $defaultPackage->id) {
            return;
        }

        $subscriptionExpiredAt = $restaurant->license_expire_on?->copy() ?? $endedAt->copy();

        $restaurant->package_id = $defaultPackage->id;
        $restaurant->package_type = 'monthly';
        $restaurant->license_expire_on = now()->addMonth();
        $restaurant->status = 'license_expired';
        $restaurant->save();

        $this->createDefaultOfflineSubscription($restaurant, $defaultPackage);

        try {
            $restaurantUser = Restaurant::restaurantAdmin($restaurant);

            if ($restaurantUser) {
                $restaurantUser->notify(new SubscriptionExpire($restaurant, $subscriptionExpiredAt));
            }
        } catch (\Exception $e) {
            Log::error('Razorpay webhook: failed to notify restaurant of subscription expiry', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deactivateRazorpayBilling(GlobalSubscription $razorpaySubscription, Carbon $endedAt): void
    {
        $razorpaySubscription->subscription_status = 'inactive';
        $razorpaySubscription->ends_at = $endedAt->format('Y-m-d');
        $razorpaySubscription->save();

        GlobalInvoice::where('gateway_name', 'razorpay')
            ->where(function ($query) use ($razorpaySubscription) {
                $query->where('global_subscription_id', $razorpaySubscription->id);

                if ($razorpaySubscription->subscription_id) {
                    $query->orWhere('subscription_id', $razorpaySubscription->subscription_id);
                }
            })
            ->update([
                'next_pay_date' => null,
                'status' => 'inactive',
            ]);
    }

    private function createDefaultOfflineSubscription(Restaurant $restaurant, Package $package): void
    {
        $currencyId = $package->currency_id ?: global_setting()->currency_id;

        GlobalSubscription::where('restaurant_id', $restaurant->id)
            ->where('subscription_status', 'active')
            ->update(['subscription_status' => 'inactive']);

        $subscription = new GlobalSubscription();
        $subscription->restaurant_id = $restaurant->id;
        $subscription->package_id = $restaurant->package_id;
        $subscription->currency_id = $currencyId;
        $subscription->package_type = $restaurant->package_type;
        $subscription->quantity = 1;
        $subscription->gateway_name = 'offline';
        $subscription->subscription_status = 'active';
        $subscription->subscribed_on_date = now();
        $subscription->ends_at = $restaurant->license_expire_on;
        $subscription->transaction_id = str(str()->random(15))->upper();
        $subscription->save();

        $offlineInvoice = new GlobalInvoice();
        $offlineInvoice->global_subscription_id = $subscription->id;
        $offlineInvoice->restaurant_id = $subscription->restaurant_id;
        $offlineInvoice->currency_id = $subscription->currency_id;
        $offlineInvoice->package_id = $subscription->package_id;
        $offlineInvoice->package_type = $subscription->package_type;
        $offlineInvoice->total = 0.00;
        $offlineInvoice->pay_date = now();
        $offlineInvoice->next_pay_date = $subscription->ends_at;
        $offlineInvoice->gateway_name = 'offline';
        $offlineInvoice->transaction_id = $subscription->transaction_id;
        $offlineInvoice->save();
    }
}
