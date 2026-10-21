<?php

namespace App\Livewire\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WaiterRequest;
use Livewire\Component;

/**
 * Tableau « Commandes en direct » : Nouvelles → En cuisine → Prêtes → À encaisser.
 * Un seul bouton par carte fait passer la commande à l'étape suivante.
 */
class TodayOrderList extends Component
{
    protected $listeners = ['refreshOrders' => '$refresh'];

    public const COLUMNS = ['new', 'kitchen', 'ready', 'bill'];

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

    /** Fait avancer la commande d'une étape (et garde les bons de cuisine synchronisés). */
    public function advance(int $orderId): void
    {
        $order = Order::find($orderId);

        if (!$order) {
            return;
        }

        if (!$order->waiter_id) {
            $order->waiter_id = user()->id;
        }

        switch (self::columnOf($order)) {
            case 'new':
                $this->syncKots($order, 'in_kitchen', 'cooking');
                $order->order_status = OrderStatus::PREPARING;
                break;
            case 'kitchen':
                $this->syncKots($order, 'food_ready', 'ready');
                $order->order_status = OrderStatus::FOOD_READY;
                break;
            case 'ready':
                $order->order_status = OrderStatus::SERVED;
                break;
            default:
                return;
        }

        $order->save();
    }

    public function markServed(int $orderId): void
    {
        $order = Order::find($orderId);

        if (!$order) {
            return;
        }

        if (!$order->waiter_id) {
            $order->waiter_id = user()->id;
        }

        $order->order_status = OrderStatus::SERVED;
        $order->save();
    }

    /** Commande dont on encaisse le paiement (fenêtre simple). */
    public ?int $collectOrderId = null;

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

    /** Envoie (ou relance) l'addition sur le téléphone du client. */
    public function sendBill(int $orderId): void
    {
        $order = Order::find($orderId);

        if ($order && $order->status !== 'paid') {
            $order->bill_sent_at = now();
            $order->bill_sent_count = min(255, (int) $order->bill_sent_count + 1);
            $order->save();
        }
    }

    /** Appel de table traité : on ferme toutes les demandes en attente de cette table. */
    public function closeCall(int $requestId): void
    {
        $request = WaiterRequest::where('id', $requestId)->where('branch_id', branch()->id)->first();

        if ($request) {
            WaiterRequest::where('table_id', $request->table_id)->where('branch_id', branch()->id)
                ->whereIn('status', ['pending', 'Pending'])
                ->update(['status' => 'completed']);
        }
    }

    public static function columnOf(Order $order): ?string
    {
        $status = $order->order_status?->value;

        return match (true) {
            in_array($status, ['placed', 'confirmed'], true) => 'new',
            $status === 'preparing' => 'kitchen',
            in_array($status, ['food_ready', 'ready_for_pickup'], true) => 'ready',
            $order->status !== 'paid' && $status !== 'cancelled' => 'bill',
            default => null,
        };
    }

    private function syncKots(Order $order, string $kotStatus, string $itemStatus): void
    {
        $kotIds = Kot::where('order_id', $order->id)->where('status', '!=', 'cancelled')->pluck('id');

        Kot::whereIn('id', $kotIds)->update(['status' => $kotStatus]);
        KotItem::whereIn('kot_id', $kotIds)->where('status', '!=', 'cancelled')->update(['status' => $itemStatus]);
    }

    public function render()
    {
        // Limites de la journée d'activité
        $boundaries = getBusinessDayBoundaries(branch(), now());
        $startUTC = $boundaries['start']->setTimezone('UTC')->toDateTimeString();
        $endUTC = $boundaries['end']->setTimezone('UTC')->toDateTimeString();

        // Tous les serveurs voient toutes les commandes du jour
        $orders = Order::withCount('items')
            ->with('table.area', 'waiter', 'customer', 'items.menuItem', 'items.menuItemVariation')
            ->whereNotIn('status', ['canceled', 'draft'])
            ->where('orders.date_time', '>=', $startUTC)
            ->where('orders.date_time', '<=', $endUTC)
            ->orderBy('id')
            ->get();

        $columns = collect(self::COLUMNS)->mapWithKeys(fn ($c) => [$c => collect()])->all();
        $finished = 0;

        foreach ($orders as $order) {
            $column = self::columnOf($order);
            $column ? $columns[$column]->push($order) : $finished++;
        }

        $calls = WaiterRequest::with('table')
            ->where('branch_id', branch()->id)
            ->whereIn('status', ['pending', 'Pending'])
            ->orderBy('id')
            ->get();

        return view('livewire.dashboard.today-order-list', [
            'collectOrder' => $this->collectOrderId ? $orders->firstWhere('id', $this->collectOrderId) : null,
            'columns' => $columns,
            'finished' => $finished,
            'calls' => $calls,
        ]);
    }
}
