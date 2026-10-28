{{-- Jimbe : options d'un plat (suppléments, sauces…) sur la page client. Même logique que livewire.pos.item-modifiers. --}}
@php
    $jbCurrency = $selectedModifierItem->branch->restaurant->currency_id;
    $jbPhoto = (restaurant() && !restaurant()->hide_menu_item_image_on_customer_site
        && $selectedModifierItem->item_photo_url && !str_contains($selectedModifierItem->item_photo_url, 'food.svg'))
        ? $selectedModifierItem->item_photo_url : null;
@endphp
<div class="jb-opt">
    {{-- Le plat --}}
    <div class="jb-opt__head">
        <div class="jb-opt__img">
            @if ($jbPhoto)
                <img src="{{ $jbPhoto }}" alt="" onerror="this.remove()">
            @endif
            <span>{{ mb_strtoupper(mb_substr(trim($selectedModifierItem->item_name), 0, 1)) }}</span>
        </div>
        <div class="min-w-0">
            <h3 class="jb-opt__name">
                {{ $selectedModifierItem->item_name }}
                @if ($selectedVariationName) <small>· {{ $selectedVariationName }}</small> @endif
            </h3>
            @if ($selectedModifierItem->description)
                <p class="jb-opt__desc">{{ $selectedModifierItem->description }}</p>
            @endif
            @if ($selectedModifierItem->price)
                <p class="jb-opt__base">{{ currency_format($selectedModifierItem->price, $jbCurrency) }}</p>
            @endif
        </div>
    </div>

    {{-- Les groupes d'options --}}
    @foreach ($modifiers as $modifier)
        @php
            $jbLink = $modifier->itemModifiers->first();
            $isRequired = (bool) ($jbLink->is_required ?? false);
            $isMulti = (bool) ($jbLink->allow_multiple_selection ?? false);
        @endphp
        <section class="jb-opt__group" wire:key="jb-opt-g-{{ $modifier->id }}">
            <div class="jb-opt__gh">
                <div class="min-w-0">
                    <h4>{{ $modifier->name }}</h4>
                    <p>{{ $isMulti ? __('modules.shop.optMulti') : __('modules.shop.optSingle') }}@if ($modifier->description) · {{ $modifier->description }}@endif</p>
                </div>
                <span class="jb-opt__tag {{ $isRequired ? 'is-req' : '' }}">{{ $isRequired ? __('modules.shop.optRequired') : __('modules.shop.optOptional') }}</span>
            </div>

            @foreach ($modifier->options as $option)
                <label class="jb-opt__row {{ $option->is_available ? '' : 'is-off' }}" wire:key="jb-opt-o-{{ $option->id }}">
                    @if ($option->is_available)
                        <input type="checkbox" class="jb-opt__input" value="{{ $option->id }}"
                            wire:model.defer="selectedModifiers.{{ $option->id }}"
                            wire:click="toggleSelection({{ $modifier->id }}, {{ $option->id }})">
                    @endif
                    <span class="jb-opt__mark {{ $isMulti ? '' : 'is-round' }}"></span>
                    <span class="jb-opt__oname">{{ $option->name }}</span>
                    <span class="jb-opt__oprice">
                        @if (!$option->is_available)
                            @lang('modules.menu.notAvailable')
                        @elseif ($option->price)
                            + {{ currency_format($option->price, $jbCurrency) }}
                        @endif
                    </span>
                </label>
            @endforeach

            @error('requiredModifiers.' . $modifier->id)
                <p class="jb-opt__err">{{ $message }}</p>
            @enderror
        </section>
    @endforeach

    {{-- Valider --}}
    <div class="jb-opt__foot">
        <button type="button" class="jb-opt__cancel" wire:click="$dispatch('closeModifiersModal')">@lang('app.cancel')</button>
        <button type="button" class="jb-opt__add" wire:click="saveModifiers" wire:loading.attr="disabled" wire:target="saveModifiers">
            @lang('modules.shop.addToCartBtn')
        </button>
    </div>
</div>
