{{-- Jimbe : choix de la taille / variante d'un plat sur la page client. Même logique que livewire.pos.item-variations. --}}
@php
    $jbPhoto = (restaurant() && !restaurant()->hide_menu_item_image_on_customer_site
        && $menuItem->item_photo_url && !str_contains($menuItem->item_photo_url, 'food.svg'))
        ? $menuItem->item_photo_url : null;
@endphp
<div class="jb-opt">
    <div class="jb-opt__head">
        <div class="jb-opt__img">
            @if ($jbPhoto)
                <img src="{{ $jbPhoto }}" alt="" onerror="this.remove()">
            @endif
            <span>{{ mb_strtoupper(mb_substr(trim($menuItem->item_name), 0, 1)) }}</span>
        </div>
        <div class="min-w-0">
            <h3 class="jb-opt__name">{{ $menuItem->item_name }}</h3>
            @if ($menuItem->description)
                <p class="jb-opt__desc">{{ $menuItem->description }}</p>
            @endif
        </div>
    </div>

    <section class="jb-opt__group">
        <div class="jb-opt__gh">
            <div class="min-w-0">
                <h4>@lang('modules.shop.optChooseSize')</h4>
                <p>@lang('modules.shop.optSingle')</p>
            </div>
            <span class="jb-opt__tag is-req">@lang('modules.shop.optRequired')</span>
        </div>

        @foreach ($menuItem->variations as $item)
            <button type="button" class="jb-opt__row jb-opt__row--btn" wire:key="jb-var-{{ $item->id }}"
                wire:click="setItemVariation({{ $item->id }})" wire:loading.attr="disabled">
                <span class="jb-opt__mark is-round"></span>
                <span class="jb-opt__oname">{{ $item->variation }}</span>
                <span class="jb-opt__oprice is-strong">{{ $item->price ? currency_format($item->price, $menuItem->branch->restaurant->currency_id) : '' }}</span>
            </button>
        @endforeach
    </section>
</div>
