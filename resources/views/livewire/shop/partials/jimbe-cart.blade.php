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
            <p class="jb-sum__hint">@lang('modules.shop.payAtEnd')</p>
        </div>

        {{-- Barre « Commander » --}}
        <div class="jb-cta">
            @if (!$isRestaurantOpenForOrders)
                <div class="jb-closed">{{ $restaurantClosedMessage }}</div>
            @else
                <button type="button" class="jb-cta__btn" wire:click="submitGuestOrder" wire:loading.attr="disabled" wire:target="submitGuestOrder">
                    <span wire:loading.remove wire:target="submitGuestOrder">@lang('modules.shop.order') · {{ currency_format($total, $restaurant->currency_id) }}</span>
                    <span wire:loading wire:target="submitGuestOrder">@lang('modules.shop.sending')…</span>
                </button>
                @if ($paymentGateway?->kadev_status)
                    <button type="button" class="jb-cta__btn" style="margin-top:8px" wire:click="submitGuestOrder(true)" wire:loading.attr="disabled" wire:target="submitGuestOrder">
                        Payer maintenant · Mobile Money / Carte
                    </button>
                @endif
            @endif
        </div>
    @else
        <div class="jb-emptycart">
            <div>🛒</div>
            <h3>@lang('messages.cartEmpty')</h3>
            <button type="button" class="jb-hero__cta" wire:click="showMenuItems">@lang('modules.shop.backToMenu') <span>→</span></button>
        </div>
    @endif
</div>
