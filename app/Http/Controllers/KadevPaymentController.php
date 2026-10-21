<?php

namespace App\Http\Controllers;

use App\Models\KadevPayment;
use App\Models\Restaurant;
use App\Services\KadevPayService;
use Illuminate\Http\Request;

/**
 * Notification de paiement Kadev Pay (webhook). URL à saisir dans le tableau de bord Kadev Pay :
 * route('kadev.webhook', ['hash' => <hash du restaurant>]).
 */
class KadevPaymentController extends Controller
{
    public function webhook(Request $request, string $hash, KadevPayService $kadev)
    {
        $restaurant = Restaurant::where('hash', $hash)->first();
        $credentials = $restaurant?->paymentGateways;

        if (!$credentials || !$credentials->kadev_status) {
            return response('Not found', 404);
        }

        // Signature obligatoire : HMAC-SHA512 du corps brut avec le « secret webhook ».
        if (!KadevPayService::signatureIsValid($request->getContent(), $request->header('X-KadevPay-Signature'), $credentials->kadev_webhook_secret)) {
            return response('Signature invalide.', 401);
        }

        $payload = $request->json()->all();
        $data = $payload['data'] ?? [];

        // Seuls les paiements réussis nous intéressent ; les autres événements sont acquittés sans action.
        if (($payload['event'] ?? null) !== 'payment.success' || ($data['status'] ?? null) !== 'paid') {
            return response('Événement ignoré.', 200);
        }

        $paymentId = (int) ($data['metadata']['kadev_payment_id'] ?? 0);
        $reference = (string) ($data['reference'] ?? '');

        $payment = KadevPayment::find($paymentId);

        // Le paiement doit appartenir à un restaurant de cette adresse (sécurité multi-restaurants).
        if (!$payment || !$reference || !$payment->order || (int) $payment->order->branch?->restaurant_id !== (int) $restaurant->id) {
            return response('Paiement introuvable.', 404);
        }

        $kadev->markPaid($payment, $reference, (float) ($data['amount'] ?? 0), ($data['metadata']['source'] ?? null) !== 'bill');

        return response('Webhook traité avec succès.', 200);
    }
}
