<?php

namespace App\Livewire\Superadmin;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Services\OrderMoneyRecalculator;
use InvalidArgumentException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class OrderMoneyRecalculation extends Component
{
    use LivewireAlert;

    public string $restaurantSearch = '';

    public ?int $restaurantId = null;

    public ?int $branchId = null;

    public string $orderNumber = '';

    public bool $syncPayment = false;

    public array $matches = [];

    public ?int $selectedOrderId = null;

    public ?array $preview = null;

    public bool $applied = false;

    public function mount(): void
    {
        abort_if((! user_can('Show Restaurant')), 403);
    }

    public function updatedRestaurantId(): void
    {
        $this->branchId = null;
        $this->resetPreviewState();

        $branches = $this->branchesForRestaurant();
        if ($branches->count() === 1) {
            $this->branchId = $branches->first()->id;
        }
    }

    public function updatedBranchId(): void
    {
        $this->resetPreviewState();
    }

    public function updatedOrderNumber(): void
    {
        $this->resetPreviewState();
    }

    public function previewOrder(): void
    {
        $this->applied = false;
        $this->preview = null;
        $this->matches = [];
        $this->selectedOrderId = null;

        $this->validate([
            'restaurantId' => 'required|integer',
            'orderNumber' => 'required|string',
        ]);

        $matches = $this->recalculator()->findOrders(
            trim($this->orderNumber),
            $this->restaurantId,
            $this->branchId
        );

        if ($matches->isEmpty()) {
            $this->alert('error', __('superadmin.orderMoneyRecalculation.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);

            return;
        }

        if ($matches->count() > 1) {
            $this->matches = $matches->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => (string) $order->order_number,
                    'branch_id' => $order->branch_id,
                    'branch_name' => $order->branch?->name,
                    'total' => (float) $order->total,
                    'date_time' => optional($order->date_time)->toDateTimeString(),
                    'status' => $order->status ?? $order->order_status?->value ?? '',
                ];
            })->all();

            return;
        }

        $this->loadPreviewFor($matches->first()->id);
    }

    public function selectMatch(int $orderId): void
    {
        $this->loadPreviewFor($orderId);
    }

    public function confirmApply(): void
    {
        if (!$this->preview || $this->applied) {
            return;
        }

        $decimals = (int) ($this->preview['decimals'] ?? 2);
        $oldTotal = number_format((float) $this->preview['totals']['total']['old'], $decimals);
        $newTotal = number_format((float) $this->preview['totals']['total']['new'], $decimals);

        $this->confirm(__('superadmin.orderMoneyRecalculation.confirmApply'), [
            'text' => __('superadmin.orderMoneyRecalculation.confirmApplyText', [
                'old' => $oldTotal,
                'new' => $newTotal,
            ]),
            'confirmButtonText' => __('app.yes'),
            'cancelButtonText' => __('app.cancel'),
            'onConfirmed' => 'applyRecalculation',
        ]);
    }

    public function applyRecalculation(): void
    {
        if (!$this->selectedOrderId || $this->applied) {
            return;
        }

        try {
            $order = $this->recalculator()->loadOrder($this->selectedOrderId);
            $this->preview = $this->recalculator()->apply($order, $this->syncPayment);
            $this->applied = true;

            $this->alert('success', __('superadmin.orderMoneyRecalculation.applied'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->alert('error', $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.superadmin.order-money-recalculation', [
            'restaurants' => $this->restaurantsForSelect(),
            'branches' => $this->branchesForRestaurant(),
        ]);
    }

    private function loadPreviewFor(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->applied = false;

        try {
            $this->preview = $this->recalculator()->preview(
                $this->recalculator()->loadOrder($orderId)
            );

            if (!($this->preview['can_sync_payment'] ?? false)) {
                $this->syncPayment = false;
            }
        } catch (InvalidArgumentException $e) {
            $this->preview = null;
            $this->alert('error', $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
        }
    }

    private function resetPreviewState(): void
    {
        $this->matches = [];
        $this->selectedOrderId = null;
        $this->preview = null;
        $this->applied = false;
        $this->syncPayment = false;
    }

    private function restaurantsForSelect()
    {
        $query = Restaurant::query()
            ->select('id', 'name')
            ->when($this->restaurantSearch !== '', function ($inner) {
                $inner->where('name', 'like', '%'.$this->restaurantSearch.'%');
            })
            ->orderBy('name')
            ->limit(40);

        $restaurants = $query->get();

        if ($this->restaurantId && !$restaurants->contains('id', $this->restaurantId)) {
            $selected = Restaurant::query()->select('id', 'name')->find($this->restaurantId);
            if ($selected) {
                $restaurants = $restaurants->prepend($selected);
            }
        }

        return $restaurants;
    }

    private function branchesForRestaurant()
    {
        if (!$this->restaurantId) {
            return collect();
        }

        return Branch::withoutGlobalScopes()
            ->select('id', 'name', 'restaurant_id')
            ->where('restaurant_id', $this->restaurantId)
            ->orderBy('name')
            ->get();
    }

    private function recalculator(): OrderMoneyRecalculator
    {
        return app(OrderMoneyRecalculator::class);
    }
}
