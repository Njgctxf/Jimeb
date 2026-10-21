{{-- Commandes en direct : un tableau en colonnes, un bouton par étape. --}}
@php
    $jbCols = [
        'new' => ['🔔', __('modules.order.boardNew'), __('modules.order.boardToKitchen'), 'bg-amber-500'],
        'kitchen' => ['👨‍🍳', __('modules.order.boardKitchen'), __('modules.order.boardMarkReady'), 'bg-sky-500'],
        'ready' => ['✅', __('modules.order.boardReady'), __('modules.order.boardMarkServed'), 'bg-emerald-500'],
        'bill' => ['🧾', __('modules.order.boardBill'), __('modules.order.boardCollect'), 'bg-violet-500'],
    ];
    $jbNow = now();
@endphp

<div wire:poll.5s
    x-data="{
        known: null,
        sound: true,
        beep() {
            if (!this.sound) return;
            try {
                const c = new (window.AudioContext || window.webkitAudioContext)();
                [880, 1175, 1480].forEach((f, i) => {
                    const o = c.createOscillator(), g = c.createGain();
                    o.frequency.value = f; o.connect(g); g.connect(c.destination);
                    g.gain.setValueAtTime(0.25, c.currentTime + i * 0.18);
                    g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + i * 0.18 + 0.17);
                    o.start(c.currentTime + i * 0.18); o.stop(c.currentTime + i * 0.18 + 0.17);
                });
            } catch (e) {}
        },
        check() {
            const ids = [...this.$root.querySelectorAll('[data-alert-id]')].map(e => e.dataset.alertId);
            if (this.known !== null && ids.some(id => !this.known.includes(id))) { this.beep(); }
            this.known = ids;
        }
    }"
    x-init="try { sound = localStorage.getItem('jbBoardSound') !== '0'; } catch (e) {} check()"
    x-on:livewire:update.window="$nextTick(() => check())"
    x-on:jb-order-taken.window="alert(@js(__('modules.order.alreadyTaken')) + ' ' + ($event.detail.name || ''))"
    class="rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">

    {{-- En-tête --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span></span>
            <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('modules.order.boardTitle')</p>
            <span class="text-xs text-gray-400">· {{ $finished }} @lang('modules.order.boardFinished')</span>
        </div>
        <button type="button" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs text-gray-600 dark:border-gray-600 dark:text-gray-300"
            @click="sound = !sound; try { localStorage.setItem('jbBoardSound', sound ? '1' : '0'); } catch (e) {} if (sound) beep()">
            <span x-text="sound ? '🔊 ' + @js(__('modules.order.boardSoundOn')) : '🔇 ' + @js(__('modules.order.boardSoundOff'))"></span>
        </button>
    </div>

    {{-- Appels de table --}}
    @if ($calls->isNotEmpty())
        <div class="mb-3 flex flex-wrap gap-2">
            @foreach ($calls as $call)
                <div wire:key="jb-call-{{ $call->id }}" data-alert-id="call-{{ $call->id }}"
                    class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 dark:border-rose-800 dark:bg-rose-900/30">
                    <span class="text-xl">{{ ['bill' => '🧾', 'water' => '💧', 'other' => '💬'][$call->reason] ?? '🙋' }}</span>
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-rose-700 dark:text-rose-200">{{ $call->table?->table_code ?? '—' }} · @lang('modules.waiterRequest.reason_' . ($call->reason ?: 'help'))</p>
                        <p class="text-[11px] text-rose-500">{{ $call->created_at?->diffForHumans(short: true) }}</p>
                    </div>
                    <button type="button" wire:click="closeCall({{ $call->id }})" wire:loading.attr="disabled"
                        class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                        @lang('modules.order.boardCallDone')
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Colonnes --}}
    <div class="-mx-1 flex snap-x gap-3 overflow-x-auto px-1 pb-1 xl:grid xl:grid-cols-4 xl:overflow-visible">
        @foreach ($jbCols as $key => [$icon, $title, $action, $color])
            <section class="w-[85%] shrink-0 snap-start rounded-xl bg-gray-50 p-2.5 sm:w-[48%] xl:w-auto dark:bg-gray-900/40">
                <header class="mb-2 flex items-center justify-between px-1">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $icon }} {{ $title }}</p>
                    <span class="min-w-[22px] rounded-full {{ $color }} px-1.5 text-center text-xs font-bold text-white">{{ $columns[$key]->count() }}</span>
                </header>

                <div class="max-h-[560px] space-y-2 overflow-y-auto">
                    @forelse ($columns[$key] as $order)
                        @php
                            $mins = (int) $order->date_time?->diffInMinutes($jbNow);
                            $late = $key === 'bill' ? '' : ($mins >= 20 ? 'text-red-600 font-bold' : ($mins >= 10 ? 'text-amber-600 font-semibold' : 'text-gray-400'));
                        @endphp
                        <article wire:key="jb-board-{{ $order->id }}" @if ($key === 'new') data-alert-id="order-{{ $order->id }}" @endif
                            class="rounded-xl border border-gray-100 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800 {{ $key === 'new' ? 'ring-2 ring-amber-300/60' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-lg font-extrabold leading-none text-gray-900 dark:text-white">
                                        {{ $order->table?->table_code ?? __('modules.order.boardNoTable') }}
                                    </p>
                                    <p class="mt-1 truncate text-xs text-gray-500">
                                        {{ $order->customer?->name ?: __('modules.customer.walkin') }} · {{ $order->show_formatted_order_number }}
                                    </p>
                                </div>
                                <span class="shrink-0 text-xs {{ $late }}">⏱ {{ $mins }} min</span>
                            </div>

                            <ul class="mt-2 space-y-0.5 text-[13px] text-gray-700 dark:text-gray-200">
                                @foreach ($order->items as $line)
                                    <li><b>{{ rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.') }}×</b> {{ $line->menuItem?->item_name }}@if ($line->menuItemVariation) <span class="text-gray-400">({{ $line->menuItemVariation->variation }})</span>@endif @if ($line->note) <span class="text-amber-600">· {{ $line->note }}</span>@endif</li>
                                @endforeach
                            </ul>
                            @if ($order->order_note)
                                <p class="mt-1.5 rounded-lg bg-amber-50 px-2 py-1 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">📝 {{ $order->order_note }}</p>
                            @endif

                            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                @if ($order->waiter)
                                    <span class="truncate text-gray-500">👤 {{ $order->waiter->name }}@if ($order->waiter_id === user()->id) ({{ __('modules.order.you') }})@endif</span>
                                @else
                                    <button type="button" wire:click="claimOrder({{ $order->id }})" class="font-semibold text-skin-base underline">@lang('modules.order.takeOrder')</button>
                                @endif
                                <span class="shrink-0 font-semibold text-gray-800 dark:text-gray-100">{{ currency_format($order->total ?? 0, restaurant()->currency_id) }}</span>
                            </div>

                            <div class="mt-2.5 flex gap-2">
                                @if ($key === 'bill')
                                    <button type="button" wire:click="collectPayment({{ $order->id }})"
                                        class="flex-1 rounded-lg {{ $color }} px-3 py-2 text-sm font-semibold text-white hover:opacity-90">
                                        💰 {{ $action }}
                                    </button>
                                @else
                                    <button type="button" wire:click="advance({{ $order->id }})" wire:loading.attr="disabled" wire:target="advance({{ $order->id }})"
                                        class="flex-1 rounded-lg {{ $color }} px-3 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50">
                                        {{ $action }} →
                                    </button>
                                @endif
                                <button type="button" title="@lang('modules.order.details')"
                                    onclick="Livewire.dispatch('showOrderDetail', { id: {{ $order->id }} })"
                                    class="rounded-lg border border-gray-200 px-2.5 text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">⋯</button>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400 dark:border-gray-700">@lang('modules.order.boardEmpty')</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
