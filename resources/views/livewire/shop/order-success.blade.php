{{-- Jimbe : confirmation, suivi de commande (étape 5) et addition (étape 6) --}}
@php
    $jbStatus = $order->order_status->value;
    $jbCancelled = $jbStatus === 'cancelled';
    $jbStep = match (true) {
        in_array($jbStatus, ['served', 'completed', 'delivered'], true) => 4,
        $jbStatus === 'food_ready' => 3,
        $jbStatus === 'preparing' => 2,
        default => 1,
    };
    // Icônes dessinées des étapes (contours 24×24) : reçue, en cuisine, prête, servie
    $jbIcon = fn (string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
    $jbSteps = [
        1 => [$jbIcon('<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/>'), __('modules.shop.stepReceived')],
        2 => [$jbIcon('<path d="M12 3c1 3.5 5 5.5 5 10a5 5 0 0 1-10 0c0-2.2 1.2-3.8 2.5-5 .2 1.6 1 2.6 2 3 0-3 0-5.5.5-8Z"/>'), __('modules.shop.stepPreparing')],
        3 => [$jbIcon('<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15L6 16Z"/><path d="M10 20a2 2 0 0 0 4 0"/><path d="M12 3v2"/>'), __('modules.shop.stepReady')],
        4 => [$jbIcon('<path d="M3 15h18"/><path d="M5 15a7 7 0 0 1 14 0"/><path d="M12 6v2"/><path d="M4 19h16"/>'), __('modules.shop.stepServed')],
    ];
    $jbCheck = $jbIcon('<path d="m5 12.5 4.5 4.5L19 7.5"/>');
    $jbHints = [
        1 => __('modules.shop.trackReceivedHint'),
        2 => __('modules.shop.trackPreparingHint'),
        3 => __('modules.shop.trackReadyHint'),
        4 => __('modules.shop.trackServedHint'),
    ];
    $jbProgress = [1 => 12, 2 => 45, 3 => 78, 4 => 100][$jbStep];
    $jbSentAt = $order->created_at?->timezone(timezone())->format('H\hi');
    $jbMaxPrep = $order->items->max(fn ($i) => $i->menuItem?->preparation_time);
    $jbMenuUrl = route('shop_restaurant', ['hash' => $restaurant->hash, 'new_order' => 1, 'branch' => $shopBranch->id ?? '']);
    $jbMoreUrl = fn (?string $cat = null) => $jbMenuUrl . '&jb=menu' . ($cat ? '&cat=' . $cat : '');
    $jbCur = $restaurant->currency_id;

    // Addition
    $jbGateway = $restaurant->paymentGateways;
    $jbCanPayOnline = (bool) ($jbGateway?->kadev_status && $jbGateway?->kadev_public_key);
    $jbPaid = round((float) $order->amount_paid, 2);
    $jbRemaining = $order->remainingAmount();
    $jbFullyPaid = $order->isFullyPaid();
    $jbTipEditable = $restaurant->enable_tip_shop && $jbPaid <= 0;
    $jbTip = round((float) $order->tip_amount);
    $jbBase = (float) $order->sub_total;
    $jbTipOptions = collect([0, 5, 10, 15])->map(fn ($pct) => ['pct' => $pct, 'amount' => (int) (ceil($jbBase * $pct / 100 / 5) * 5)])->unique('amount');
    $jbShare = $this->shareAmount();
    $jbPayChoice = \App\Models\Order::PAY_METHODS[$order->pay_method] ?? null;
    $jbBillSent = $order->bill_sent_at && ! $jbFullyPaid && ! $jbCancelled;
@endphp

<div class="jb-shop jb-done" wire:poll.6s="refreshOrderSuccess">
    <link rel="stylesheet" href="{{ asset('css/jimbe-shop.css') }}?v={{ @filemtime(public_path('css/jimbe-shop.css')) }}">
    @include('livewire.shop.partials.jimbe-foodslice-head')
    <div class="jb-done__hero">
        {{-- Coche (ou croix) qui se dessine à l'arrivée --}}
        <div class="jb-okmark {{ $jbCancelled ? 'is-cancel' : '' }}" aria-hidden="true">
            <svg viewBox="0 0 52 52">
                <circle class="jb-okmark__c" cx="26" cy="26" r="24" />
                @if ($jbCancelled)
                    <path class="jb-okmark__p" d="M17 17l18 18M35 17 17 35" />
                @else
                    <path class="jb-okmark__p" d="M14.5 27.5 22 35l15.5-17" />
                @endif
            </svg>
        </div>
        <h1>{{ $jbCancelled ? __('modules.shop.orderCancelled') : __('modules.shop.orderSent') }}</h1>
        <p>
            @if ($jbCancelled)
                {{ __('modules.shop.orderCancelledSub') }}
            @else
                {{ __('modules.shop.sentAt', ['time' => $jbSentAt]) }}
            @endif
        </p>
        <div class="jb-done__meta">
            <span>{{ $order->show_formatted_order_number }}</span>
            @if ($order->table)
                <span>{{ \Illuminate\Support\Str::startsWith(mb_strtolower($order->table->table_code), mb_strtolower(__('modules.shop.tableWord'))) ? $order->table->table_code : __('modules.shop.tableWord') . ' ' . $order->table->table_code }}</span>
            @endif
            @if ($jbMaxPrep && $jbStep < 3)
                <span>~{{ prep_time_label($jbMaxPrep) }}</span>
            @endif
        </div>
    </div>

    @if ($jbBillSent)
        {{-- Le serveur a envoyé l'addition : alerte + son + vibration à chaque envoi --}}
        <div class="jb-billalert" wire:key="jb-billalert-{{ $order->bill_sent_count }}"
            x-data x-init="
                const k = 'jbBill:{{ $order->id }}', n = {{ (int) $order->bill_sent_count }};
                let seen = 0; try { seen = Number(sessionStorage.getItem(k) || 0); sessionStorage.setItem(k, n); } catch (e) {}
                if (n > seen) {
                    try { navigator.vibrate && navigator.vibrate([200, 100, 200]); } catch (e) {}
                    try {
                        const c = new (window.AudioContext || window.webkitAudioContext)();
                        [660, 880, 1100].forEach((f, i) => { const o = c.createOscillator(), g = c.createGain(); o.frequency.value = f; o.connect(g); g.connect(c.destination); g.gain.setValueAtTime(.2, c.currentTime + i * .2); g.gain.exponentialRampToValueAtTime(.001, c.currentTime + i * .2 + .18); o.start(c.currentTime + i * .2); o.stop(c.currentTime + i * .2 + .18); });
                    } catch (e) {}
                    $el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }">
            <b>{!! $jbIcon('<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>') !!}</b>
            <div>
                <h3>@lang('modules.shop.billSentTitle')</h3>
                <p>{{ $order->bill_sent_count > 1 ? __('modules.shop.billReminderSub') : __('modules.shop.billSentSub') }}</p>
                <strong>{{ currency_format($jbRemaining, $jbCur) }}</strong>
            </div>
        </div>
    @endif

    @unless ($jbCancelled)
        {{-- Étape 5 : suivi en direct, animé (rafraîchi toutes les 6 s) --}}
        <div class="jb-panel jb-trk jb-trk--s{{ $jbStep }}" wire:key="jb-trk">
            <div class="jb-trk__now">
                <div class="jb-trk__icon" aria-hidden="true">
                    {!! $jbSteps[$jbStep][0] !!}
                    @if ($jbStep < 4) <i class="jb-trk__ring"></i> @endif
                </div>
                <div class="min-w-0">
                    <p class="jb-trk__live">
                        @if ($jbStep < 4) <i></i> @lang('modules.shop.trackLive') @else @lang('modules.shop.trackDone') @endif
                    </p>
                    <h3 class="jb-panel__title jb-trk__title" wire:key="jb-trk-title-{{ $jbStep }}">{{ $jbSteps[$jbStep][1] }}</h3>
                    <p class="jb-track__hint">{{ $jbHints[$jbStep] }}</p>
                </div>
            </div>

            <ol class="jb-trk__steps" style="--jb-trk: {{ round(($jbStep - 1) / 3, 3) }}">
                @foreach ($jbSteps as $n => [$icon, $label])
                    <li class="{{ $n < $jbStep ? 'is-done' : '' }} {{ $n === $jbStep ? 'is-now' : '' }}" style="--i: {{ $n }}">
                        <span class="jb-trk__dot">{!! $n < $jbStep ? $jbCheck : $icon !!}</span>
                        <b>{{ $label }}</b>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Recommander --}}
        <div class="jb-panel">
            <h3 class="jb-panel__title">@lang('modules.shop.moreTitle')</h3>
            <div class="jb-more">
                <a href="{{ $jbMoreUrl('boisson') }}"><b>{!! $jbIcon('<path d="M7 4h10l-1.5 16h-7L7 4Z"/><path d="M7.5 9h9"/><path d="M13 4l2-2"/>') !!}</b>@lang('modules.shop.moreDrink')</a>
                <a href="{{ $jbMoreUrl('dessert') }}"><b>{!! $jbIcon('<path d="M4 14h16v6H4z"/><path d="M6 14v-3a6 6 0 0 1 12 0v3"/><path d="M12 5V3"/>') !!}</b>@lang('modules.shop.moreDessert')</a>
                <a href="{{ $jbMoreUrl() }}"><b>{!! $jbIcon('<path d="M12 5v14M5 12h14"/>') !!}</b>@lang('modules.shop.moreOther')</a>
            </div>
        </div>
    @endunless

    <div class="jb-panel">
        <h3 class="jb-panel__title">@lang('modules.shop.yourOrder')</h3>
        <div class="jb-recap">
            @foreach ($order->items as $item)
                <div class="jb-recap__row" wire:key="jb-recap-{{ $item->id }}">
                    <div>
                        <b>{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }} × {{ $item->menuItem?->item_name }}</b>
                        @if ($item->menuItemVariation)
                            <em>{{ $item->menuItemVariation->variation }}</em>
                        @endif
                        @foreach ($item->modifierOptions as $mod)
                            <small>+ {{ $mod->name }}</small>
                        @endforeach
                    </div>
                    <span>{{ currency_format($item->amount, $jbCur) }}</span>
                </div>
            @endforeach
        </div>
        @if ($order->order_note)
            <p class="jb-recap__note">{{ $order->order_note }}</p>
        @endif
    </div>

    @unless ($jbCancelled)
        {{-- Étape 6 : l'addition --}}
        <div class="jb-panel jb-bill" x-data="{ split: {{ $splitCount > 1 ? 'true' : 'false' }} }">
            <h3 class="jb-panel__title">@lang('modules.shop.billTitle')</h3>
            @if ($jbPayChoice)
                <div class="jb-choice">
                    <span>@lang($jbPayChoice[1])</span>
                    <span>{{ $order->pay_when === 'now' ? __('modules.shop.payNowLabel') : __('modules.shop.payAfter') }}</span>
                    @if ($order->pay_split > 1)
                        <span>÷ {{ $order->pay_split }}</span>
                    @endif
                </div>
            @endif

            <div class="jb-sum">
                <div><span>@lang('modules.order.subTotal')</span><b>{{ currency_format($order->sub_total, $jbCur) }}</b></div>
                @if ($jbTip > 0)
                    <div><span>@lang('modules.shop.billTip')</span><b>{{ currency_format($jbTip, $jbCur) }}</b></div>
                @endif
                @if ((float) $order->total - (float) $order->sub_total - $jbTip > 0.5)
                    <div><span>@lang('modules.order.totalTax')</span><b>{{ currency_format((float) $order->total - (float) $order->sub_total - $jbTip, $jbCur) }}</b></div>
                @endif
                <div class="jb-sum__total"><span>@lang('modules.order.total')</span><b>{{ currency_format($order->total, $jbCur) }}</b></div>
                @if ($jbPaid > 0)
                    <div><span>@lang('modules.shop.billPaid')</span><b class="jb-bill__ok">− {{ currency_format($jbPaid, $jbCur) }}</b></div>
                    @unless ($jbFullyPaid)
                        <div class="jb-sum__total"><span>@lang('modules.shop.billRemaining')</span><b>{{ currency_format($jbRemaining, $jbCur) }}</b></div>
                    @endunless
                @endif
            </div>

            @if ($jbFullyPaid)
                <div class="jb-bill__done">{!! $jbCheck !!} @lang('modules.shop.billAllPaid')</div>
            @else
                @if ($jbTipEditable)
                    <p class="jb-bill__label">@lang('modules.shop.billTip')</p>
                    <div class="jb-chips">
                        @foreach ($jbTipOptions as $opt)
                            <button type="button" wire:key="jb-tip-{{ $opt['pct'] }}" wire:click="setTip({{ $opt['amount'] }})"
                                class="{{ $jbTip == $opt['amount'] ? 'is-on' : '' }}">
                                @if ($opt['pct'] === 0)
                                    @lang('modules.shop.billNoTip')
                                @else
                                    {{ $opt['pct'] }}%<small>{{ currency_format($opt['amount'], $jbCur) }}</small>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="jb-seg">
                    <button type="button" :class="!split && 'is-on'" @click="split = false; $wire.setSplit(1)">@lang('modules.shop.billPayAll')</button>
                    <button type="button" :class="split && 'is-on'" @click="split = true; $wire.setSplit({{ max(2, $splitCount) }})">@lang('modules.shop.billSplit')</button>
                </div>

                <div class="jb-split" x-show="split" x-cloak x-transition>
                    <div class="jb-split__ctr">
                        <button type="button" wire:click="bumpSplit(-1)" @disabled($splitCount <= 2)>−</button>
                        <span><b>{{ max(2, $splitCount) }}</b> @lang('modules.shop.billPeople')</span>
                        <button type="button" wire:click="bumpSplit(1)">+</button>
                    </div>
                    <p class="jb-split__each"><b>{{ currency_format($jbShare, $jbCur) }}</b> @lang('modules.shop.billPerPerson')</p>
                    <p class="jb-sum__hint">@lang('modules.shop.billShareHint')</p>
                </div>

                <div class="jb-pay">
                    <button type="button" class="jb-pay__opt" wire:click="callWaiterForBill" wire:loading.attr="disabled" wire:target="callWaiterForBill">
                        <b>{!! $jbSteps[3][0] !!}</b>
                        <span>@lang('modules.shop.billCallWaiter')<small>@lang('modules.shop.billCallWaiterSub')</small></span>
                    </button>
                    @if ($jbCanPayOnline)
                        <button type="button" class="jb-pay__opt is-main" wire:click="payOnline" wire:loading.attr="disabled" wire:target="payOnline">
                            <b>{!! $jbIcon('<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>') !!}</b>
                            <span>@lang('modules.shop.billPayOnline') · {{ currency_format($jbShare, $jbCur) }}<small>@lang('modules.shop.billPayOnlineSub')</small></span>
                        </button>
                    @endif
                </div>
            @endif

            @if ($billMessage)
                <div class="jb-bill__msg" wire:key="jb-bill-msg-{{ md5($billMessage) }}">{{ $billMessage }}</div>
            @endif
        </div>
    @endunless

    <a href="{{ $jbMenuUrl }}" class="jb-cta__btn jb-done__btn" style="display:block;text-align:center;text-decoration:none;">
        @lang('modules.shop.orderMore')
    </a>

    @if ($jbCanPayOnline)
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('kadev-checkout', (event) => {
                    const d = Array.isArray(event) ? event[0] : event;
                    const run = () => KadevPay.checkout({
                        public_key: d.publicKey,
                        amount: d.amount,
                        email: d.email,
                        name: d.name || undefined,
                        phone: d.phone || undefined,
                        method: 'momo',
                        metadata: { kadev_payment_id: d.paymentId, order_uuid: d.orderUuid, source: 'bill' },
                        onSuccess: (response) => {
                            const ref = (response && (response.reference || (response.data && response.data.reference))) || '';
                            @this.call('confirmKadevPayment', d.paymentId, ref);
                        },
                        onClose: () => {},
                    });
                    if (window.KadevPay) { run(); return; }
                    const sc = document.createElement('script');
                    sc.src = d.sdk;
                    sc.onload = run;
                    document.head.appendChild(sc);
                });
            });
        </script>
    @endif
</div>
