<?php

namespace App\Livewire\Concerns;

use App\Enums\OrderStatus;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Payment;
use App\Models\Table;

/**
 * Jimbe : actions rapides sur une commande, partagées par « Commandes en direct » et la page de détail.
 */
trait ManagesLiveOrders
{
    /** Commande dont on encaisse le paiement (fenêtre simple). */
    public ?int $collectOrderId = null;

    /** États proposés dans le menu déroulant (dans l'ordre du service). */
    public static function quickStatuses(): array
    {
        return [
            'confirmed' => ['🔔', __('modules.order.boardNew')],
            'preparing' => ['👨‍🍳', __('modules.order.boardKitchen')],
            'food_ready' => ['✅', __('modules.order.boardReady')],
            'served' => ['🍽️', __('modules.order.historyServed')],
        ];
    }

    /** Colonne du tableau : new | kitchen | ready | bill | null (terminée). */
    public static function columnOf(Order $order): ?string
    {
        $status = $order->order_status?->value;

        return match (true) {
            in_array($status, ['placed', 'confirmed'], true) => 'new',
            $status === 'preparing' => 'kitchen',
            in_array($status, ['food_ready', 'ready_for_pickup'], true) => 'ready',
            $order->status !== 'paid' && $order->status !== 'canceled' && $status !== 'cancelled' => 'bill',
            default => null,
        };
    }

    /** Le premier serveur qui prend la commande se l'attribue. */
    public function claimOrder(int $orderId): void
    {
        $order = Order::find($orderId);

        if (!$order) {
            return;
        }

        $updated = Order::where('id', $orderId)->whereNull('waiter_id')->update(['waiter_id' => user()->id]);

        if (!$updated && $order->waiter_id !== user()->id) {
            $this->dispatch('jb-order-taken', name: $order->waiter?->name);
        }
    }

    /** Gros bouton : fait avancer la commande d'une étape. */
    public function advance(int $orderId): void
    {
        $order = Order::find($orderId);

        $next = match ($order ? self::columnOf($order) : null) {
            'new' => 'preparing',
            'kitchen' => 'food_ready',
            'ready' => 'served',
            default => null,
        };

        if ($next) {
            $this->setStatus($orderId, $next);
        }
    }

    /** Menu déroulant : met la commande dans l'état choisi (en avant comme en arrière). */
    public function setStatus(int $orderId, string $status): void
    {
        $order = Order::find($orderId);

        if (!$order || !array_key_exists($status, self::quickStatuses()) || $order->status === 'canceled') {
            return;
        }

        if (!$order->waiter_id) {
            $order->waiter_id = user()->id;
        }

        match ($status) {
            'confirmed' => $this->syncKots($order, 'pending_confirmation', 'pending'),
            'preparing' => $this->syncKots($order, 'in_kitchen', 'cooking'),
            'food_ready', 'served' => $this->syncKots($order, 'food_ready', 'ready'),
        };

        $order->order_status = $status;
        $order->save();
    }

    /** Annulation (avec confirmation côté écran). */
    public function cancelOrder(int $orderId, ?string $reason = null): void
    {
        $order = Order::find($orderId);

        if (!$order || $order->status === 'paid' || !user_can('Update Order')) {
            return;
        }

        $order->update([
            'status' => 'canceled',
            'order_status' => 'cancelled',
            'cancel_reason_text' => trim((string) $reason) ?: __('modules.order.cancelledFromBoard'),
            'cancelled_by' => user()->id,
            'cancel_time' => now(),
        ]);

        Kot::where('order_id', $order->id)->update(['status' => 'cancelled']);

        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['available_status' => 'available']);
        }
    }

    /** Envoie (ou relance) l'addition sur le téléphone du client. */
    public function sendBill(int $orderId): void
    {
        $order = Order::find($orderId);

        if ($order && $order->status !== 'paid') {
            $order->bill_sent_at = now();
            $order->bill_sent_count = min(255, (int) $order->bill_sent_count + 1);
            $order->save();
            OrderHistory::record($order->id, 'bill_sent');
        }
    }

    public function collectPayment(int $orderId): void
    {
        $this->collectOrderId = $orderId;
    }

    public function closeCollect(): void
    {
        $this->collectOrderId = null;
    }

    /** Cas complexes (paiement partiel, pourboire…) : fenêtre de caisse complète. */
    public function advancedPayment(): void
    {
        $id = $this->collectOrderId;
        $this->collectOrderId = null;
        $this->dispatch('showPaymentModal', id: $id);
    }

    /** Encaissement en un clic du reste à payer, avec le moyen choisi. */
    public function quickPay(string $method): void
    {
        $order = Order::find($this->collectOrderId);
        $paymentMethod = Order::PAY_METHODS[$method][2] ?? null;

        if (!$order || !$paymentMethod || $order->status === 'paid') {
            $this->collectOrderId = null;

            return;
        }

        $remaining = $order->remainingAmount();

        if ($remaining > 0) {
            Payment::create([
                'order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'payment_method' => $paymentMethod,
                'amount' => $remaining,
                'balance' => 0,
                'is_due' => false,
            ]);
        }

        // Le reste dû est soldé : on retire la ligne « dû » et on recalcule.
        Payment::where('order_id', $order->id)->where('payment_method', 'due')->delete();
        $order->amount_paid = Payment::where('order_id', $order->id)->where('payment_method', '!=', 'due')->sum('amount');
        $order->status = 'paid';
        $order->save();

        \App\Events\SendNewOrderReceived::dispatch($order);

        $this->collectOrderId = null;
        $this->dispatch('jb-paid');
    }

    private function syncKots(Order $order, string $kotStatus, string $itemStatus): void
    {
        $kotIds = Kot::where('order_id', $order->id)->where('status', '!=', 'cancelled')->pluck('id');

        Kot::whereIn('id', $kotIds)->update(['status' => $kotStatus]);
        KotItem::whereIn('kot_id', $kotIds)->where('status', '!=', 'cancelled')->update(['status' => $itemStatus]);
    }
}
