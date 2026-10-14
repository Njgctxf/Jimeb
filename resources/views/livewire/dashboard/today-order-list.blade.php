<div wire:poll.5s
    x-data="{
        known: null,
        beep() {
            try {
                const c = new (window.AudioContext || window.webkitAudioContext)();
                [880, 1175].forEach((f, i) => {
                    const o = c.createOscillator(), g = c.createGain();
                    o.frequency.value = f; o.connect(g); g.connect(c.destination);
                    g.gain.setValueAtTime(0.25, c.currentTime + i * 0.22);
                    g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + i * 0.22 + 0.2);
                    o.start(c.currentTime + i * 0.22); o.stop(c.currentTime + i * 0.22 + 0.2);
                });
            } catch (e) {}
        },
        check() {
            const ids = [...this.$root.querySelectorAll('[data-order-id]')].map(e => e.dataset.orderId);
            if (this.known !== null && ids.some(id => !this.known.includes(id))) { this.beep(); }
            this.known = ids;
        }
    }"
    x-init="check()"
    x-on:livewire:update.window="$nextTick(() => check())"
    x-on:jb-order-taken.window="alert(@js(__('modules.order.alreadyTaken')) + ' ' + ($event.detail.name || ''))">

    <div class="bg-white rounded-xl border border-gray-100 p-5 dark:bg-gray-800 dark:border-gray-700 max-h-[560px] overflow-y-auto w-full h-full">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium text-gray-700 dark:text-white">@lang('modules.order.todayOrder')</p>
            <span class="text-[11px] text-gray-400 dark:text-white">{{ $orders->count() }} @lang('modules.order.total')</span>
        </div>

        <div class="space-y-3">
            @forelse ($orders as $order)
                @php
                    $isServed = in_array($order->order_status?->value, ['served', 'completed', 'delivered'], true);
                    $customerLabel = $order->customer?->name ?: __('modules.customer.walkin');
                @endphp
                <div class="rounded-xl border p-3 {{ $isServed ? 'border-gray-100 bg-gray-50 opacity-75 dark:border-gray-700 dark:bg-gray-700/40' : 'border-skin-base/30 bg-skin-base/[0.04]' }}"
                    wire:key="today-order-{{ $order->id }}" data-order-id="{{ $order->id }}">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 rounded-lg bg-skin-base/[0.15] text-skin-base">
                            @include('livewire.dashboard.partials.table-area-badge', ['order' => $order])
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-[13px] font-semibold text-gray-800 dark:text-gray-100">{{ $customerLabel }}</p>
                                <p class="shrink-0 text-[13px] font-semibold text-gray-800 dark:text-gray-100">{{ currency_format($order->display_total ?? $order->total ?? 0, restaurant()->currency_id) }}</p>
                            </div>
                            <p class="text-[11px] text-gray-400">
                                {{ $order->date_time?->timezone(timezone())->translatedFormat(timeFormat()) }} · {{ $order->show_formatted_order_number }}
                            </p>
                            <ul class="mt-1.5 space-y-0.5 text-[12px] text-gray-700 dark:text-gray-200">
                                @foreach ($order->items as $line)
                                    <li>{{ rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.') }} × {{ $line->menuItem?->item_name }}@if ($line->note) <span class="text-gray-400">({{ $line->note }})</span>@endif</li>
                                @endforeach
                            </ul>
                            @if ($order->order_note)
                                <p class="mt-1 text-[11px] italic text-amber-700">📝 {{ $order->order_note }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-block rounded-full px-2 py-0.5 text-[10px] {{ $order->order_status->badgeClasses() }}">@lang('modules.order.info_' . $order->order_status->value)</span>

                        <div class="flex items-center gap-2">
                            @if ($order->waiter)
                                <span class="text-[11px] font-medium text-gray-600 dark:text-gray-300">👤 {{ $order->waiter->name }}@if ($order->waiter_id === user()->id) ({{ __('modules.order.you') }})@endif</span>
                            @elseif (! $isServed)
                                <button type="button" wire:click="claimOrder({{ $order->id }})" wire:loading.attr="disabled"
                                    class="rounded-lg bg-skin-base px-3 py-1.5 text-[11px] font-semibold text-white hover:opacity-90">
                                    @lang('modules.order.takeOrder')
                                </button>
                            @endif

                            @if (! $isServed)
                                <button type="button" wire:click="markServed({{ $order->id }})" wire:loading.attr="disabled"
                                    class="rounded-lg border border-skin-base px-3 py-1.5 text-[11px] font-semibold text-skin-base hover:bg-skin-base/10">
                                    @lang('modules.order.markServed')
                                </button>
                            @endif

                            <a href="#" onclick="event.preventDefault(); Livewire.dispatch('showOrderDetail', { id: {{ $order->id }} }); return false;"
                                class="text-[11px] text-gray-400 underline">@lang('modules.order.details')</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="group flex justify-center gap-3 items-center border h-36 font-medium bg-white shadow-sm rounded-lg dark:bg-gray-700 dark:border-gray-600 p-3 dark:text-gray-400 text-sm">
                    @lang('messages.waitingTodayOrder')
                </div>
            @endforelse
        </div>
    </div>
</div>
