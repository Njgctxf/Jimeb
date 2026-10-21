<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\ManagesLiveOrders;
use App\Models\Order;
use App\Models\WaiterRequest;
use Livewire\Component;

/**
 * Tableau « Commandes en direct » : Nouvelles → En cuisine → Prêtes → À encaisser.
 * Gros bouton pour l'étape suivante, menu déroulant pour choisir un autre état, carte → page de détail.
 */
class TodayOrderList extends Component
{
    use ManagesLiveOrders;

    protected $listeners = ['refreshOrders' => '$refresh'];

    public const COLUMNS = ['new', 'kitchen', 'ready', 'bill'];

    /** « Mes commandes » : seulement celles du serveur connecté (et celles que personne n'a prises). */
    public bool $mine = false;

    public function mount(): void
    {
        $this->mine = (bool) session('jb_board_mine', false);
    }

    public function updatedMine($value): void
    {
        session(['jb_board_mine' => (bool) $value]);
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

    public function render()
    {
        // Limites de la journée d'activité
        $boundaries = getBusinessDayBoundaries(branch(), now());
        $startUTC = $boundaries['start']->setTimezone('UTC')->toDateTimeString();
        $endUTC = $boundaries['end']->setTimezone('UTC')->toDateTimeString();

        // Les plus anciennes d'abord : ce sont celles qui attendent le plus
        $orders = Order::withCount('items')
            ->with('table.area', 'waiter', 'customer', 'items.menuItem', 'items.menuItemVariation')
            ->whereNotIn('status', ['canceled', 'draft'])
            ->where('orders.date_time', '>=', $startUTC)
            ->where('orders.date_time', '<=', $endUTC)
            ->when($this->mine, fn ($q) => $q->where(fn ($q) => $q->whereNull('waiter_id')->orWhere('waiter_id', user()->id)))
            ->orderBy('date_time')
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
            'collectOrder' => $this->collectOrderId ? Order::with('table')->find($this->collectOrderId) : null,
            'columns' => $columns,
            'finished' => $finished,
            'calls' => $calls,
        ]);
    }
}
