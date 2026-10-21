<?php

namespace App\Services;

use App\Events\SendNewOrderReceived;
use App\Events\SendOrderBillEvent;
use App\Models\KadevPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewayCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kadev Pay (https://pay.kadev.ci) : vérification d'un paiement, signature des webhooks et validation d'une commande.
 *
 * Règle : une commande n'est marquée payée qu'après une confirmation de Kadev Pay (webhook signé ou vérification
 * serveur à serveur). Le retour dans le navigateur du client ne suffit jamais.
 */
class KadevPayService
{
    public const BASE_URL = 'https://pay.kadev.ci';

    public static function sdkUrl(): string
    {
        return self::BASE_URL . '/js/v1/kadev-pay.js';
    }

    /** Signature d'un webhook : HMAC-SHA512 du corps brut, clé = « secret webhook » du tableau de bord Kadev Pay. */
    public static function signatureIsValid(string $rawBody, ?string $signature, ?string $webhookSecret): bool
    {
        if (!$signature || !$webhookSecret) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawBody, $webhookSecret), $signature);
    }

    /**
     * Vérifie une transaction auprès de Kadev Pay (appel serveur, clé secrète).
     *
     * @return array{status:string, amount:float, currency:?string}|null null si inconnue ou en erreur
     */
    public function verify(PaymentGatewayCredential $credentials, string $reference): ?array
    {
        $secret = $credentials->kadev_secret_key;

        if (!$secret || !preg_match('/^[A-Za-z0-9._-]{4,80}$/', $reference)) {
            return null;
        }

        try {
            $response = Http::withToken($secret)->acceptJson()->timeout(15)
                ->get(self::BASE_URL . '/api/v1/transactions/verify/' . $reference);
        } catch (\Throwable $e) {
            Log::warning('Kadev Pay : vérification impossible', ['reference' => $reference, 'error' => $e->getMessage()]);

            return null;
        }

        $json = $response->json();

        if (!$response->ok() || ($json['status'] ?? null) !== 'success' || !isset($json['data'])) {
            return null;
        }

        return [
            'status' => (string) ($json['data']['status'] ?? ''),
            'amount' => (float) ($json['data']['amount'] ?? 0),
            'currency' => $json['data']['currency'] ?? null,
        ];
    }

    /**
     * Marque la commande payée (idempotent). $paidAmount = montant confirmé par Kadev Pay.
     *
     * $notify = false pour un règlement depuis la page de suivi : la commande est déjà en cuisine.
     *
     * @return bool true si le paiement est validé à l'issue de l'appel
     */
    public function markPaid(KadevPayment $payment, string $reference, float $paidAmount, bool $notify = true): bool
    {
        if ($payment->payment_status === 'completed') {
            return true;
        }

        // Le client doit avoir payé au moins le montant de la commande.
        if ($paidAmount + 0.001 < (float) $payment->amount) {
            $payment->update([
                'payment_status' => 'failed',
                'payment_error_response' => json_encode(['message' => 'Montant payé inférieur au montant de la commande', 'paid' => $paidAmount]),
            ]);

            return false;
        }

        // Une référence ne peut valider qu'un seul paiement.
        if (KadevPayment::where('reference', $reference)->where('id', '!=', $payment->id)->exists()) {
            return false;
        }

        DB::transaction(function () use ($payment, $reference) {
            $payment->update([
                'reference' => $reference,
                'payment_status' => 'completed',
                'payment_date' => now(),
            ]);

            $order = Order::find($payment->order_id);

            Payment::updateOrCreate(
                ['order_id' => $payment->order_id, 'payment_method' => 'due', 'amount' => $payment->amount],
                ['payment_method' => 'kadev', 'branch_id' => $order?->branch_id, 'transaction_id' => $reference]
            );

            if ($order) {
                $order->amount_paid = $order->amount_paid + $payment->amount;
                // Addition partagée : la commande n'est « payée » qu'une fois toutes les parts réglées.
                if (round((float) $order->amount_paid, 2) + 0.01 >= round((float) $order->total, 2)) {
                    $order->status = 'paid';
                }
                $order->save();
            }
        });

        $order = Order::find($payment->order_id);

        if ($order && $notify) {
            ShopCartKotPrintUrls::flashDeferredKotPrintForShopOrder($order);
            SendNewOrderReceived::dispatch($order);

            if ($order->customer_id) {
                SendOrderBillEvent::dispatch($order);
            }
        }

        return true;
    }
}
