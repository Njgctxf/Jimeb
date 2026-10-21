{{-- Détail d'une commande (service) : tout pour agir vite sur une seule commande --}}
@php
    $cur = restaurant()->currency_id;
    $status = $order->order_status?->value;
    $cancelled = $order->status === 'canceled' || $status === 'cancelled';
    $paid = $order->status === 'paid';
    $next = [
        'new' => [__('modules.order.boardToKitchen'), 'bg-amber-500'],
        'kitchen' => [__('modules.order.boardMarkReady'), 'bg-sky-500'],
        'ready' => [__('modules.order.boardMarkServed'), 'bg-emerald-500'],
    ][$column] ?? null;
    $pay = \App\Models\Order::PAY_METHODS[$order->pay_method] ?? null;
    $labels = [
        'placed' => ['📩', __('modules.order.liveReceived')],
        'confirmed' => ['📩', __('modules.order.liveReceived')],
        'preparing' => ['👨‍🍳', __('modules.order.boardKitchen')],
        'food_ready' => ['✅', __('modules.order.boardReady')],
        'served' => ['🍽️', __('modules.order.historyServed')],
        'completed' => ['🏁', __('modules.order.liveCompleted')],
        'cancelled' => ['✕', __('modules.order.historyCancelled')],
        'paid' => ['💰', __('modules.order.paid')],
        'bill_sent' => ['📨', __('modules.order.liveBillSent')],
    ];
    $methodLabels = ['cash' => '💵 ' . __('modules.order.payCash'), 'card' => '💳 ' . __('modules.order.payCard'), 'upi' => '📱 ' . __('modules.order.payMomo'), 'kadev' => '📱 ' . __('modules.order.payMomo')];
    $mins = (int) $order->date_time?->diffInMinutes(now());
@endphp

<div class="mx-auto max-w-3xl space-y-4 p-4" wire:poll.10s>
    <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-skin-base">← @lang('modules.order.liveBack')</a>

    {{-- En-tête : table, client, état --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $order->table?->table_code ?? __('modules.order.boardNoTable') }}</p>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $order->show_formatted_order_number }} · {{ $order->date_time?->timezone(timezone())->format('H:i') }}
                    @unless ($cancelled || $paid) · <span class="{{ $mins >= 20 ? 'font-bold text-red-600' : ($mins >= 10 ? 'font-semibold text-amber-600' : '') }}">⏱ {{ $mins }} min</span>@endunless
                </p>
            </div>
            <div class="text-right">
                @if ($cancelled)
                    <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-700">✕ @lang('modules.order.historyCancelled')</span>
                @else
                    @include('livewire.order.partials.status-select', ['order' => $order])
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                <p class="text-xs text-gray-400">@lang('modules.order.liveCustomer')</p>
                <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $order->customer?->name ?: __('modules.customer.walkin') }}</p>
                @if ($order->customer?->phone)
                    <a href="tel:{{ $order->customer->phone }}" class="text-sm text-skin-base">📞 {{ $order->customer->phone }}</a>
                @endif
            </div>
            <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                <p class="text-xs text-gray-400">@lang('modules.order.liveWaiter')</p>
                @if ($order->waiter)
                    <p class="font-semibold text-gray-800 dark:text-gray-100">👤 {{ $order->waiter->name }}@if ($order->waiter_id === user()->id) ({{ __('modules.order.you') }})@endif</p>
                @else
                    <button type="button" wire:click="claimOrder({{ $order->id }})" class="font-semibold text-skin-base underline">@lang('modules.order.takeOrder')</button>
                @endif
            </div>
        </div>

        @if ($next && ! $cancelled)
            <button type="button" wire:click="advance({{ $order->id }})" wire:loading.attr="disabled"
                class="mt-4 w-full rounded-xl {{ $next[1] }} py-3 text-base font-bold text-white hover:opacity-90 disabled:opacity-50">
                {{ $next[0] }} →
            </button>
        @endif
    </div>

    {{-- Plats --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <p class="font-semibold text-gray-800 dark:text-white">🍽️ @lang('modules.order.liveDishes')</p>
            @unless ($cancelled || $paid)
                <a href="{{ route('pos.kot', ['id' => $order->id]) }}" class="rounded-lg border border-skin-base px-3 py-1.5 text-xs font-semibold text-skin-base hover:bg-skin-base/10">＋ @lang('modules.order.newKot')</a>
            @endunless
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($order->items as $line)
                <div class="flex justify-between gap-3 py-2.5" wire:key="live-item-{{ $line->id }}">
                    <div>
                        <p class="text-sm text-gray-800 dark:text-gray-100"><b>{{ rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.') }}×</b> {{ $line->menuItem?->item_name }}
                            @if ($line->menuItemVariation)<span class="text-gray-400">({{ $line->menuItemVariation->variation }})</span>@endif
                        </p>
                        @foreach ($line->modifierOptions as $mod)
                            <p class="text-xs text-gray-500">+ {{ $mod->name }}</p>
                        @endforeach
                        @if ($line->note)
                            <p class="text-xs text-amber-600">📝 {{ $line->note }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ currency_format($line->amount, $cur) }}</span>
                </div>
            @endforeach
        </div>
        @if ($order->order_note)
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">📝 {{ $order->order_note }}</p>
        @endif
    </div>

    {{-- Addition --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-2 font-semibold text-gray-800 dark:text-white">🧾 @lang('modules.shop.billTitle')</p>
        @if ($pay)
            <div class="mb-3 flex flex-wrap gap-1.5 text-xs font-medium">
                <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-200">{{ $pay[0] }} @lang($pay[1])</span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-200">{{ $order->pay_when === 'now' ? '⚡ ' . __('modules.order.payNowShort') : '🍽️ ' . __('modules.order.payAfterShort') }}</span>
                @if ($order->pay_split > 1)
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-200">👥 ÷{{ $order->pay_split }} = {{ currency_format(ceil($order->total / $order->pay_split), $cur) }} / pers.</span>
                @endif
            </div>
        @endif
        <div class="space-y-1 text-sm">
            <div class="flex justify-between text-gray-500"><span>@lang('modules.order.subTotal')</span><span>{{ currency_format($order->sub_total, $cur) }}</span></div>
            @if ((float) $order->tip_amount > 0)
                <div class="flex justify-between text-gray-500"><span>@lang('modules.shop.billTip')</span><span>{{ currency_format($order->tip_amount, $cur) }}</span></div>
            @endif
            <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white"><span>@lang('modules.order.total')</span><span>{{ currency_format($order->total, $cur) }}</span></div>
            @foreach ($order->payments->where('payment_method', '!=', 'due') as $p)
                <div class="flex justify-between text-emerald-600"><span>✓ {{ $methodLabels[$p->payment_method] ?? ucfirst($p->payment_method) }}</span><span>− {{ currency_format($p->amount, $cur) }}</span></div>
            @endforeach
            @if (! $paid && ! $cancelled && (float) $order->amount_paid > 0)
                <div class="flex justify-between font-bold text-orange-600"><span>@lang('modules.shop.billRemaining')</span><span>{{ currency_format($order->remainingAmount(), $cur) }}</span></div>
            @endif
        </div>

        @if ($paid)
            <p class="mt-3 rounded-xl bg-emerald-50 py-2.5 text-center font-semibold text-emerald-700">✓ @lang('modules.order.paid')</p>
        @elseif (! $cancelled)
            @if ($order->bill_sent_at)
                <p class="mt-3 text-xs text-violet-600 dark:text-violet-300">📨 {{ __('modules.order.boardBillSentAt', ['time' => $order->bill_sent_at->timezone(timezone())->format('H:i')]) }}@if ($order->bill_sent_count > 1) · ×{{ $order->bill_sent_count }}@endif</p>
            @endif
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="button" wire:click="sendBill({{ $order->id }})" wire:loading.attr="disabled"
                    class="rounded-xl border border-violet-300 py-2.5 text-sm font-semibold text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-900/30">
                    📨 {{ $order->bill_sent_at ? __('modules.order.boardRemindBill') : __('modules.order.boardSendBill') }}
                </button>
                <button type="button" wire:click="collectPayment({{ $order->id }})"
                    class="rounded-xl bg-violet-500 py-2.5 text-sm font-semibold text-white hover:opacity-90">💰 @lang('modules.order.boardCollect')</button>
            </div>
        @endif
    </div>

    {{-- Historique horaire --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-3 font-semibold text-gray-800 dark:text-white">🕒 @lang('modules.order.liveTimeline')</p>
        <ol class="relative ms-2 border-s border-gray-200 dark:border-gray-700">
            @unless ($history->whereIn('status', ['placed', 'confirmed'])->isNotEmpty())
                <li class="mb-3 ms-4"><span class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full {{ $history->isEmpty() ? 'bg-skin-base' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                    <p class="text-sm text-gray-800 dark:text-gray-100">📩 @lang('modules.order.liveReceived')</p>
                    <p class="text-xs text-gray-400">{{ $order->date_time?->timezone(timezone())->format('H:i') }}</p>
                </li>
            @endunless
            @foreach ($history as $h)
                @php [$e, $l] = $labels[$h->status] ?? ['•', $h->status]; @endphp
                <li class="mb-3 ms-4"><span class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full {{ $loop->last ? 'bg-skin-base' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                    <p class="text-sm text-gray-800 dark:text-gray-100">{{ $e }} {{ $l }}</p>
                    <p class="text-xs text-gray-400">{{ $h->created_at?->timezone(timezone())->format('H:i') }}</p>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">🖨️ @lang('modules.order.livePrint')</a>
    </div>

    @include('livewire.order.partials.quick-collect')
</div>
