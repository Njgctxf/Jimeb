<?php

namespace App\Services;

use App\Models\KotItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderMoneyRecalculator
{
    /**
     * @return EloquentCollection<int, Order>
     */
    public function findOrders(string $orderNumber, ?int $restaurantId = null, ?int $branchId = null): EloquentCollection
    {
        $query = Order::withoutGlobalScopes()
            ->with(['branch.restaurant.currency'])
            ->where('order_number', $orderNumber);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($restaurantId) {
            $query->whereHas('branch', function ($branchQuery) use ($restaurantId) {
                $branchQuery->withoutGlobalScopes()->where('restaurant_id', $restaurantId);
            });
        }

        return $query->orderBy('id')->get();
    }

    public function loadOrder(int $orderId): Order
    {
        return Order::withoutGlobalScopes()
            ->with($this->relations())
            ->findOrFail($orderId);
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(Order $order): array
    {
        $order->loadMissing($this->relations());

        $branch = $order->branch;
        $restaurant = $branch?->restaurant;

        if (!$restaurant instanceof Restaurant) {
            throw new InvalidArgumentException('Could not resolve the restaurant for this order.');
        }

        return $this->withRestaurantContext($restaurant, $branch, function () use ($order, $restaurant, $branch) {
            $currencyId = $restaurant->currency_id;
            $decimals = currency_decimal_places($currencyId);
            $gross = 0.0;
            $lines = [];

            foreach ($order->items as $item) {
                $beforePrice = (float) $item->price;
                $beforeAmount = (float) $item->amount;
                $qty = (float) ($item->quantity ?? 1);

                if ((bool) ($item->is_free_item_from_stamp ?? false)) {
                    $newPrice = currency_round($beforePrice, $currencyId);
                    $newAmount = 0.0;
                } else {
                    $unit = $this->currentUnitPrice($order, $item);
                    $modifiers = (float) $item->modifierOptions->sum(function ($modifier) {
                        return (float) ($modifier->pivot->modifier_option_price ?? $modifier->price ?? 0);
                    });
                    $newPrice = currency_round($unit, $currencyId);
                    $newAmount = currency_round(($unit + $modifiers) * $qty, $currencyId);
                }

                $gross += $newAmount;
                $lines[] = [
                    'item_id' => $item->id,
                    'menu_item_id' => $item->menu_item_id,
                    'menu_item_variation_id' => $item->menu_item_variation_id,
                    'name' => $item->menuItem->item_name ?? ('#'.$item->id),
                    'before_price' => $beforePrice,
                    'before_amount' => $beforeAmount,
                    'new_price' => $newPrice,
                    'new_amount' => $newAmount,
                    'changed' => $this->amountsDiffer($beforePrice, $newPrice, $decimals)
                        || $this->amountsDiffer($beforeAmount, $newAmount, $decimals),
                ];
            }

            $charges = $order->charges->map(fn ($row) => $row->charge)->filter();
            $taxes = $order->taxes->map(fn ($row) => $row->tax)->filter();

            $totals = OrderTaxCalculator::totalsFromSubTotal(
                $gross,
                $order->discount_type,
                (float) ($order->discount_value ?? 0),
                $charges,
                (float) ($order->tip_amount ?? 0),
                (float) ($order->delivery_fee ?? 0),
                (bool) ($restaurant->tax_inclusive ?? false),
                (bool) ($restaurant->include_charges_in_tax_base ?? false),
                $taxes->isNotEmpty() ? $taxes : null
            );

            $oldAmountPaid = (float) ($order->amount_paid ?? 0);
            $paymentCount = $order->payments->count();
            $hasSplitPayments = $paymentCount > 1;
            $hasSplitOrders = $order->relationLoaded('splitOrders')
                ? $order->splitOrders->isNotEmpty()
                : $order->splitOrders()->exists();

            $totalFields = [
                'sub_total' => (float) $order->sub_total,
                'discount_amount' => (float) ($order->discount_amount ?? 0),
                'total_tax_amount' => (float) ($order->total_tax_amount ?? 0),
                'tax_base' => (float) ($order->tax_base ?? 0),
                'total' => (float) $order->total,
            ];

            $totalRows = [];
            $totalsChanged = false;
            foreach ($totalFields as $field => $oldValue) {
                $newValue = (float) ($totals[$field] ?? $oldValue);
                $changed = $this->amountsDiffer($oldValue, $newValue, $decimals);
                $totalsChanged = $totalsChanged || $changed;
                $totalRows[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'changed' => $changed,
                ];
            }

            $totalRows['amount_paid'] = [
                'old' => $oldAmountPaid,
                'new' => $oldAmountPaid,
                'changed' => false,
            ];

            $warnings = [];
            $warnings[] = 'uses_current_menu';
            if ($hasSplitPayments) {
                $warnings[] = 'multiple_payments';
            }
            if ($hasSplitOrders) {
                $warnings[] = 'split_order';
            }

            $linesChanged = collect($lines)->contains(fn ($line) => $line['changed']);

            return [
                'order_id' => $order->id,
                'order_number' => (string) $order->order_number,
                'order_status' => $order->status ?? $order->order_status?->value ?? null,
                'date_time' => optional($order->date_time)->toDateTimeString(),
                'restaurant_id' => $restaurant->id,
                'restaurant_name' => $restaurant->name,
                'branch_id' => $branch?->id,
                'branch_name' => $branch?->name,
                'currency_id' => $currencyId,
                'decimals' => $decimals,
                'tax_inclusive' => (bool) ($restaurant->tax_inclusive ?? false),
                'payment_count' => $paymentCount,
                'can_sync_payment' => $paymentCount === 1,
                'has_changes' => $linesChanged || $totalsChanged,
                'warnings' => $warnings,
                'lines' => $lines,
                'totals' => $totalRows,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(Order $order, bool $syncPayment = false): array
    {
        $preview = $this->preview($order);

        $this->withRestaurantContext(
            $order->branch->restaurant,
            $order->branch,
            function () use ($order, $preview, $syncPayment) {
                $this->persist($order, $preview, $syncPayment);
            }
        );

        if ($syncPayment && ($preview['can_sync_payment'] ?? false)) {
            $preview['totals']['amount_paid']['new'] = $preview['totals']['total']['new'];
            $preview['totals']['amount_paid']['changed'] = $this->amountsDiffer(
                (float) $preview['totals']['amount_paid']['old'],
                (float) $preview['totals']['amount_paid']['new'],
                (int) $preview['decimals']
            );
        }

        return $preview;
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    private function persist(Order $order, array $preview, bool $syncPayment): void
    {
        $order->loadMissing(['items', 'kot', 'payments']);

        DB::transaction(function () use ($order, $preview, $syncPayment) {
            $kotIds = $order->kot->pluck('id');

            foreach ($preview['lines'] as $row) {
                OrderItem::withoutGlobalScopes()
                    ->where('id', $row['item_id'])
                    ->update([
                        'price' => $row['new_price'],
                        'amount' => $row['new_amount'],
                    ]);

                if ($kotIds->isEmpty() || empty($row['menu_item_id'])) {
                    continue;
                }

                KotItem::query()
                    ->whereIn('kot_id', $kotIds)
                    ->where('menu_item_id', $row['menu_item_id'])
                    ->when($row['menu_item_variation_id'], function ($query) use ($row) {
                        $query->where('menu_item_variation_id', $row['menu_item_variation_id']);
                    }, function ($query) {
                        $query->whereNull('menu_item_variation_id');
                    })
                    ->update([
                        'price' => $row['new_price'],
                        'amount' => $row['new_amount'],
                    ]);
            }

            $payload = [
                'sub_total' => $preview['totals']['sub_total']['new'],
                'discount_amount' => $preview['totals']['discount_amount']['new'],
                'total_tax_amount' => $preview['totals']['total_tax_amount']['new'],
                'tax_base' => $preview['totals']['tax_base']['new'],
                'total' => $preview['totals']['total']['new'],
            ];

            if ($syncPayment && $order->payments->count() === 1) {
                $payload['amount_paid'] = $preview['totals']['total']['new'];
                $order->payments->first()->updateQuietly([
                    'amount' => $preview['totals']['total']['new'],
                ]);
            }

            $order->updateQuietly($payload);
        });
    }

    private function currentUnitPrice(Order $order, OrderItem $item): float
    {
        $orderTypeId = $order->order_type_id ? (int) $order->order_type_id : null;
        $deliveryAppId = $order->delivery_app_id ?? null;

        if ($item->menuItem && $orderTypeId) {
            $item->menuItem->setPriceContext($orderTypeId, $deliveryAppId);
            if ($item->menuItemVariation) {
                $item->menuItemVariation->setPriceContext($orderTypeId, $deliveryAppId);
            }
        }

        if ($item->menuItemVariation) {
            return (float) $item->menuItemVariation->price;
        }

        if ($item->menuItem) {
            return (float) $item->menuItem->price;
        }

        return (float) $item->price;
    }

    /**
     * @return array<string, mixed>
     */
    private function relations(): array
    {
        return [
            'branch.restaurant.currency',
            'items' => fn ($query) => $query->withoutGlobalScopes(),
            'items.menuItem' => fn ($query) => $query->withoutGlobalScopes(),
            'items.menuItemVariation' => fn ($query) => $query->withoutGlobalScopes(),
            'items.modifierOptions',
            'charges.charge',
            'taxes.tax',
            'payments',
            'kot.items',
            'splitOrders',
        ];
    }

    private function withRestaurantContext(Restaurant $restaurant, mixed $branch, callable $callback): mixed
    {
        $hadRestaurant = session()->has('restaurant');
        $hadBranch = session()->has('branch');
        $previousRestaurant = session('restaurant');
        $previousBranch = session('branch');

        $nullCurrencyKey = 'currency_format_setting';
        $currencyKey = 'currency_format_setting'.$restaurant->currency_id;
        $hadNullCurrency = session()->has($nullCurrencyKey);
        $previousNullCurrency = session($nullCurrencyKey);
        $hadCurrency = session()->has($currencyKey);
        $previousCurrency = session($currencyKey);

        session(['restaurant' => $restaurant->loadMissing('currency')]);
        if ($branch) {
            session(['branch' => $branch]);
        } else {
            session()->forget('branch');
        }
        session()->forget($nullCurrencyKey);
        session()->forget($currencyKey);

        try {
            return $callback();
        } finally {
            if ($hadRestaurant) {
                session(['restaurant' => $previousRestaurant]);
            } else {
                session()->forget('restaurant');
            }

            if ($hadBranch) {
                session(['branch' => $previousBranch]);
            } else {
                session()->forget('branch');
            }

            if ($hadNullCurrency) {
                session([$nullCurrencyKey => $previousNullCurrency]);
            } else {
                session()->forget($nullCurrencyKey);
            }

            if ($hadCurrency) {
                session([$currencyKey => $previousCurrency]);
            } else {
                session()->forget($currencyKey);
            }
        }
    }

    private function amountsDiffer(float $left, float $right, int $decimals): bool
    {
        $epsilon = 1 / (10 ** max(2, $decimals));

        return abs($left - $right) >= $epsilon;
    }
}
