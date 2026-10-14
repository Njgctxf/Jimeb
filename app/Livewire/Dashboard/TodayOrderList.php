<?php

namespace App\Livewire\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Component;

class TodayOrderList extends Component
{
    protected $listeners = ['refreshOrders' => '$refresh'];

    public $waiterOrders;
    public $orders;

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

    public function markServed(int $orderId): void
    {
        $order = Order::find($orderId);

        if (!$order) {
            return;
        }

        // Si personne ne l'a prise, celui qui sert la prend
        if (!$order->waiter_id) {
            $order->waiter_id = user()->id;
        }

        $order->order_status = OrderStatus::SERVED;
        $order->save();
    }

    public function render()
    {
        // Limites de la journée d'activité
        $boundaries = getBusinessDayBoundaries(branch(), now());
        $startUTC = $boundaries['start']->setTimezone('UTC')->toDateTimeString();
        $endUTC = $boundaries['end']->setTimezone('UTC')->toDateTimeString();

        // Tous les serveurs voient toutes les commandes du jour
        $this->orders = Order::withCount('items')
            ->with('table.area', 'waiter', 'orderType', 'customer', 'items.menuItem')
            ->where('status', '<>', 'canceled')
            ->where('status', '<>', 'draft')
            ->where('orders.date_time', '>=', $startUTC)
            ->where('orders.date_time', '<=', $endUTC)
            ->orderBy('id', 'desc')
            ->get();

        $this->waiterOrders = $this->orders;

        return view('livewire.dashboard.today-order-list', [
            'waiterOrders' => $this->waiterOrders,
            'orders' => $this->orders,
        ]);
    }
}
