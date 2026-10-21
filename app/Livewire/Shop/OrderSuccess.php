<?php

namespace App\Livewire\Shop;

use App\Models\Branch;
use App\Models\KadevPayment;
use App\Models\Order;
use App\Services\KadevPayService;
use Livewire\Attributes\On;
use Livewire\Component;

class OrderSuccess extends Component
{

    public $id;
    public $order;
    public $restaurant;
    public $shopBranch;
    public $dateFormat;
    public $timeFormat;
    public $showLoyaltyOnSuccess = false;

    public function mount()
    {
        $this->order = Order::with([
            'taxes.tax',
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'orderCashCollection',
            'charges.charge',
        ])->where('id', $this->id)->firstOrFail();

        $this->showLoyaltyOnSuccess = function_exists('module_enabled') && module_enabled('Loyalty');

        if (is_null(customer()) && $this->restaurant->customer_login_required) {
            return $this->redirect(route('home'));
        }

        $this->restaurant->loadMissing('euAllergenSetting');

        if (request()->branch && request()->branch != '') {
            $this->shopBranch = Branch::find(request()->branch);
        } else {
            $this->shopBranch = $this->restaurant->branches->first();
        }

        // Set date and time formats
        $this->dateFormat = $this->restaurant->date_format ?? dateFormat();
        $this->timeFormat = $this->restaurant->time_format ?? timeFormat();
    }

    public function render()
    {
        return view('livewire.shop.order-success', [
            'taxMode' => $this->order->tax_mode ?? ($this->restaurant->tax_mode ?? 'order'),
        ]);
    }

    #[On('orderLoyaltyUpdated')]
    public function refreshOrderTotals(): void
    {
        $this->order->refresh();
        $this->order->load([
            'taxes.tax',
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'orderCashCollection',
            'charges.charge',
        ]);
    }

    public function refreshOrderSuccess()
    {
        $this->refreshOrderTotals();
        $this->dispatch('$refresh');
        $this->dispatch('refreshOrderSuccess');
    }

    /* ---------- Étape 6 : l'addition (pourboire, partage, serveur ou paiement en ligne) ---------- */

    public int $splitCount = 1;

    public ?string $billMessage = null;

    /** Pourboire en montant fixe ; recalcule le total tant que rien n'a été payé. */
    public function setTip($amount): void
    {
        $this->order->refresh();

        if (!$this->restaurant->enable_tip_shop || (float) $this->order->amount_paid > 0 || $this->order->order_status->value === 'cancelled') {
            return;
        }

        $amount = max(0, min(round((float) $amount), round((float) $this->order->sub_total)));
        $this->order->total = (float) $this->order->total - (float) $this->order->tip_amount + $amount;
        $this->order->tip_amount = $amount;
        $this->order->save();

        $this->refreshOrderTotals();
    }

    public function setSplit($count): void
    {
        $this->splitCount = max(1, min(20, (int) $count));
    }

    public function bumpSplit(int $delta): void
    {
        $this->setSplit(max(2, $this->splitCount + $delta));
    }

    /** Montant d'une part : le reste à payer divisé par le nombre de personnes (arrondi au franc supérieur). */
    public function shareAmount(): float
    {
        $remaining = $this->order->remainingAmount();

        return $this->splitCount > 1 ? min($remaining, ceil($remaining / $this->splitCount)) : $remaining;
    }

    public function callWaiterForBill(): void
    {
        if (!$this->restaurant->is_waiter_request_enabled || !$this->order->table_id) {
            $this->billMessage = __('modules.waiterRequest.callFailed');

            return;
        }

        $pending = \App\Models\WaiterRequest::where('table_id', $this->order->table_id)
            ->whereIn('status', ['pending', 'Pending'])->exists();

        if (!$pending) {
            \App\Models\WaiterRequest::create([
                'table_id' => $this->order->table_id,
                'branch_id' => $this->order->branch_id,
                'status' => 'pending',
                'reason' => 'bill',
            ]);
        }

        $this->billMessage = __('modules.shop.billWaiterComing');
    }

    /** Ouvre Kadev Pay pour une part (ou le reste). Le montant vient toujours du serveur. */
    public function payOnline(): void
    {
        $this->order->refresh();
        $credentials = $this->restaurant->paymentGateways;
        $amount = $this->shareAmount();

        if ($amount <= 0 || !$credentials?->kadev_status || !$credentials->kadev_public_key) {
            return;
        }

        $payment = KadevPayment::create([
            'order_id' => $this->order->id,
            'amount' => $amount,
            'payment_status' => 'pending',
        ]);

        $customer = $this->order->customer;
        $email = filter_var($customer?->email, FILTER_VALIDATE_EMAIL) ?: ('commande' . $this->order->id . '@' . (parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.com'));

        $this->dispatch('kadev-checkout', [
            'sdk' => KadevPayService::sdkUrl(),
            'publicKey' => $credentials->kadev_public_key,
            'amount' => (int) round($amount),
            'email' => $email,
            'name' => $customer?->name,
            'phone' => $customer?->phone,
            'paymentId' => $payment->id,
            'orderUuid' => $this->order->uuid,
        ]);
    }

    /** Après le widget : on vérifie auprès de Kadev Pay, jamais sur la seule parole du navigateur. */
    public function confirmKadevPayment($paymentId, $reference): void
    {
        $payment = KadevPayment::where('id', $paymentId)->where('order_id', $this->order->id)->first();
        $credentials = $this->restaurant->paymentGateways;

        if (!$payment || !$credentials) {
            return;
        }

        $service = app(KadevPayService::class);
        $result = $service->verify($credentials, (string) $reference);

        $this->billMessage = ($result && $result['status'] === 'paid' && $service->markPaid($payment, (string) $reference, $result['amount'], false))
            ? __('modules.shop.billPaidThanks')
            : __('modules.shop.billPaymentPending');

        $this->refreshOrderTotals();
    }
}
