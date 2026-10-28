{{-- Carte « Plats populaires » (défilement horizontal). Variable Alpine : item --}}
<article class="jb-pick" :class="(item.in_stock && item.is_available !== false) ? '' : 'is-out'"
    x-data="{ fav: (() => { try { return JSON.parse(localStorage.getItem('jbFavs') || '[]').includes(Number(item.id)); } catch (e) { return false; } })() }"
    x-init="$nextTick(() => { window.jbReveal && window.jbReveal($el); })">
    <span class="jb-pick__tag" x-show="item.discount_percent || (item.badges || []).length"
        :class="item.discount_percent ? 'is-off' : 'jb-pick__tag--' + ((item.badges || [])[0] || {}).tone"
        x-text="item.discount_percent ? '-' + item.discount_percent + '%' : ((item.badges || [])[0] || {}).label"></span>
    <button type="button" class="jb-pick__heart" :class="fav ? 'is-on' : ''" aria-label="@lang('modules.shop.favorite')"
        @click.stop="fav = !fav; try { let a = JSON.parse(localStorage.getItem('jbFavs') || '[]').filter(x => x !== Number(item.id)); if (fav) a.push(Number(item.id)); localStorage.setItem('jbFavs', JSON.stringify(a)); } catch (e) {}">
        <svg viewBox="0 0 24 24" :fill="fav ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
    </button>

    <div class="jb-pick__img" @click="openItemDetail(item.id)">
        <span class="jb-pick__emoji" x-text="window.jbEmoji(item.item_name + ' ' + (item.category_label || ''))"></span>
        <template x-if="!cfg.hideImages && item.item_photo_url && !String(item.item_photo_url).includes('food.svg')">
            <img :src="item.item_photo_url" :alt="item.item_name" loading="lazy" decoding="async" x-on:error="$el.style.display='none'">
        </template>
    </div>

    <h4 class="jb-pick__name" @click="openItemDetail(item.id)" x-text="item.item_name"></h4>
    <p class="jb-pick__desc" x-text="item.description || ''"></p>
    <div class="jb-pick__time" x-show="item.preparation_time" x-text="item.preparation_time + ' min'"></div>

    <div class="jb-pick__foot">
        <div class="jb-pick__price">
            <span x-text="itemPriceLabel(item)"></span>
            <small x-show="item.original_price_label" x-text="item.original_price_label"></small>
        </div>
        <div x-show="cfg.canCreateOrder && cfg.allowCustomerOrders && item.in_stock && item.is_available !== false" @click.stop>
            <div class="jb-step" x-show="(cartItemQty[item.id] || 0) > 0">
                <button type="button" class="minus" aria-label="−"
                    @click="item.variations_count > 0 ? callLw('subCartItems', item.id) : (item.modifier_groups_count > 0 ? callLw('subModifiers', item.id) : browseCartMutate('dec', item.id, item.variations_count, item.modifier_groups_count))">−</button>
                <b x-text="cartItemQty[item.id] || 0"></b>
                <button type="button" aria-label="+"
                    @click="window.jbFly && window.jbFly($el); (item.variations_count > 0 || item.modifier_groups_count > 0) ? callLw('addCartItems', item.id, item.variations_count, item.modifier_groups_count) : browseCartMutate('inc', item.id, item.variations_count, item.modifier_groups_count)">+</button>
            </div>
            <button type="button" class="jb-plus" aria-label="+"
                x-show="(cartItemQty[item.id] || 0) <= 0 && cfg.orderLimitOk"
                @click.stop="window.jbFly && window.jbFly($el); browseCartMutate('add', item.id, item.variations_count, item.modifier_groups_count)">
                <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        </div>
        <span class="jb-out" x-show="!(item.in_stock && item.is_available !== false)">@lang('modules.shop.unavailable')</span>
    </div>
</article>
