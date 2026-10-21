{{-- Plat en ligne : photo + NOM + description + prix + badges. Variables Alpine : `item`, `ix` (rang pour l'animation) --}}
<article class="jb-row"
    :class="(item.in_stock && item.is_available !== false) ? '' : 'is-out'"
    :style="'--d:' + (((typeof ix !== 'undefined' ? ix : 0) % 8) * 70) + 'ms'"
    x-init="$nextTick(() => { window.jbReveal && window.jbReveal($el); })">

    <div class="jb-row__img" @click="openItemDetail(item.id)">
        <span class="jb-row__emoji" x-text="window.jbEmoji(item.item_name + ' ' + (item.category_label || ''))"></span>
        <template x-if="!cfg.hideImages && item.item_photo_url && !String(item.item_photo_url).includes('food.svg')">
            <img :src="item.item_photo_url" :alt="item.item_name" loading="lazy" decoding="async" x-on:error="$el.style.display='none'">
        </template>
        <div class="jb-row__badges jb-row__badges--over" x-show="(item.badges || []).length">
            <template x-for="b in (item.badges || [])" :key="b.key">
                <span class="jb-badge2" :class="'jb-badge2--' + b.tone"><i x-text="b.emoji"></i><span x-text="b.label"></span></span>
            </template>
        </div>
        <span class="jb-row__off" x-show="!(item.in_stock && item.is_available !== false)">@lang('modules.shop.unavailable')</span>
        <span class="jb-pill" x-show="item.discount_percent" x-text="'-' + item.discount_percent + '%'"></span>
    </div>

    <div class="jb-row__body" @click="openItemDetail(item.id)">
        <h4 class="jb-row__name" x-text="item.item_name"></h4>
        <p class="jb-row__desc" x-show="item.description" x-text="item.description"></p>
        <div class="jb-row__foot">
            <div class="jb-row__price">
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
            <span class="jb-row__time" x-show="item.preparation_time"
                x-text="'⏱ ' + (item.preparation_time >= 60 ? Math.floor(item.preparation_time / 60) + ' h' + (item.preparation_time % 60 ? ' ' + String(item.preparation_time % 60).padStart(2, '0') : '') : item.preparation_time + ' min')"></span>
        </div>
    </div>

    <div class="jb-row__ctl" x-show="cfg.canCreateOrder && cfg.allowCustomerOrders && item.in_stock && item.is_available !== false" @click.stop>
        <div class="jb-step jb-step--col" x-show="(cartItemQty[item.id] || 0) > 0">
            <button type="button" aria-label="+"
                @click="window.jbFly && window.jbFly($el); (item.variations_count > 0 || item.modifier_groups_count > 0) ? callLw('addCartItems', item.id, item.variations_count, item.modifier_groups_count) : browseCartMutate('inc', item.id, item.variations_count, item.modifier_groups_count)">+</button>
            <b x-text="cartItemQty[item.id] || 0"></b>
            <button type="button" class="minus" aria-label="−"
                @click="item.variations_count > 0 ? callLw('subCartItems', item.id) : (item.modifier_groups_count > 0 ? callLw('subModifiers', item.id) : browseCartMutate('dec', item.id, item.variations_count, item.modifier_groups_count))">−</button>
        </div>
        <button type="button" class="jb-plus jb-plus--row" aria-label="+"
            x-show="(cartItemQty[item.id] || 0) <= 0 && cfg.orderLimitOk"
            @click.stop="window.jbFly && window.jbFly($el); browseCartMutate('add', item.id, item.variations_count, item.modifier_groups_count)">
            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        </button>
    </div>
</article>
