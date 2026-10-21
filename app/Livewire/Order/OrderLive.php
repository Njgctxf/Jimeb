<?php

namespace App\Livewire\Order;

use App\Livewire\Concerns\ManagesLiveOrders;
use App\Models\Order;
use App\Models\OrderHistory;
use Livewire\Component;

/**
 * Jimbe : détail d'une commande pour le serveur (plats, client, paiement, historique, actions).
 */
class OrderLive extends Component
{
    use ManagesLiveOrders;

    public int $orderId;

    protected $listeners = ['refreshOrders' => '$refresh'];

    public function mount(int $orderId): void
    {
        // Le scope de branche des commandes garantit qu'on ne voit que celles du restaurant.
        abort_unless(Order::whereKey($orderId)->exists(), 404);
        $this->orderId = $orderId;
    }

    public function render()
    {
        $order = Order::with([
            'table.area', 'customer', 'waiter', 'payments',
            'items.menuItem', 'items.menuItemVariation', 'items.modifierOptions',
        ])->findOrFail($this->orderId);

        return view('livewire.order.order-live', [
            'order' => $order,
            'column' => self::columnOf($order),
            'history' => OrderHistory::where('order_id', $order->id)->orderBy('id')->get(),
            'collectOrder' => $this->collectOrderId ? $order : null,
        ]);
    }
}
