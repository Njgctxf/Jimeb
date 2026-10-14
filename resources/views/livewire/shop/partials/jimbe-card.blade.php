{{-- Carte plat (Alpine : variable `item`) --}}
<div class="jb-card" :class="item.in_stock ? '' : 'is-out'">
    <div class="jb-card__img" @click="openItemDetail(item.id)" style="cursor:pointer">
        <span class="jb-card__emoji" x-text="window.jbEmoji(item.item_name + ' ' + (item.category_label || ''))"></span>
        <template x-if="!cfg.hideImages && item.item_photo_url && !String(item.item_photo_url).includes('food.svg')">
            <img :src="item.item_photo_url" :alt="item.item_name" loading="lazy" onerror="this.style.display='none'" style="position:absolute;inset:0">
        </template>
        <span class="jb-pill" x-show="item.discount_percent" x-text="'-' + item.discount_percent + '%'"></span>
    </div>
    <div class="jb-card__body" @click="openItemDetail(item.id)" style="cursor:pointer">
        <div class="jb-card__name" x-text="item.item_name"></div>
        <div class="jb-card__desc" x-text="item.description || ''"></div>
        <div class="jb-card__time" x-show="item.preparation_time"
            x-text="'⏱ ' + (item.preparation_time >= 60 ? Math.floor(item.preparation_time / 60) + ' h' + (item.preparation_time % 60 ? ' ' + String(item.preparation_time % 60).padStart(2, '0') : '') : item.preparation_time + ' min')"></div>
    </div>
    <div class="jb-card__foot">
        <div class="jb-price">
            <template x-if="item.variations_count == 0">
                <span>
                    <span x-text="itemPriceLabel(item)"></span>
                    <small x-show="item.original_price_label" x-text="item.original_price_label"></small>
                </span>
            </template>
        </div>
        <div x-show="cfg.canCreateOrder && cfg.allowCustomerOrders" @click.stop>
            <span class="jb-out" x-show="!item.in_stock" x-text="labels.outOfStock"></span>
            <div class="jb-step" x-show="item.in_stock && (cartItemQty[item.id] || 0) > 0">
                <button type="button" class="minus"
                    @click="item.variations_count > 0 ? callLw('subCartItems', item.id) : (item.modifier_groups_count > 0 ? callLw('subModifiers', item.id) : browseCartMutate('dec', item.id, item.variations_count, item.modifier_groups_count))">−</button>
                <b x-text="cartItemQty[item.id] || 0"></b>
                <button type="button"
                    @click="(item.variations_count > 0 || item.modifier_groups_count > 0) ? callLw('addCartItems', item.id, item.variations_count, item.modifier_groups_count) : browseCartMutate('inc', item.id, item.variations_count, item.modifier_groups_count)">+</button>
            </div>
            <button type="button" class="jb-plus" aria-label="+"
                x-show="item.in_stock && (cartItemQty[item.id] || 0) <= 0 && cfg.orderLimitOk"
                @click.stop="browseCartMutate('add', item.id, item.variations_count, item.modifier_groups_count)">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        </div>
    </div>
</div>
