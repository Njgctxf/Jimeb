@php
    // Une seule bannière, fixe : la première bannière « haut de page » active créée par le restaurant
    $jbBanner = \App\Models\RestaurantBanner::forRestaurant($restaurant->id)->where('is_active', true)
        ->where('position', 'hero')->orderBy('sort_order')->orderBy('id')->first();
    // Action déclenchée par le bouton d'une bannière (expression Alpine)
    $jbAct = function ($b) {
        $id = (int) $b->target_id;
        return match ($b->target_type) {
            'dish' => $id ? "openItemDetail($id)" : "setBadgeFilter(null); go('menu')",
            'promo' => "go('promo')",
            'call' => "\$dispatch('jb-call-open')",
            'cart' => "callLw('showCartItems')",
            'category' => $id ? "setBadgeFilter(null); openCat($id)" : "setBadgeFilter(null); go('menu')",
            default => "setBadgeFilter(null); go('menu')",
        };
    };
    $jbTableTxt = null;
    if (!empty($table?->table_code)) {
        $w = $restaurant->welcome_table_label ?: __('modules.shop.tableWord');
        $jbTableTxt = \Illuminate\Support\Str::startsWith(mb_strtolower($table->table_code), mb_strtolower($w)) ? $table->table_code : $w . ' ' . $table->table_code;
    }
@endphp

{{-- Page client « menu d'abord » : en-tête compact, recherche, une bannière discrète,
     une rangée de plats populaires, puis les catégories (chacune a sa page). Scope Alpine : ttShopClientMenuBrowse --}}

{{-- ===== En-tête ===== --}}
<div class="jb-hm-top">
    <div class="jb-hm-top__logo"><x-restaurant-logo :restaurant="$restaurant" class="h-8" /></div>
    <div class="jb-hm-top__txt">
        <div class="jb-hm-top__name">{{ $restaurant->name }}</div>
        @if ($jbTableTxt)
            <div class="jb-hm-top__tag">{{ $jbTableTxt }}</div>
        @endif
    </div>
    <div class="jb-hm-top__act">
        @if ($jbCanCall)
            <button type="button" class="jb-ibtn jb-ibtn--bell" aria-label="@lang('app.callWaiter')" @click="$dispatch('jb-call-open')">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            </button>
        @endif
        <button type="button" class="jb-ibtn" id="jb-cart-top" aria-label="@lang('modules.order.viewCart')" @click="callLw('showCartItems')">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
            <span class="jb-badge" x-show="cartCount() > 0" x-text="cartCount()"></span>
        </button>
    </div>
</div>

{{-- ===== Recherche + filtre ===== --}}
@include('livewire.shop.partials.jimbe-searchrow', ['jbSearchGoesToMenu' => false])

{{-- ===== Bannière (seulement si le restaurant en a créé une) ===== --}}
@if ($jbBanner)
    @php
        $hasText = $jbBanner->title || $jbBanner->subtitle || $jbBanner->button_text;
        $bg = $jbBanner->image_url
            ? ($hasText ? "linear-gradient(95deg, rgba(8,20,18,.8) 6%, rgba(8,20,18,.4) 60%, rgba(8,20,18,.05)), " : '') . "url('" . $jbBanner->image_url . "')"
            : null;
    @endphp
    <div class="jb-banner {{ $bg ? '' : 'jb-banner--plain' }}" x-show="!(search || '').trim() && !badgeFilter"
        @if ($bg) style="background-image: {{ $bg }};" @endif
        @unless ($jbBanner->button_text) @click="{{ $jbAct($jbBanner) }}" @endunless>
        @if ($hasText)
            <div class="jb-banner__txt">
                @if ($jbBanner->title) <h2>{{ $jbBanner->title }}</h2> @endif
                @if ($jbBanner->subtitle) <p>{{ $jbBanner->subtitle }}</p> @endif
                @if ($jbBanner->button_text)
                    <button type="button" class="jb-banner__cta" @click.stop="{{ $jbAct($jbBanner) }}">{{ $jbBanner->button_text }}</button>
                @endif
            </div>
        @endif
    </div>
@endif

{{-- ===== Populaires (une seule rangée) ===== --}}
<div id="jb-popular" x-show="popularItems().length > 0 && !(search || '').trim() && !badgeFilter" x-cloak>
    <div class="jb-hm-sec">
        <h3>@lang('modules.shop.popularPicks')</h3>
    </div>
    <div class="jb-hm-picks">
        <template x-for="item in popularItems()" :key="'jb-popular-' + item.id">
            @include('livewire.shop.partials.jimbe-pick')
        </template>
    </div>
</div>

{{-- ===== Catégories : chacune ouvre sa page ===== --}}
<div id="jb-cats" x-show="cfg.showMenu && !(search || '').trim() && !badgeFilter">
    <div class="jb-hm-sec">
        <h3>@lang('modules.shop.categoriesTitle')</h3>
    </div>
    <div class="jb-cats">
        <template x-for="c in visibleCategories()" :key="'jb-cat-' + c.id">
            <button type="button" class="jb-cat" @click="openCat(c.id)">
                <span class="jb-cat__img">
                    <span x-text="window.jbEmoji(c.name)"></span>
                    <template x-if="!cfg.hideImages && catThumb(c.id)">
                        <img :src="catThumb(c.id)" alt="" loading="lazy" x-on:error="$el.style.display='none'">
                    </template>
                </span>
                <span class="jb-cat__name" x-text="c.name"></span>
                <span class="jb-cat__count" x-text="c.count + ' ' + (c.count > 1 ? @js(__('modules.shop.dishes')) : @js(__('modules.shop.dish')))"></span>
            </button>
        </template>
    </div>
    <div class="jb-empty" x-show="visibleCategories().length === 0" x-cloak x-text="labels.noItemAdded"></div>
</div>

{{-- ===== Recherche ou filtre : les plats trouvés, classés par catégorie ===== --}}
<div x-show="(search || '').trim() || badgeFilter" x-cloak>
    @include('livewire.shop.partials.jimbe-menu-list')
</div>
