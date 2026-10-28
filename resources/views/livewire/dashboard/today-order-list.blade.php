{{-- Commandes en direct : une page par étape, un bouton par étape. --}}
@php
    // [titre, action, couleur du bouton, pastille, bordure des cartes]
    $jbCols = [
        'new' => [__('modules.order.boardNew'), __('modules.order.boardToKitchen'), 'bg-amber-500 hover:bg-amber-600', 'bg-amber-500', 'border-amber-300 dark:border-amber-700'],
        'kitchen' => [__('modules.order.boardKitchen'), __('modules.order.boardMarkReady'), 'bg-sky-500 hover:bg-sky-600', 'bg-sky-500', 'border-gray-200 dark:border-gray-700'],
        'ready' => [__('modules.order.boardReady'), __('modules.order.boardMarkServed'), 'bg-emerald-500 hover:bg-emerald-600', 'bg-emerald-500', 'border-gray-200 dark:border-gray-700'],
        'bill' => [__('modules.order.boardBill'), __('modules.order.boardCollect'), 'bg-violet-500 hover:bg-violet-600', 'bg-violet-500', 'border-gray-200 dark:border-gray-700'],
    ];
    $jbNow = now();
    $jbQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 3, '.', ''), '0'), '.');
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
            // Nombre de commandes que personne n'a prises (+ appels) dans le titre de l'onglet
            const n = this.$root.querySelectorAll('[data-unclaimed], [data-alert-id^=call]').length;
            this.baseTitle ??= document.title.replace(/^\(\d+\)\s*/, '');
            document.title = (n ? '(' + n + ') ' : '') + this.baseTitle;
        },
        baseTitle: null,
        init() {
            try { this.sound = localStorage.getItem('jbBoardSound') !== '0'; } catch (e) {}
            this.check();
            // Après chaque rafraîchissement Livewire : nouvelles commandes ? (son + titre)
            Livewire.hook('morphed', () => this.$nextTick(() => this.check()));
            // Rappel toutes les 30 s tant qu'une commande n'est pas prise ou qu'un appel attend
            setInterval(() => { if (this.$root.querySelector('[data-unclaimed], [data-alert-id^=call]')) this.beep(); }, 30000);
        },
    }"
    x-on:jb-order-taken.window="alert(@js(__('modules.order.alreadyTaken')) + ' ' + ($event.detail.name || ''))">

    {{-- En-tête --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span></span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">@lang('modules.order.boardTitle')</h2>
            <span class="text-sm text-gray-400">{{ $finished }} @lang('modules.order.boardFinished')</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-lg bg-gray-100 p-1 text-sm dark:bg-gray-800">
                <button type="button" wire:click="$set('mine', false)" class="rounded-md px-3 py-1.5 font-medium {{ $mine ? 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' : 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' }}">@lang('modules.order.boardAll')</button>
                <button type="button" wire:click="$set('mine', true)" class="rounded-md px-3 py-1.5 font-medium {{ $mine ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">@lang('modules.order.boardMine')</button>
            </div>
            <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                @click="sound = !sound; try { localStorage.setItem('jbBoardSound', sound ? '1' : '0'); } catch (e) {} if (sound) beep()">
                <span class="h-2 w-2 rounded-full" :class="sound ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                <span x-text="sound ? @js(__('modules.order.boardSoundOn')) : @js(__('modules.order.boardSoundOff'))"></span>
            </button>
        </div>
    </div>

    {{-- Appels de table --}}
    @if ($calls->isNotEmpty())
        <div class="mb-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($calls as $call)
                <div wire:key="jb-call-{{ $call->id }}" data-alert-id="call-{{ $call->id }}"
                    class="flex items-center gap-3 rounded-xl border border-rose-200 border-l-4 border-l-rose-500 bg-rose-50 px-4 py-3 dark:border-rose-900 dark:border-l-rose-500 dark:bg-rose-950/40">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-rose-800 dark:text-rose-100">
                            {{ $call->table?->table_code ?? '—' }} · @lang('modules.waiterRequest.reason_' . ($call->reason ?: 'help'))
                        </p>
                        @if ($call->message)
                            <p class="mt-0.5 break-words text-sm text-rose-700 dark:text-rose-200">« {{ $call->message }} »</p>
                        @endif
                        <p class="mt-0.5 text-xs text-rose-500 dark:text-rose-300">{{ $call->created_at?->diffForHumans(short: true) }}</p>
                    </div>
                    <button type="button" wire:click="closeCall({{ $call->id }})" wire:loading.attr="disabled"
                        class="shrink-0 rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                        @lang('modules.order.boardCallDone')
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Une page par étape : barre sur toute la largeur, avec le nombre de commandes de chacune --}}
    <nav class="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach ($jbCols as $key => [$title, $action, $btn, $dot])
            @php
                $jbCount = $columns[$key]->count() + ($key === 'bill' ? count($billByTable) : 0);
                $jbSlug = array_search($key, \App\Livewire\Dashboard\TodayOrderList::STAGES);
            @endphp
            {{-- Clic simple : Livewire change l'étape sans recharger ; le lien reste utilisable au clic du milieu (nouvel onglet) --}}
            <a href="{{ route('orders.index', $key === 'new' ? [] : ['etape' => $jbSlug]) }}"
                wire:click.prevent="goToStage('{{ $jbSlug }}')"
                @if ($key === $stage) aria-current="page" @endif
                @class([
                    'flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition',
                    'border-skin-base bg-skin-base text-white shadow-sm' => $key === $stage,
                    'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-white' => $key !== $stage,
                ])>
                <span class="flex min-w-0 items-center gap-2.5">
                    <span @class(['h-2.5 w-2.5 shrink-0 rounded-full', $dot => $key !== $stage, 'bg-white' => $key === $stage])></span>
                    <span class="truncate text-sm font-semibold">{{ $title }}</span>
                </span>
                <span @class(['text-xl font-bold tabular-nums', 'opacity-40' => ! $jbCount && $key !== $stage])>{{ $jbCount }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Hors de la page « Nouvelles » : repères invisibles pour garder le son et le titre de l'onglet --}}
    @if ($stage !== 'new')
        @foreach ($columns['new'] as $order)
            <span class="hidden" wire:key="jb-alert-{{ $order->id }}" data-alert-id="order-{{ $order->id }}" @if (! $order->waiter_id) data-unclaimed @endif></span>
        @endforeach
    @endif

    {{-- Étape affichée --}}
    @foreach ($jbCols as $key => [$title, $action, $btn, $dot, $border])
        @continue($key !== $stage)
        <section>
            @if ($key === 'new' && $columns['new']->count() > 1)
                <button type="button" wire:click="sendAllToKitchen" wire:loading.attr="disabled" wire:target="sendAllToKitchen"
                    wire:confirm="{{ __('modules.order.boardAllToKitchenConfirm', ['count' => $columns['new']->count()]) }}"
                    class="mb-4 w-full rounded-xl border border-dashed border-amber-400 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20">
                    @lang('modules.order.boardAllToKitchen', ['count' => $columns['new']->count()])
                </button>
            @endif

            <div class="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                {{-- Addition regroupée : une seule carte par table qui a plusieurs commandes servies --}}
                @if ($key === 'bill')
                    @foreach ($billByTable as $tableId => $tOrders)
                        @php
                            $tPending = $pendingByTable[$tableId] ?? 0;
                            $tTotal = $tOrders->sum(fn ($o) => $o->remainingAmount());
                        @endphp
                        <article wire:key="jb-board-table-{{ $tableId }}"
                            class="rounded-2xl border border-violet-300 bg-white p-4 shadow-sm dark:border-violet-800 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xl font-bold leading-none text-gray-900 dark:text-white">{{ $tOrders->first()->table?->table_code }}</p>
                                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">@lang('modules.order.boardTableOrders', ['count' => $tOrders->count()])</p>
                                </div>
                                <p class="shrink-0 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ currency_format($tTotal, restaurant()->currency_id) }}</p>
                            </div>

                            <div class="mt-3 divide-y divide-gray-100 rounded-xl border border-gray-100 dark:divide-gray-700 dark:border-gray-700">
                                @foreach ($tOrders as $o)
                                    <a href="{{ route('orders.live', $o->id) }}" wire:key="jb-board-table-{{ $tableId }}-{{ $o->id }}"
                                        class="block px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <p class="flex justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $o->show_formatted_order_number }} · {{ $o->date_time?->timezone(timezone())->format('H:i') }}</span>
                                            <span class="tabular-nums">{{ currency_format($o->remainingAmount(), restaurant()->currency_id) }}</span>
                                        </p>
                                        <ul class="mt-1 space-y-0.5 text-sm text-gray-700 dark:text-gray-200">
                                            @foreach ($o->items as $line)
                                                <li class="flex gap-2"><span class="w-6 shrink-0 font-semibold tabular-nums text-gray-400">{{ $jbQty($line->quantity) }}×</span><span>{{ $line->menuItem?->item_name }}@if ($line->menuItemVariation) <span class="text-gray-400">({{ $line->menuItemVariation->variation }})</span>@endif</span></li>
                                            @endforeach
                                        </ul>
                                    </a>
                                @endforeach
                            </div>

                            @if ($tPending)
                                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ trans_choice('modules.order.boardTablePending', $tPending, ['count' => $tPending]) }}</p>
                            @endif

                            <div class="mt-4 flex gap-2">
                                <button type="button" wire:click="sendTableBill({{ $tableId }})" wire:loading.attr="disabled" wire:target="sendTableBill({{ $tableId }})"
                                    class="flex-1 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    @lang('modules.order.boardSendBill')
                                </button>
                                <button type="button" wire:click="collectTable({{ $tableId }})"
                                    @if ($tPending) wire:confirm="{{ trans_choice('modules.order.boardTablePendingConfirm', $tPending, ['count' => $tPending]) }}" @endif
                                    class="flex-1 rounded-lg bg-violet-500 px-3 py-2.5 text-sm font-semibold text-white hover:bg-violet-600">
                                    @lang('modules.order.boardCollectTable')
                                </button>
                            </div>
                        </article>
                    @endforeach
                @endif

                @forelse ($columns[$key] as $order)
                    @php
                        $mins = (int) $order->date_time?->diffInMinutes($jbNow);
                        $late = $key === 'bill'
                            ? 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'
                            : ($mins >= 20 ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : ($mins >= 10 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'));
                        $pay = \App\Models\Order::PAY_METHODS[$order->pay_method] ?? null;
                    @endphp
                    <article wire:key="jb-board-{{ $order->id }}" @if ($key === 'new') data-alert-id="order-{{ $order->id }}" @endif @if ($key === 'new' && ! $order->waiter_id) data-unclaimed @endif
                        class="flex flex-col rounded-2xl border {{ $border }} bg-white p-4 shadow-sm dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('orders.live', $order->id) }}" class="min-w-0 hover:opacity-80">
                                <p class="text-xl font-bold leading-none text-gray-900 dark:text-white">
                                    {{ $order->table?->table_code ?? __('modules.order.boardNoTable') }}
                                </p>
                                <p class="mt-1.5 truncate text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->customer?->name ?: __('modules.customer.walkin') }} · {{ $order->show_formatted_order_number }}
                                </p>
                            </a>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums {{ $late }}">{{ $mins }} min</span>
                        </div>

                        @if (($pos = $sessionPos[$order->id] ?? null) && $pos[1] > 1)
                            <p class="mt-2 self-start rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-semibold text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">@lang('modules.order.boardSessionPos', ['i' => $pos[0], 'n' => $pos[1]])</p>
                        @endif

                        <div class="mt-3">@include('livewire.order.partials.status-select', ['order' => $order])</div>

                        <a href="{{ route('orders.live', $order->id) }}" class="mt-3 block border-t border-gray-100 pt-3 hover:opacity-80 dark:border-gray-700">
                            <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                @foreach ($order->items as $line)
                                    <li class="flex gap-2">
                                        <span class="w-6 shrink-0 font-semibold tabular-nums text-gray-400">{{ $jbQty($line->quantity) }}×</span>
                                        <span class="min-w-0">{{ $line->menuItem?->item_name }}@if ($line->menuItemVariation) <span class="text-gray-400">({{ $line->menuItemVariation->variation }})</span>@endif
                                            @if ($line->note) <span class="block text-xs text-amber-600 dark:text-amber-400">{{ $line->note }}</span>@endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </a>

                        @if ($order->order_note)
                            <p class="mt-3 rounded-lg border-l-4 border-amber-400 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-900/30 dark:text-amber-100">{{ $order->order_note }}</p>
                        @endif

                        @if ($pay)
                            <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs font-medium">
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-700 dark:text-gray-200">@lang($pay[1])</span>
                                @if ($order->pay_when === 'now' && $order->status !== 'paid')
                                    <span class="animate-pulse rounded-full bg-orange-100 px-2.5 py-1 font-semibold text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">@lang('modules.order.boardCollectNow')</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500 dark:bg-gray-700 dark:text-gray-300">{{ $order->pay_when === 'now' ? __('modules.order.payNowShort') : __('modules.order.payAfterShort') }}</span>
                                @endif
                                @if ($order->pay_split > 1)
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-700 dark:text-gray-200">÷{{ $order->pay_split }}</span>
                                @endif
                                @if ($order->status === 'paid')
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">@lang('modules.order.paid')</span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-auto pt-4">
                            <div class="flex items-center justify-between gap-2 text-sm">
                                @if ($order->waiter)
                                    <span class="truncate text-gray-500 dark:text-gray-400">{{ $order->waiter->name }}@if ($order->waiter_id === user()->id) ({{ __('modules.order.you') }})@endif</span>
                                @else
                                    <button type="button" wire:click="claimOrder({{ $order->id }})" class="font-semibold text-skin-base hover:underline">@lang('modules.order.takeOrder')</button>
                                @endif
                                <span class="shrink-0 text-base font-bold tabular-nums text-gray-900 dark:text-white">{{ currency_format($order->total ?? 0, restaurant()->currency_id) }}</span>
                            </div>

                            @if ($key === 'bill' && $order->bill_sent_at)
                                <p class="mt-2 text-xs text-violet-600 dark:text-violet-300">{{ __('modules.order.boardBillSentAt', ['time' => $order->bill_sent_at->timezone(timezone())->format('H:i')]) }}@if ($order->bill_sent_count > 1) · ×{{ $order->bill_sent_count }}@endif</p>
                            @endif

                            <div class="mt-3 flex gap-2">
                                @if ($key === 'bill')
                                    <button type="button" wire:click="sendBill({{ $order->id }})" wire:loading.attr="disabled" wire:target="sendBill({{ $order->id }})"
                                        class="flex-1 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                        {{ $order->bill_sent_at ? __('modules.order.boardRemindBill') : __('modules.order.boardSendBill') }}
                                    </button>
                                    @php $oPending = $order->table_id ? ($pendingByTable[$order->table_id] ?? 0) : 0; @endphp
                                    <button type="button" wire:click="collectPayment({{ $order->id }})"
                                        @if ($oPending) wire:confirm="{{ trans_choice('modules.order.boardTablePendingConfirm', $oPending, ['count' => $oPending]) }}" @endif
                                        class="flex-1 rounded-lg {{ $btn }} px-3 py-2.5 text-sm font-semibold text-white">
                                        {{ $action }}
                                    </button>
                                @else
                                    <button type="button" wire:click="advance({{ $order->id }})" wire:loading.attr="disabled" wire:target="advance({{ $order->id }})"
                                        class="flex-1 rounded-lg {{ $btn }} px-3 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                                        {{ $action }}
                                    </button>
                                    @if ($order->pay_when === 'now' && $order->status !== 'paid')
                                        <button type="button" wire:click="collectPayment({{ $order->id }})"
                                            class="rounded-lg bg-orange-500 px-3 text-sm font-semibold text-white hover:bg-orange-600">@lang('modules.order.boardCollect')</button>
                                    @endif
                                @endif
                                <a href="{{ route('orders.live', $order->id) }}" title="@lang('modules.order.details')"
                                    class="flex items-center rounded-lg border border-gray-200 px-3 text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    @if ($key !== 'bill' || empty($billByTable))
                        <div class="col-span-full rounded-2xl border border-dashed border-gray-200 py-16 text-center dark:border-gray-700">
                            <p class="text-base font-semibold text-gray-500 dark:text-gray-400">{{ $title }}</p>
                            <p class="mt-1 text-sm text-gray-400">@lang('modules.order.boardEmpty')</p>
                        </div>
                    @endif
                @endforelse
            </div>
        </section>
    @endforeach

    @include('livewire.order.partials.quick-collect')
    @include('livewire.order.partials.table-collect')
</div>
