{{-- Page d'un plat (plein écran). Utilise l'état Alpine : itemDetailOpen, selectedPreviewItem --}}
<div class="jb-dp" x-show="itemDetailOpen" x-cloak
    x-transition:enter="jb-dp-enter" x-transition:enter-start="jb-dp-enter-start" x-transition:enter-end="jb-dp-enter-end"
    x-transition:leave="jb-dp-leave" x-transition:leave-start="jb-dp-leave-start" x-transition:leave-end="jb-dp-leave-end"
    role="dialog" aria-modal="true" @keydown.escape.window="closeItemDetail()"
    x-data="{
        favs: (() => { try { return JSON.parse(localStorage.getItem('jbFavs') || '[]'); } catch (e) { return []; } })(),
        isFav(id) { return this.favs.includes(Number(id)); },
        toggleFav(id) {
            id = Number(id);
            this.favs = this.favs.includes(id) ? this.favs.filter(x => x !== id) : [...this.favs, id];
            try { localStorage.setItem('jbFavs', JSON.stringify(this.favs)); } catch (e) {}
        },
    }">
    <template x-if="selectedPreviewItem">
        <div class="jb-dp__in">
            {{-- Haut : fond coloré, retour, favori, grande image --}}
            <div class="jb-dp__hero">
                <button type="button" class="jb-dp__round" @click="closeItemDetail()" aria-label="@lang('app.back')">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" class="jb-dp__round jb-dp__fav" :class="isFav(selectedPreviewItem.id) ? 'is-on' : ''"
                    @click="toggleFav(selectedPreviewItem.id)" aria-label="Favori">
                    <svg viewBox="0 0 24 24" :fill="isFav(selectedPreviewItem.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                </button>

                <div class="jb-dp__photo">
                    <span class="jb-dp__emoji" x-text="window.jbEmoji(selectedPreviewItem.item_name + ' ' + (selectedPreviewItem.category_label || ''))"></span>
                    <template x-if="!cfg.hideImages && selectedPreviewItem.item_photo_url && !String(selectedPreviewItem.item_photo_url).includes('food.svg')">
                        <img :src="selectedPreviewItem.item_photo_url" :alt="selectedPreviewItem.item_name" decoding="async" x-on:error="$el.style.display='none'">
                    </template>
                </div>

                <div class="jb-dp__videos" x-show="(selectedPreviewItem.video_urls || []).length">
                    <template x-for="v in (selectedPreviewItem.video_urls || [])" :key="v">
                        <video :src="v" controls playsinline preload="metadata"></video>
                    </template>
                </div>

                <div class="jb-dp__thumbs" x-show="!cfg.hideImages && (selectedPreviewItem.extra_photo_urls || []).length">
                    <template x-for="u in (selectedPreviewItem.extra_photo_urls || [])" :key="u">
                        <button type="button" @click="selectedPreviewItem = { ...selectedPreviewItem, item_photo_url: u }">
                            <img :src="u" alt="" loading="lazy">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Bas : panneau blanc arrondi --}}
            <div class="jb-dp__sheet">
                <div class="jb-dp__row">
                    <div class="min-w-0">
                        <div class="jb-dp__cat" x-text="selectedPreviewItem.category_label || ''"></div>
                        <h2 class="jb-dp__name" x-text="selectedPreviewItem.item_name"></h2>
                    </div>
                    <div class="jb-dp__price">
                        <span x-text="itemPriceLabel(selectedPreviewItem)"></span>
                        <small x-show="selectedPreviewItem.original_price_label" x-text="selectedPreviewItem.original_price_label"></small>
                    </div>
                </div>

                <p class="jb-dp__desc" x-show="selectedPreviewItem.description" x-text="selectedPreviewItem.description"></p>

                <div class="jb-dp__chips">
                    <span class="jb-dp__chip" x-show="selectedPreviewItem.preparation_time">
                        ⏱ <span x-text="labels.preparationTime + ' : ' + (selectedPreviewItem.preparation_time >= 60 ? Math.floor(selectedPreviewItem.preparation_time / 60) + ' h' + (selectedPreviewItem.preparation_time % 60 ? ' ' + String(selectedPreviewItem.preparation_time % 60).padStart(2, '0') : '') : selectedPreviewItem.preparation_time + ' min')"></span>
                    </span>
                    <span class="jb-dp__chip jb-dp__chip--off" x-show="selectedPreviewItem.discount_percent" x-text="'-' + selectedPreviewItem.discount_percent + '%'"></span>
                    <template x-for="row in (selectedPreviewItem.dietary_labels_display || [])" :key="'d-' + row.key">
                        <span class="jb-dp__chip jb-dp__chip--diet"><img :src="row.icon" alt="" width="16" height="16"><span x-text="row.label"></span></span>
                    </template>
                </div>

                <template x-if="cfg && cfg.euAllergensEnabled !== false && selectedPreviewItem.eu_allergens_display && selectedPreviewItem.eu_allergens_display.length">
                    <div class="jb-dp__allergens">
                        <b x-text="labels.allergensHeading"></b>
                        <div>
                            <template x-for="row in selectedPreviewItem.eu_allergens_display" :key="'a-' + row.key">
                                <span><img :src="row.icon" alt="" width="16" height="16"><span x-text="row.label"></span></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="jb-dp__bar" x-show="selectedPreviewItem.is_available === false || !selectedPreviewItem.in_stock">
                <div class="jb-dp__off">@lang('modules.shop.unavailableLong')</div>
            </div>

            {{-- Barre du bas : quantité + bouton --}}
            <div class="jb-dp__bar" x-show="cfg.canCreateOrder && cfg.allowCustomerOrders && selectedPreviewItem.in_stock && selectedPreviewItem.is_available !== false">
                <template x-if="(cartItemQty[selectedPreviewItem.id] || 0) > 0">
                    <div class="jb-dp__bar-in">
                        <div class="jb-step jb-step--big">
                            <button type="button" class="minus" aria-label="−"
                                @click="selectedPreviewItem.variations_count > 0 ? callLw('subCartItems', selectedPreviewItem.id) : (selectedPreviewItem.modifier_groups_count > 0 ? callLw('subModifiers', selectedPreviewItem.id) : browseCartMutate('dec', selectedPreviewItem.id, selectedPreviewItem.variations_count, selectedPreviewItem.modifier_groups_count))">−</button>
                            <b x-text="cartItemQty[selectedPreviewItem.id] || 0"></b>
                            <button type="button" aria-label="+"
                                @click="(selectedPreviewItem.variations_count > 0 || selectedPreviewItem.modifier_groups_count > 0) ? callLw('addCartItems', selectedPreviewItem.id, selectedPreviewItem.variations_count, selectedPreviewItem.modifier_groups_count) : browseCartMutate('inc', selectedPreviewItem.id, selectedPreviewItem.variations_count, selectedPreviewItem.modifier_groups_count)">+</button>
                        </div>
                        <button type="button" class="jb-dp__cta" @click="closeItemDetail(); callLw('showCartItems')">
                            @lang('modules.shop.viewCart') · <span x-text="cartCount()"></span>
                        </button>
                    </div>
                </template>
                <template x-if="(cartItemQty[selectedPreviewItem.id] || 0) <= 0">
                    <div class="jb-dp__bar-in">
                        <button type="button" class="jb-dp__cta jb-dp__cta--full" x-show="cfg.orderLimitOk"
                            @click="window.jbFly && window.jbFly($el); browseCartMutate('add', selectedPreviewItem.id, selectedPreviewItem.variations_count || 0, selectedPreviewItem.modifier_groups_count || 0)">
                            @lang('modules.shop.addToCart') · <span x-text="itemPriceLabel(selectedPreviewItem)"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
