<?php

namespace App\Livewire\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
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

    public function collectPayment(int $orderId): void
    {
        $this->dispatch('showPaymentModal', id: $orderId);
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
            'columns' => $columns,
            'finished' => $finished,
            'calls' => $calls,
        ]);
    }
}
