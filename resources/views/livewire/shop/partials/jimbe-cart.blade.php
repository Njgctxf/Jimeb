{{-- Jimbe : page panier + formulaire de commande --}}
@php
    $jbAreas = \App\Models\Area::where('branch_id', $shopBranch->id)
        ->with(['tables' => fn ($q) => $q->where('status', 'active')])
        ->get()
        ->filter(fn ($a) => $a->tables->count() > 0);

    $jbTotalTax = 0;
    if ($taxMode === 'order') {
        foreach ($taxes ?? [] as $t) {
            $jbTotalTax += ($t->tax_percent / 100) * ($taxBase ?? 0);
        }
    } else {
        foreach ($orderItemTaxDetails ?? [] as $d) {
            $jbTotalTax += collect($d['tax_amount'] ?? 0)->sum();
        }
    }

    $jbOnline = (bool) ($paymentGateway?->kadev_status && $paymentGateway?->kadev_public_key);
    $jbPayIcons = [
        'cash' => '<path d="M3 7.5h18v9H3z"/><circle cx="12" cy="12" r="2.4"/><path d="M6 10v4M18 10v4"/>',
        'card' => '<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3 10h18M7 15h4"/>',
        'momo' => '<rect x="7" y="2.8" width="10" height="18.4" rx="2.5"/><path d="M11 17.6h2"/>',
    ];
    $jbHasTable = ! empty($tableID) && $table;
@endphp

<link rel="stylesheet" href="{{ asset('css/jimbe-shop.css') }}?v={{ @filemtime(public_path('css/jimbe-shop.css')) }}">
@include('livewire.shop.partials.jimbe-foodslice-head')

<div class="jb-shop jb-cart jb-c2" data-total="{{ (float) $total }}"
    x-data="{
        when: $wire.entangle('payWhen'),
        method: $wire.entangle('payMethod'),
        split: $wire.entangle('paySplit'),
        online: @js($jbOnline),
        note: false,
        get payOnline() { return this.when === 'now' && this.online && this.method !== 'cash' && Number(this.split) <= 1; },
        share() {
            const n = Math.ceil(Number(this.$root.dataset.total || 0) / Math.max(1, Number(this.split)));
            return @js(currency_format(0, $restaurant->currency_id)).replace('0', n.toLocaleString('fr-FR'));
        },
    }">
    {{-- En-tête --}}
    <div class="jb-c2-head">
        <button type="button" class="jb-ibtn" wire:click="showMenuItems" aria-label="@lang('app.back')">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </button>
        <div class="jb-c2-head__txt">
            <small>{{ $restaurant->name }}</small>
            <h1>@lang('modules.shop.myCart')</h1>
        </div>
        @if ($cartQty > 0)
            <span class="jb-c2-head__count">{{ trans_choice('modules.shop.cartCount', $cartQty, ['count' => $cartQty]) }}</span>
        @endif
    </div>

    @if ($cartQty > 0)
        {{-- Plats --}}
        <section class="jb-c2-items">
            @foreach ($orderItemList as $key => $item)
                @php $displayPrice = $this->getItemDisplayPrice($key); @endphp
                <article class="jb-c2-item" wire:key="jb-line-{{ $key }}">
                    @if ($restaurant && !$restaurant->hide_menu_item_image_on_customer_site)
                        <div class="jb-c2-item__img">
                            <span>{{ mb_strtoupper(mb_substr($item->item_name, 0, 1)) }}</span>
                            <img src="{{ $item->item_photo_url }}" alt="" loading="lazy" onerror="this.remove()">
                        </div>
                    @endif
                    <div class="jb-c2-item__body">
                        <h3>{{ $item->item_name }}</h3>
                        @if (isset($orderItemVariation[$key]))
                            <p class="jb-c2-item__var">{{ $orderItemVariation[$key]->variation }}</p>
                        @endif
                        @if (!empty($itemModifiersSelected[$key]))
                            <p class="jb-c2-item__mods">
                                @foreach ($itemModifiersSelected[$key] as $modifierOptionId)
                                    <span>{{ $this->modifierOptions[$modifierOptionId]->name }}</span>
                                @endforeach
                            </p>
                        @endif
                        @if ($orderItemQty[$key] > 1)
                            <small class="jb-c2-item__unit">{{ $orderItemQty[$key] }} × {{ currency_format($displayPrice, $restaurant->currency_id) }}</small>
                        @endif
                        <div class="jb-c2-item__foot">
                            <b class="jb-c2-item__price">{{ currency_format($orderItemAmount[$key], $restaurant->currency_id) }}</b>
                            <div class="jb-c2-qty">
                                <button type="button" wire:click="subQty('{{ $key }}')" aria-label="-1">
                                    @if ($orderItemQty[$key] <= 1)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M10 7V4.5h4V7M7 7l1 13h8l1-13"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 12h12"/></svg>
                                    @endif
                                </button>
                                <b>{{ $orderItemQty[$key] }}</b>
                                <button type="button" class="is-add" wire:click="addQty('{{ $key }}')" aria-label="+1">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 6v12M6 12h12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
            <button type="button" class="jb-c2-more" wire:click="showMenuItems">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 6v12M6 12h12"/></svg>
                @lang('modules.shop.cartAddMore')
            </button>
        </section>

        {{-- Vos informations --}}
        <form class="jb-c2-card" wire:submit.prevent="submitGuestOrder">
            <h2 class="jb-c2-title">@lang('modules.shop.cartYourInfo')</h2>

            @if ($jbHasTable)
                {{-- QR de table : la table est déjà connue, on ne la redemande pas --}}
                <div class="jb-c2-table">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18M5 9v10M19 9v10M8 9V6h8v3"/></svg>
                    <span>@lang('modules.shop.cartTableAt')</span>
                    <b>{{ $table->table_code }}</b>
                </div>
            @endif

            <label class="jb-c2-field">
                <span>@lang('modules.shop.yourName') <i>*</i></span>
                <input type="text" wire:model.defer="guestName" autocomplete="name" placeholder="@lang('modules.shop.yourNamePlaceholder')" maxlength="80">
                @error('guestName') <small class="jb-err">{{ $message }}</small> @enderror
            </label>

            @if (! $jbHasTable)
                @if ($this->singleQrMode())
                    {{-- QR unique : le client écrit le numéro de sa table --}}
                    <label class="jb-c2-field">
                        <span>Numéro de votre table <i>*</i></span>
                        <input type="text" wire:model.defer="guestTableNumber" inputmode="numeric" autocomplete="off" placeholder="ex. 5" maxlength="30">
                        @error('guestTableNumber') <small class="jb-err">{{ $message }}</small> @enderror
                    </label>
                @else
                    <label class="jb-c2-field">
                        <span>@lang('modules.shop.yourTable') <i>*</i></span>
                        <select wire:model.defer="guestTable">
                            <option value="">@lang('modules.shop.chooseTable')</option>
                            @foreach ($jbAreas as $area)
                                <optgroup label="{{ $area->area_name }}">
                                    @foreach ($area->tables as $t)
                                        <option value="{{ $t->hash }}">{{ $t->table_code }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('guestTable') <small class="jb-err">{{ $message }}</small> @enderror
                    </label>
                @endif
            @endif

            <label class="jb-c2-field">
                <span>@lang('modules.shop.yourPhone') <em>@lang('modules.shop.optional')</em></span>
                <input type="tel" wire:model.defer="guestPhone" autocomplete="tel" inputmode="tel" placeholder="07 00 00 00 00" maxlength="25">
                @error('guestPhone') <small class="jb-err">{{ $message }}</small> @enderror
            </label>

            <button type="button" class="jb-c2-notebtn" x-show="!note" @click="note = true; $nextTick(() => $refs.note.focus())">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9l-4-4L4 16z"/></svg>
                @lang('modules.shop.cartAddNote')
            </button>
            <label class="jb-c2-field" x-show="note" x-cloak x-transition>
                <span>@lang('modules.shop.kitchenNote') <em>@lang('modules.shop.optional')</em></span>
                <textarea x-ref="note" wire:model.defer="orderNote" rows="2" placeholder="@lang('modules.shop.kitchenNotePlaceholder')" maxlength="300"></textarea>
            </label>
        </form>

        {{-- Paiement : le client choisit comment et quand il paie --}}
        <section class="jb-c2-card">
            <h2 class="jb-c2-title">@lang('modules.shop.payTitle')</h2>
            <p class="jb-c2-label">@lang('modules.shop.payHow')</p>
            <div class="jb-c2-pay">
                @foreach (\App\Models\Order::PAY_METHODS as $jbKey => [$jbUnused, $jbLabel])
                    <button type="button" :class="method === '{{ $jbKey }}' && 'is-on'" @click="method = '{{ $jbKey }}'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $jbPayIcons[$jbKey] ?? '' !!}</svg>
                        <span>@lang($jbLabel)</span>
                    </button>
                @endforeach
            </div>

            <p class="jb-c2-label">@lang('modules.shop.payWhenLabel')</p>
            <div class="jb-c2-seg" :class="when === 'now' && 'is-right'">
                <i></i>
                <button type="button" :class="when === 'after' && 'is-on'" @click="when = 'after'">@lang('modules.shop.payAfter')</button>
                <button type="button" :class="when === 'now' && 'is-on'" @click="when = 'now'">@lang('modules.shop.payNowLabel')</button>
            </div>

            <label class="jb-c2-switch">
                <span>@lang('modules.shop.paySplitLabel')</span>
                <input type="checkbox" :checked="split > 1" @change="split = $event.target.checked ? 2 : 1">
                <i></i>
            </label>
            <div class="jb-c2-split" x-show="split > 1" x-cloak x-transition>
                <div class="jb-c2-qty">
                    <button type="button" @click="split = Math.max(2, Number(split) - 1)" :disabled="split <= 2" aria-label="-1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 12h12"/></svg></button>
                    <b x-text="split"></b>
                    <button type="button" class="is-add" @click="split = Math.min(20, Number(split) + 1)" aria-label="+1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 6v12M6 12h12"/></svg></button>
                </div>
                <p><b x-text="share()"></b> @lang('modules.shop.billPerPerson')</p>
            </div>

            <p class="jb-c2-hint" x-show="when === 'after'">@lang('modules.shop.payHintAfter')</p>
            <p class="jb-c2-hint" x-show="when === 'now' && payOnline" x-cloak>@lang('modules.shop.payHintOnline')</p>
            <p class="jb-c2-hint" x-show="when === 'now' && !payOnline" x-cloak>@lang('modules.shop.payHintWaiter')</p>
        </section>

        {{-- Récapitulatif --}}
        <section class="jb-c2-card jb-c2-sum">
            <h2 class="jb-c2-title">@lang('modules.shop.cartSummary')</h2>
            <div><span>@lang('modules.order.subTotal')</span><b>{{ currency_format($subTotal, $restaurant->currency_id) }}</b></div>
            @if (count($orderItemList) > 0 && $this->applicableExtraCharges && count($this->applicableExtraCharges) > 0)
                @foreach ($this->applicableExtraCharges as $charge)
                    <div wire:key="jb-charge-{{ $loop->index }}">
                        <span>{{ $charge->charge_name }}@if ($charge->charge_type == 'percent') ({{ $charge->charge_value }}%)@endif</span>
                        <b>{{ currency_format($charge->getAmount($subTotal), $restaurant->currency_id) }}</b>
                    </div>
                @endforeach
            @endif
            @if ($jbTotalTax > 0)
                <div><span>@lang('modules.order.totalTax')</span><b>{{ currency_format($jbTotalTax, $restaurant->currency_id) }}</b></div>
            @endif
            <div class="jb-c2-sum__total"><span>@lang('modules.order.total')</span><b>{{ currency_format($total, $restaurant->currency_id) }}</b></div>
        </section>

        {{-- Envoi en cours : écran animé jusqu'à l'ouverture du suivi de commande.
             Téléporté à la racine de la page pour couvrir tout l'écran (les panneaux le recadreraient). --}}
        @teleport('body')
        <div class="jb-sending" wire:loading.flex wire:target="submitGuestOrder" role="status" aria-live="polite">
            <div class="jb-sending__stage" aria-hidden="true">
                <span class="jb-sending__steam s1"></span><span class="jb-sending__steam s2"></span><span class="jb-sending__steam s3"></span>
                <svg class="jb-sending__cloche" viewBox="0 0 120 80">
                    <path class="lid" d="M14 62a46 46 0 0 1 92 0Z" />
                    <circle class="knob" cx="60" cy="13" r="6" />
                    <rect class="tray" x="4" y="62" width="112" height="8" rx="4" />
                </svg>
            </div>
            <p class="jb-sending__title">@lang('modules.shop.sendingOrder')</p>
            <p class="jb-sending__hint">@lang('modules.shop.sendingHint')</p>
            <div class="jb-sending__bar"><i></i></div>
        </div>
        @endteleport

        {{-- Barre « Commander » collée en bas de l'écran --}}
        <div class="jb-c2-bar">
            @if (!$isRestaurantOpenForOrders)
                <div class="jb-closed">{{ $restaurantClosedMessage }}</div>
            @else
                <button type="button" class="jb-c2-bar__btn" x-show="!payOnline" wire:click="submitGuestOrder" wire:loading.attr="disabled" wire:target="submitGuestOrder">
                    <span wire:loading.remove wire:target="submitGuestOrder">@lang('modules.shop.order')</span>
                    <span wire:loading wire:target="submitGuestOrder">@lang('modules.shop.sending')…</span>
                    <b>{{ currency_format($total, $restaurant->currency_id) }}</b>
                </button>
                <button type="button" class="jb-c2-bar__btn" x-show="payOnline" x-cloak wire:click="submitGuestOrder(true)" wire:loading.attr="disabled" wire:target="submitGuestOrder">
                    <span wire:loading.remove wire:target="submitGuestOrder">@lang('modules.shop.payAndOrder')</span>
                    <span wire:loading wire:target="submitGuestOrder">@lang('modules.shop.sending')…</span>
                    <b>{{ currency_format($total, $restaurant->currency_id) }}</b>
                </button>
            @endif
        </div>

    @else
        <div class="jb-emptycart">
            <h3>@lang('messages.cartEmpty')</h3>
            <button type="button" class="jb-hero__cta" wire:click="showMenuItems">@lang('modules.shop.backToMenu')</button>
        </div>
    @endif
</div>
