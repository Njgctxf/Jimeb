{{-- Bandeau « plat du moment » (esprit Foodslice) : texte à gauche, panneau orange en diagonale avec la photo,
     gros prix et bouton noir. Plat mis en avant : le premier des plats populaires. Scope Alpine : ttShopClientMenuBrowse --}}
<template x-for="it in ((!(search || '').trim() && !badgeFilter) ? popularItems().slice(0, 1) : [])" :key="'fs-hero-' + it.id">
    <section class="jb-fs-hero">
        <div class="jb-fs-hero__panel" aria-hidden="true"></div>

        <div class="jb-fs-hero__txt">
            <p class="jb-fs-hero__kicker">@lang('modules.shop.dishOfTheMoment')</p>
            {{-- Le dernier mot du nom en orange, comme « Délicieux Burger. » --}}
            <h2 class="jb-fs-hero__title" @click="openItemDetail(it.id)">
                <span x-text="it.item_name.trim().split(' ').slice(0, -1).join(' ')"></span>
                <em x-text="it.item_name.trim().split(' ').slice(-1)[0] + '.'"></em>
            </h2>
            <div class="jb-fs-hero__chips" x-show="(it.badges || []).length">
                <template x-for="(b, bi) in (it.badges || []).slice(0, 2)" :key="'fs-b-' + b.key">
                    <span class="jb-fs-chip" :class="bi === 0 ? 'is-hot' : ''" x-text="b.label"></span>
                </template>
            </div>
            <p class="jb-fs-hero__price" x-text="itemPriceLabel(it)"></p>
            <button type="button" class="jb-fs-btn"
                x-show="cfg.canCreateOrder && cfg.allowCustomerOrders && it.in_stock && it.is_available !== false && cfg.orderLimitOk"
                @click="window.jbFly && window.jbFly($el); browseCartMutate('add', it.id, it.variations_count || 0, it.modifier_groups_count || 0)">
                @lang('modules.shop.addToCart')
            </button>
        </div>

        <button type="button" class="jb-fs-hero__photo" @click="openItemDetail(it.id)" :aria-label="it.item_name">
            <span x-text="window.jbEmoji(it.item_name)"></span>
            <template x-if="!cfg.hideImages && it.item_photo_url && !String(it.item_photo_url).includes('food.svg')">
                <img :src="it.item_photo_url" alt="" decoding="async" x-on:error="$el.style.display='none'">
            </template>
        </button>
    </section>
</template>
