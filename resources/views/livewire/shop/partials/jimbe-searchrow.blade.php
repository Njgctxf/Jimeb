{{-- Recherche + bouton filtre. Paramètre : $jbSearchGoesToMenu (bool) --}}
<div class="jb-hm-searchrow" x-data="{ f: false }">
    <label class="jb-search jb-hm-search">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input type="text" x-model.debounce.300ms="search" placeholder="@lang('modules.shop.search')"
            @if (!empty($jbSearchGoesToMenu)) @input.debounce.350ms="if ($event.target.value.trim() !== '') { go('menu'); }" @endif>
    </label>
    <button type="button" class="jb-hm-filter" :class="badgeFilter ? 'is-on' : ''" @click="f = !f" aria-label="@lang('modules.shop.filter')">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
        <i x-show="badgeFilter"></i>
    </button>

    <div class="jb-hm-fpanel" x-show="f" x-cloak x-transition.origin.top.right @click.outside="f = false">
        <div class="jb-hm-fpanel__t">@lang('modules.shop.filterBy')</div>
        <div class="jb-hm-fpanel__chips">
            <button type="button" :class="!badgeFilter ? 'is-on' : ''" @click="setBadgeFilter(null); f = false">🍽️ @lang('modules.shop.all')</button>
            <button type="button" :class="badgeFilter === 'promo' ? 'is-on' : ''" @click="setBadgeFilter('promo'); f = false; go('menu')">🏷️ @lang('modules.shop.navPromos')</button>
            @foreach (\App\Support\DishBadges::keys() as $bk)
                <button type="button" :class="badgeFilter === '{{ $bk }}' ? 'is-on' : ''" @click="setBadgeFilter('{{ $bk }}'); f = false; go('menu')">{{ \App\Support\DishBadges::emoji($bk) }} @lang(\App\Support\DishBadges::langKey($bk))</button>
            @endforeach
        </div>
    </div>
</div>
