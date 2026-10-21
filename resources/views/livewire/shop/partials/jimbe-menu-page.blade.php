{{-- Page « Menu » : toute la carte du restaurant, avec onglets de catégories collés en haut --}}
<div class="jb-mp-head">
    <button type="button" class="jb-ibtn" @click="go('home')" aria-label="@lang('app.back')">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
    </button>
    <div class="jb-mp-head__t">
        <small>{{ $restaurant->name }}</small>
        <h1>@lang('modules.shop.navMenu')</h1>
    </div>
    <div class="jb-hm-top__act">
        @if ($jbCanCall)
            <button type="button" class="jb-ibtn jb-ibtn--bell" aria-label="@lang('app.callWaiter')" @click="$dispatch('jb-call-open')">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            </button>
        @endif
        <button type="button" class="jb-ibtn" aria-label="@lang('modules.order.viewCart')" @click="callLw('showCartItems')">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
            <span class="jb-badge" x-show="cartCount() > 0" x-text="cartCount()"></span>
        </button>
    </div>
</div>

@include('livewire.shop.partials.jimbe-searchrow', ['jbSearchGoesToMenu' => false])

<div id="jb-menu" x-show="cfg.showMenu" x-cloak x-data="jbSpy()" x-init="start()">
    {{-- Filtre actif --}}
    <div class="jb-mp-filter" x-show="badgeFilter" x-cloak>
        <span x-text="@js(__('modules.shop.filtered')) + ' : ' + ({ promo: '🏷️ ' + @js(__('modules.shop.navPromos')) }[badgeFilter] || badgeFilter)"></span>
        <button type="button" @click="setBadgeFilter(null)">✕ @lang('modules.shop.clear')</button>
    </div>

    {{-- Onglets de catégories --}}
    <div class="jb-tabs" x-show="groupedForDisplay().length > 1">
        <div class="jb-tabs__in" x-ref="scroller">
            <template x-for="(g, gi) in groupedForDisplay()" :key="'jb-c-' + gi + g.key">
                <button type="button" class="jb-tab" :class="active === gi ? 'is-active' : ''" :data-i="gi" @click="jump(gi)">
                    <span class="jb-tab__e" x-text="window.jbEmoji(g.key)"></span>
                    <span x-text="g.key"></span>
                </button>
            </template>
        </div>
    </div>

    <template x-for="(g, gi) in groupedForDisplay()" :key="'jb-s-' + gi + g.key">
        <section class="jb-section" :id="'jb-sec-' + gi">
            <div class="jb-sec2">
                <span class="jb-sec2__e" x-text="window.jbEmoji(g.key)"></span>
                <div>
                    <h3 x-text="g.key"></h3>
                    <small x-text="g.items.length + ' ' + (g.items.length > 1 ? @js(__('modules.shop.dishes')) : @js(__('modules.shop.dish')))"></small>
                </div>
            </div>
            <div class="jb-rows">
                <template x-for="(item, ix) in g.items" :key="'jb-i-' + item.id">
                    @include('livewire.shop.partials.jimbe-row')
                </template>
            </div>
        </section>
    </template>

    <template x-if="flatItems().length === 0">
        <div class="jb-empty" x-text="labels.noItemAdded"></div>
    </template>
</div>
