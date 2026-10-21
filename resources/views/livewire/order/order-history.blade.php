{{-- Historique simple des commandes --}}
@php
    $jbService = [
        'placed' => ['🔔', __('modules.order.boardNew'), 'bg-amber-100 text-amber-800'],
        'confirmed' => ['🔔', __('modules.order.boardNew'), 'bg-amber-100 text-amber-800'],
        'preparing' => ['👨‍🍳', __('modules.order.boardKitchen'), 'bg-sky-100 text-sky-800'],
        'food_ready' => ['✅', __('modules.order.boardReady'), 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['✕', __('modules.order.historyCancelled'), 'bg-red-100 text-red-700'],
    ];
    $jbServed = ['🍽️', __('modules.order.historyServed'), 'bg-gray-100 text-gray-700'];
    $jbMethods = [
        'cash' => '💵 ' . __('modules.order.payCash'),
        'card' => '💳 ' . __('modules.order.payCard'),
        'upi' => '📱 ' . __('modules.order.payMomo'),
        'kadev' => '📱 ' . __('modules.order.payMomo'),
    ];
@endphp

<div class="rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-wrap items-center gap-2">
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="@lang('modules.order.historySearch')"
            class="min-w-[200px] flex-1 rounded-lg border-gray-200 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
        <div class="flex overflow-hidden rounded-lg border border-gray-200 text-sm dark:border-gray-600">
            @foreach (\App\Livewire\Order\OrderHistory::PERIODS as $p)
                <button type="button" wire:click="$set('period', '{{ $p }}')"
                    class="px-3 py-2 {{ $period === $p ? 'bg-skin-base text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    @lang('modules.order.period_' . $p)
                </button>
            @endforeach
        </div>
    </div>

    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
        💰 {{ __('modules.order.historyTotals', ['count' => $totals->n, 'amount' => currency_format($totals->amount, restaurant()->currency_id)]) }}
    </p>

    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($orders as $order)
            @php
                $status = $order->order_status?->value;
                $service = $jbService[$status] ?? $jbServed;
                $paidWith = $order->payments->where('payment_method', '!=', 'due')->pluck('payment_method')->unique()
                    ->map(fn ($m) => $jbMethods[$m] ?? ucfirst($m))->join(' + ');
            @endphp
            <button type="button" wire:key="hist-{{ $order->id }}" onclick="Livewire.dispatch('showOrderDetail', { id: {{ $order->id }} })"
                class="flex w-full flex-wrap items-center gap-x-4 gap-y-1 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40">
                <span class="w-14 text-sm font-bold text-gray-900 dark:text-white">{{ $order->table?->table_code ?? '—' }}</span>
                <span class="min-w-[140px] flex-1">
                    <span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $order->customer?->name ?: __('modules.customer.walkin') }}</span>
                    <span class="block text-xs text-gray-400">{{ $order->show_formatted_order_number }} · {{ $order->date_time?->timezone(timezone())->format('d/m H:i') }} · {{ $order->items_count }} @lang('modules.order.historyDishes')</span>
                </span>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $service[2] }}">{{ $service[0] }} {{ $service[1] }}</span>
                @if ($order->status === 'paid')
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">✓ @lang('modules.order.paid'){{ $paidWith ? ' · ' . $paidWith : '' }}</span>
                @elseif ($status !== 'cancelled')
                    <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700">⏳ @lang('modules.order.historyToPay')</span>
                @endif
                <span class="w-24 text-right text-sm font-bold text-gray-900 dark:text-white">{{ currency_format($order->total, restaurant()->currency_id) }}</span>
            </button>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">@lang('modules.order.historyEmpty')</p>
        @endforelse
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
</div>
