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
@endphp

<link rel="stylesheet" href="{{ asset('css/jimbe-shop.css') }}?v={{ @filemtime(public_path('css/jimbe-shop.css')) }}">

<div class="jb-shop jb-cart">
    <div class="jb-top" style="margin-bottom: 18px;">
        <div class="jb-top__brand">
            <button type="button" class="jb-ibtn" wire:click="showMenuItems" aria-label="@lang('app.back')">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>
            <div>
                <div class="jb-top__hello">{{ $restaurant->name }}</div>
                <div class="jb-top__name" style="font-size: 22px;">@lang('modules.shop.myCart')</div>
            </div>
        </div>
    </div>

    @if ($cartQty > 0)
        {{-- Plats du panier --}}
        <div class="jb-lines">
            @foreach ($orderItemList as $key => $item)
                @php
                    $displayPrice = $this->getItemDisplayPrice($key);
                    $lineTotal = $orderItemAmount[$key];
                @endphp
                <div class="jb-line" wire:key="jb-line-{{ $key }}">
                    @if ($restaurant && !$restaurant->hide_menu_item_image_on_customer_site)
                        <div class="jb-line__img">
                            <img src="{{ $item->item_photo_url }}" alt="" loading="lazy" onerror="this.style.display='none'">
                        </div>
                    @endif
                    <div class="jb-line__body">
                        <div class="jb-line__name">
                            {{ $item->item_name }}
                            @if (isset($orderItemVariation[$key]))
                                <em>{{ $orderItemVariation[$key]->variation }}</em>
                            @endif
                        </div>
                        @if (!empty($itemModifiersSelected[$key]))
                            <div class="jb-line__mods">
                                @foreach ($itemModifiersSelected[$key] as $modifierOptionId)
                                    <span>+ {{ $this->modifierOptions[$modifierOptionId]->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="jb-line__foot">
                            <span class="jb-line__unit">{{ currency_format($displayPrice, $restaurant->currency_id) }}</span>
                            <div class="jb-step">
                                <button type="button" class="minus" wire:click="subQty('{{ $key }}')">−</button>
                                <b>{{ $orderItemQty[$key] }}</b>
                                <button type="button" wire:click="addQty('{{ $key }}')">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="jb-line__total">{{ currency_format($lineTotal, $restaurant->currency_id) }}</div>
                </div>
            @endforeach
        </div>

        {{-- Formulaire de commande --}}
        <form class="jb-panel" wire:submit.prevent="submitGuestOrder">
            <h3 class="jb-panel__title">@lang('modules.shop.yourOrder')</h3>

            <label class="jb-field">
                <span>@lang('modules.shop.yourName') <i>*</i></span>
                <input type="text" wire:model.defer="guestName" autocomplete="name" placeholder="@lang('modules.shop.yourNamePlaceholder')" maxlength="80">
                @error('guestName') <small class="jb-err">{{ $message }}</small> @enderror
            </label>

            @if (! empty($tableID) && $table)
                {{-- QR de table : la table est déjà connue, on ne la redemande pas --}}
                <div class="jb-field">
                    <span>@lang('modules.shop.yourTable')</span>
                    <div class="jb-fixed"><i></i> {{ $table->table_code }}</div>
                </div>
            @elseif ($this->singleQrMode())
                {{-- QR unique : le client écrit le numéro de sa table --}}
                <label class="jb-field">
                    <span>Numéro de votre table <i>*</i></span>
                    <input type="text" wire:model.defer="guestTableNumber" inputmode="numeric" autocomplete="off" placeholder="ex. 5" maxlength="30">
                    @error('guestTableNumber') <small class="jb-err">{{ $message }}</small> @enderror
                </label>
            @else
                <label class="jb-field">
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

            <label class="jb-field">
                <span>@lang('modules.shop.yourPhone') <em>(@lang('modules.shop.optional'))</em></span>
                <input type="tel" wire:model.defer="guestPhone" autocomplete="tel" inputmode="tel" placeholder="07 00 00 00 00" maxlength="25">
                @error('guestPhone') <small class="jb-err">{{ $message }}</small> @enderror
            </label>

            <label class="jb-field">
                <span>@lang('modules.shop.kitchenNote') <em>(@lang('modules.shop.optional'))</em></span>
                <textarea wire:model.defer="orderNote" rows="2" placeholder="@lang('modules.shop.kitchenNotePlaceholder')" maxlength="300"></textarea>
            </label>
        </form>

        {{-- Récapitulatif --}}
        <div class="jb-panel jb-sum">
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
            <div class="jb-sum__total"><span>@lang('modules.order.total')</span><b>{{ currency_format($total, $restaurant->currency_id) }}</b></div>
        </div>

        {{-- Paiement : le client choisit comment et quand il paie --}}
        @php $jbOnline = (bool) ($paymentGateway?->kadev_status && $paymentGateway?->kadev_public_key); @endphp
        <div class="jb-panel jb-paychoice" data-total="{{ (float) $total }}"
            x-data="{
                when: $wire.entangle('payWhen'),
                method: $wire.entangle('payMethod'),
                split: $wire.entangle('paySplit'),
                online: @js($jbOnline),
                get payOnline() { return this.when === 'now' && this.online && this.method !== 'cash' && Number(this.split) <= 1; },
                share() {
                    const n = Math.ceil(Number(this.$root.dataset.total || 0) / Math.max(1, Number(this.split)));
                    return @js(currency_format(0, $restaurant->currency_id)).replace('0', n.toLocaleString('fr-FR'));
                },
            }">
            <h3 class="jb-panel__title">@lang('modules.shop.payTitle')</h3>

            <p class="jb-bill__label" style="margin-top:0">@lang('modules.shop.payHow')</p>
            <div class="jb-paym">
                @foreach (\App\Models\Order::PAY_METHODS as $jbKey => [$jbEmoji, $jbLabel])
                    <button type="button" :class="method === '{{ $jbKey }}' && 'is-on'" @click="method = '{{ $jbKey }}'">
                        @lang($jbLabel)
                    </button>
                @endforeach
            </div>

            <p class="jb-bill__label">@lang('modules.shop.payWhenLabel')</p>
            <div class="jb-seg" style="margin-top:0">
                <button type="button" :class="when === 'after' && 'is-on'" @click="when = 'after'">@lang('modules.shop.payAfter')</button>
                <button type="button" :class="when === 'now' && 'is-on'" @click="when = 'now'">@lang('modules.shop.payNowLabel')</button>
            </div>

            <label class="jb-paysplit">
                <input type="checkbox" :checked="split > 1" @change="split = $event.target.checked ? 2 : 1">
                <span>@lang('modules.shop.paySplitLabel')</span>
            </label>
            <div class="jb-split" x-show="split > 1" x-cloak x-transition style="margin-top:8px">
                <div class="jb-split__ctr">
                    <button type="button" @click="split = Math.max(2, Number(split) - 1)" :disabled="split <= 2">−</button>
                    <span><b x-text="split"></b> @lang('modules.shop.billPeople')</span>
                    <button type="button" @click="split = Math.min(20, Number(split) + 1)">+</button>
                </div>
                <p class="jb-split__each"><b x-text="share()"></b> @lang('modules.shop.billPerPerson')</p>
            </div>

            <p class="jb-sum__hint" x-show="when === 'after'">@lang('modules.shop.payHintAfter')</p>
            <p class="jb-sum__hint" x-show="when === 'now' && payOnline" x-cloak>@lang('modules.shop.payHintOnline')</p>
            <p class="jb-sum__hint" x-show="when === 'now' && !payOnline" x-cloak>@lang('modules.shop.payHintWaiter')</p>

            {{-- Barre « Commander » (dans le même bloc pour suivre le choix du client) --}}
            <div class="jb-cta">
                @if (!$isRestaurantOpenForOrders)
                    <div class="jb-closed">{{ $restaurantClosedMessage }}</div>
                @else
                    <button type="button" class="jb-cta__btn" x-show="!payOnline" wire:click="submitGuestOrder" wire:loading.attr="disabled" wire:target="submitGuestOrder">
                        <span wire:loading.remove wire:target="submitGuestOrder">@lang('modules.shop.order') · {{ currency_format($total, $restaurant->currency_id) }}</span>
                        <span wire:loading wire:target="submitGuestOrder">@lang('modules.shop.sending')…</span>
                    </button>
                    <button type="button" class="jb-cta__btn" x-show="payOnline" x-cloak wire:click="submitGuestOrder(true)" wire:loading.attr="disabled" wire:target="submitGuestOrder">
                        <span wire:loading.remove wire:target="submitGuestOrder">@lang('modules.shop.payAndOrder') · {{ currency_format($total, $restaurant->currency_id) }}</span>
                        <span wire:loading wire:target="submitGuestOrder">@lang('modules.shop.sending')…</span>
                    </button>
                @endif
            </div>
        </div>

    @else
        <div class="jb-emptycart">
            <h3>@lang('messages.cartEmpty')</h3>
            <button type="button" class="jb-hero__cta" wire:click="showMenuItems">@lang('modules.shop.backToMenu')</button>
        </div>
    @endif
</div>
