<?php

namespace App\Livewire\Order;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Jimbe : historique simple des commandes (recherche + période), statuts en clair.
 */
class OrderHistory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $period = 'today';

    protected $queryString = ['search' => ['except' => ''], 'period' => ['except' => 'today']];

    public const PERIODS = ['today', 'yesterday', 'week', 'month'];

    public function updating($name): void
    {
        if (in_array($name, ['search', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $period = in_array($this->period, self::PERIODS, true) ? $this->period : 'today';
        $tz = timezone();

        [$from, $to] = match ($period) {
            'yesterday' => [now($tz)->subDay()->startOfDay(), now($tz)->subDay()->endOfDay()],
            'week' => [now($tz)->subDays(6)->startOfDay(), now($tz)->endOfDay()],
            'month' => [now($tz)->subDays(29)->startOfDay(), now($tz)->endOfDay()],
            default => [now($tz)->startOfDay(), now($tz)->endOfDay()],
        };

        $search = trim($this->search);

        $orders = Order::with('table', 'customer', 'payments')
            ->withCount('items')
            ->where('status', '<>', 'draft')
            ->whereBetween('date_time', [$from->clone()->setTimezone('UTC'), $to->clone()->setTimezone('UTC')])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', '%' . ltrim($search, '#') . '%')
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('table', fn ($t) => $t->where('table_code', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        $totals = Order::where('status', 'paid')
            ->whereBetween('date_time', [$from->clone()->setTimezone('UTC'), $to->clone()->setTimezone('UTC')])
            ->selectRaw('count(*) as n, coalesce(sum(total), 0) as amount')
            ->first();

        return view('livewire.order.order-history', [
            'orders' => $orders,
            'totals' => $totals,
        ]);
    }
}
