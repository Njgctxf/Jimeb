{{-- Page « Menu » : toute la carte du restaurant, avec onglets de catégories collés en haut --}}
@include('livewire.shop.partials.jimbe-page-head', ['jbPageTitle' => __('modules.shop.navMenu')])

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
