<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\ManagesLiveOrders;
use App\Models\Order;
use App\Models\WaiterRequest;
use Livewire\Component;

/**
 * Jimbe : résumé du service sur le tableau de bord (compteurs + son). Le traitement se fait dans « Commandes ».
 */
class LiveSummary extends Component
{
    public function render()
    {
        $boundaries = getBusinessDayBoundaries(branch(), now());

        $orders = Order::whereNotIn('status', ['canceled', 'draft'])
            ->where('date_time', '>=', $boundaries['start']->setTimezone('UTC')->toDateTimeString())
            ->where('date_time', '<=', $boundaries['end']->setTimezone('UTC')->toDateTimeString())
            ->get(['id', 'status', 'order_status']);

        $counts = ['new' => [], 'kitchen' => [], 'ready' => [], 'bill' => []];
        foreach ($orders as $order) {
            $column = TodayOrderList::columnOf($order);
            if ($column) {
                $counts[$column][] = $order->id;
            }
        }

        $calls = WaiterRequest::where('branch_id', branch()->id)->whereIn('status', ['pending', 'Pending'])->pluck('id');

        return view('livewire.dashboard.live-summary', ['counts' => $counts, 'calls' => $calls]);
    }
}
