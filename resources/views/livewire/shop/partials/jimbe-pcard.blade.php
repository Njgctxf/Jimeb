{{-- Carte plat « Plats populaires » (Alpine : variable `item`) --}}
<div class="jb-pcard" :class="item.in_stock ? '' : 'is-out'"
    x-init="$nextTick(() => { window.jbReveal && window.jbReveal($el); })">
    <div class="jb-pcard__plate" @click="openItemDetail(item.id)">
        <span class="jb-pcard__emoji" x-text="window.jbEmoji(item.item_name + ' ' + (item.category_label || ''))"></span>
        <template x-if="!cfg.hideImages && item.item_photo_url && !String(item.item_photo_url).includes('food.svg')">
            <img :src="item.item_photo_url" :alt="item.item_name" loading="lazy" decoding="async"
                x-on:error="$el.style.display='none'">
        </template>
        <span class="jb-pill" x-show="item.discount_percent" x-text="'-' + item.discount_percent + '%'"></span>
    </div>
    <div class="jb-pcard__name" @click="openItemDetail(item.id)" x-text="item.item_name"></div>
    <div class="jb-pcard__price">
        <template x-if="item.variations_count == 0">
            <span>
                <span x-text="itemPriceLabel(item)"></span>
                <small x-show="item.original_price_label" x-text="item.original_price_label"></small>
            </span>
        </template>
        <template x-if="item.variations_count > 0">
            <span x-text="itemPriceLabel(item)"></span>
        </template>
    </div>

    <div class="jb-pcard__ctl" x-show="cfg.canCreateOrder && cfg.allowCustomerOrders" @click.stop>
        <span class="jb-out" x-show="!item.in_stock" x-text="labels.outOfStock"></span>
        <div class="jb-step jb-step--wide" x-show="item.in_stock && (cartItemQty[item.id] || 0) > 0">
            <button type="button" class="minus" aria-label="−"
                @click="item.variations_count > 0 ? callLw('subCartItems', item.id) : (item.modifier_groups_count > 0 ? callLw('subModifiers', item.id) : browseCartMutate('dec', item.id, item.variations_count, item.modifier_groups_count))">−</button>
            <b x-text="cartItemQty[item.id] || 0"></b>
            <button type="button" aria-label="+"
                @click="window.jbFly && window.jbFly($el); (item.variations_count > 0 || item.modifier_groups_count > 0) ? callLw('addCartItems', item.id, item.variations_count, item.modifier_groups_count) : browseCartMutate('inc', item.id, item.variations_count, item.modifier_groups_count)">+</button>
        </div>
        <button type="button" class="jb-pcard__btn"
            x-show="item.in_stock && (cartItemQty[item.id] || 0) <= 0 && cfg.orderLimitOk"
            @click.stop="window.jbFly && window.jbFly($el); browseCartMutate('add', item.id, item.variations_count, item.modifier_groups_count)">
            @lang('modules.shop.orderBtn')
        </button>
    </div>
</div>
