{{-- Ligne de plat façon carte de restaurant (Alpine : variable `item`). Sans photo, le texte occupe toute la ligne. --}}
<div class="jb-dish" :class="[item.in_stock ? '' : 'is-out', (!cfg.hideImages && item.item_photo_url && !String(item.item_photo_url).includes('food.svg')) ? 'has-photo' : '']"
    x-init="$nextTick(() => { window.jbReveal && window.jbReveal($el); const im = $el.querySelector('img'); if (im && im.complete && im.naturalWidth === 0) $el.classList.add('no-img'); })">
    <div class="jb-dish__main" @click="openItemDetail(item.id)">
        <div class="jb-dish__name" x-text="item.item_name"></div>
        <div class="jb-dish__desc" x-show="item.description" x-text="item.description || ''"></div>
        <div class="jb-dish__meta">
            <template x-if="item.variations_count == 0">
                <span class="jb-dish__price">
                    <span x-text="itemPriceLabel(item)"></span>
                    <small x-show="item.original_price_label" x-text="item.original_price_label"></small>
                </span>
            </template>
            <template x-if="item.variations_count > 0">
                <span class="jb-dish__price jb-dish__price--from" x-text="itemPriceLabel(item)"></span>
            </template>
            <span class="jb-dish__time" x-show="item.preparation_time"
                x-text="'· ' + (item.preparation_time >= 60 ? Math.floor(item.preparation_time / 60) + ' h' + (item.preparation_time % 60 ? ' ' + String(item.preparation_time % 60).padStart(2, '0') : '') : item.preparation_time + ' min')"></span>
            <span class="jb-pill" x-show="item.discount_percent" x-text="'-' + item.discount_percent + '%'"></span>
        </div>
    </div>
    <div class="jb-dish__side">
        <div class="jb-dish__img" x-show="!cfg.hideImages && item.item_photo_url && !String(item.item_photo_url).includes('food.svg')" @click="openItemDetail(item.id)">
            <img :src="item.item_photo_url" :alt="item.item_name" loading="lazy" decoding="async" @error="$el.closest('.jb-dish').classList.add('no-img')">
        </div>
        <div class="jb-dish__ctl" x-show="cfg.canCreateOrder && cfg.allowCustomerOrders" @click.stop>
            <span class="jb-out" x-show="!item.in_stock" x-text="labels.outOfStock"></span>
            <div class="jb-step" x-show="item.in_stock && (cartItemQty[item.id] || 0) > 0">
                <button type="button" class="minus" aria-label="−"
                    @click="item.variations_count > 0 ? callLw('subCartItems', item.id) : (item.modifier_groups_count > 0 ? callLw('subModifiers', item.id) : browseCartMutate('dec', item.id, item.variations_count, item.modifier_groups_count))">−</button>
                <b x-text="cartItemQty[item.id] || 0"></b>
                <button type="button" aria-label="+"
                    @click="window.jbFly && window.jbFly($el); (item.variations_count > 0 || item.modifier_groups_count > 0) ? callLw('addCartItems', item.id, item.variations_count, item.modifier_groups_count) : browseCartMutate('inc', item.id, item.variations_count, item.modifier_groups_count)">+</button>
            </div>
            <button type="button" class="jb-plus" aria-label="+"
                x-show="item.in_stock && (cartItemQty[item.id] || 0) <= 0 && cfg.orderLimitOk"
                @click.stop="window.jbFly && window.jbFly($el); browseCartMutate('add', item.id, item.variations_count, item.modifier_groups_count)">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        </div>
    </div>
</div>
