{{-- Page « Promos » : tous les plats en promotion --}}
@include('livewire.shop.partials.jimbe-page-head', ['jbPageTitle' => __('modules.shop.navPromos')])

<div class="jb-promo-hero" x-show="promoItems().length > 0" x-cloak>
    <span class="jb-promo-hero__tag">🏷️ @lang('modules.shop.exclusive')</span>
    <h2 x-text="@js(__('modules.shop.upTo')) + ' -' + Math.max(...promoItems().map(p => Number(p.discount_percent || 0)), 0) + '%'"></h2>
    <p x-text="promoItems().length + ' ' + (promoItems().length > 1 ? @js(__('modules.shop.dishes')) : @js(__('modules.shop.dish'))) + ' ' + @js(__('modules.shop.promoOnSale'))"></p>
</div>

<div class="jb-rows">
    <template x-for="(item, ix) in promoItems()" :key="'jb-p-' + item.id">
        @include('livewire.shop.partials.jimbe-row')
    </template>
</div>

<div class="jb-promo-empty" x-show="promoItems().length === 0" x-cloak>
    <div>🏷️</div>
    <h3>@lang('modules.shop.promoEmptyTitle')</h3>
    <p>@lang('modules.shop.promoEmptySub')</p>
    <button type="button" class="jb-hero__cta" @click="go('menu')">@lang('modules.shop.seeMenu') <span>→</span></button>
</div>
