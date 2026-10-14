@php
    $lwId = $this->getId();
@endphp

<div wire:ignore class="tt-shop-client-menu-root">
    <script type="application/json" id="tt-shop-client-catalog-json">{!! json_encode($clientShopCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/json" id="tt-shop-client-browse-config-json">{!! json_encode($clientShopBrowseConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>

    <div x-data="ttShopClientMenuBrowse('{{ $lwId }}', @js($clientShopBrowseConfig['labels'] ?? []))" x-init="init()" class="min-w-0 w-full max-w-full">
        <div x-show="showOrderTypeOverlay && !cfg.force_dine_in_qr && !cfg.disable_order_type_popup" x-cloak x-transition.opacity
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 dark:bg-black/70"
            role="dialog" aria-modal="true"
            @keydown.escape.window="dismissOrderTypeOverlay()">
            <div class="relative w-full max-w-xl overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-900" @click.stop>
                <button type="button"
                    class="absolute top-3 end-3 z-10 inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    @click="dismissOrderTypeOverlay()"
                    :aria-label="labels.close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="px-6 pt-6 pb-2 text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="labels.selectOrderType"></h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="labels.selectOrderTypeDescription"></p>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="ot in orderTypesPick" :key="'ot-pick-' + ot.id">
                        <button type="button" @click="pickOrderType(ot.id)"
                            :class="Number(orderTypeId) === Number(ot.id)
                                ? 'border-skin-base ring-2 ring-skin-base/30 bg-skin-base/[0.06] dark:bg-skin-base/10'
                                : 'border-gray-200 dark:border-gray-600'"
                            class="flex flex-col items-center justify-center p-6 transition-all duration-200 border-2 rounded-lg hover:border-skin-base hover:shadow-lg dark:hover:border-skin-base group">
                            <div class="flex items-center justify-center w-16 h-16 mb-4 transition-colors rounded-full bg-gray-50 group-hover:bg-skin-base dark:bg-gray-700">
                                <svg class="w-8 h-8 text-gray-600 transition-colors group-hover:text-white dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path x-show="ot.type === 'dine_in'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    <path x-show="ot.type === 'delivery'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    <path x-show="ot.type === 'pickup'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white" x-text="ot.name"></span>
                            <span class="mt-2 text-sm text-center text-gray-600 dark:text-gray-400" x-show="ot.type === 'dine_in'" x-text="labels.dineInDescription"></span>
                            <span class="mt-2 text-sm text-center text-gray-600 dark:text-gray-400" x-show="ot.type === 'delivery'" x-text="labels.deliveryDescription"></span>
                            <span class="mt-2 text-sm text-center text-gray-600 dark:text-gray-400" x-show="ot.type === 'pickup'" x-text="labels.pickupDescription"></span>
                        </button>
                    </template>
                </div>
                <div class="px-6 pb-4 text-center">
                    <button type="button"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="dismissOrderTypeOverlay()"
                        x-text="labels.browseMenuContinue"></button>
                </div>
                <div class="px-6 pb-6 text-center" x-show="showBookTableEscape && bookTableUrl" x-cloak>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="labels.bookTableInstead"></p>
                    <a :href="bookTableUrl"
                        class="inline-flex items-center justify-center mt-2 text-sm font-semibold text-skin-base hover:underline">
                        <span x-text="labels.bookTable"></span>
                    </a>
                </div>
            </div>
        </div>

        <div x-show="orderTypesPick.length > 1 && !cfg.force_dine_in_qr && !cfg.disable_order_type_popup" x-cloak class="px-4 mt-3">
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-100 bg-white px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-800/80 sm:px-4">
                <div class="min-w-0 text-sm">
                    <template x-if="orderTypeConfirmedByUser && selectedOrderTypeLabel()">
                        <p class="truncate text-gray-700 dark:text-gray-200">
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="labels.currentOrderType + ':'"></span>
                            <span class="ms-1 font-semibold" x-text="selectedOrderTypeLabel()"></span>
                        </p>
                    </template>
                    <template x-if="!orderTypeConfirmedByUser">
                        <p class="text-xs text-amber-700 dark:text-amber-300" x-text="labels.orderTypeNotSelectedYet"></p>
                    </template>
                </div>
                <button type="button"
                    @click="openOrderTypeOverlay()"
                    class="inline-flex shrink-0 items-center gap-1 rounded-md border border-skin-base/30 bg-skin-base/[0.08] px-3 py-1.5 text-xs font-semibold text-skin-base transition hover:bg-skin-base/[0.14] dark:border-skin-base/40 dark:bg-skin-base/10">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="labels.changeOrderType"></span>
                </button>
            </div>
        </div>

        <link rel="stylesheet" href="{{ asset('css/jimbe-shop.css') }}?v={{ @filemtime(public_path('css/jimbe-shop.css')) }}">

        <div class="jb-shop" id="jb-top" x-show="cfg.showMenu !== false" x-cloak>
            {{-- Barre du haut --}}
            <div class="jb-top">
                <div class="jb-top__brand">
                    <div class="jb-top__logo"><x-restaurant-logo :restaurant="$restaurant" class="h-8" /></div>
                    <div class="min-w-0">
                        <div class="jb-top__hello" x-text="(new Date().getHours() < 12 ? @js(__('modules.shop.goodMorning')) : (new Date().getHours() < 18 ? @js(__('modules.shop.goodAfternoon')) : @js(__('modules.shop.goodEvening')))) + ' 👋'"></div>
                        <div class="jb-top__name">{{ $restaurant->name }}</div>
                    </div>
                </div>
                <div class="jb-top__actions">
                    <button type="button" class="jb-ibtn" aria-label="@lang('app.callWaiter')"
                        @click="document.querySelector('[wire\\:click=callWaiter]')?.click()">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    </button>
                    <button type="button" class="jb-ibtn" aria-label="@lang('modules.order.viewCart')" @click="callLw('showCartItems')">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
                        <span class="jb-badge" x-show="cartCount() > 0" x-text="cartCount()"></span>
                    </button>
                </div>
            </div>

            {{-- Recherche --}}
            <label class="jb-search">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" x-model.debounce.300ms="search" placeholder="@lang('modules.shop.search')">
            </label>

            {{-- Bannière --}}
            <div class="jb-hero">
                <span class="jb-hero__tag">@lang('modules.shop.heroTag')</span>
                <h2>@lang('modules.shop.heroTitle')</h2>
                <p>@lang('modules.shop.heroSub')</p>
                <button type="button" class="jb-hero__cta" @click="document.getElementById('jb-menu')?.scrollIntoView({behavior: 'smooth'})">
                    @lang('modules.shop.heroCta') <span>→</span>
                </button>
                <div class="jb-hero__emoji">🍽️</div>
                <span class="jb-hero__bubble b1">🍗</span><span class="jb-hero__bubble b2">🥗</span><span class="jb-hero__bubble b3">🥤</span>
            </div>

            {{-- Menus --}}
            <div class="jb-chips" x-show="menus.length > 0">
                <button type="button" class="jb-chip" :class="menuId === null ? 'is-active' : ''" @click="setMenuId(null)">
                    <div class="jb-chip__c">🍴</div><span class="jb-chip__t">@lang('modules.shop.all')</span>
                </button>
                <template x-for="m in menus" :key="'jb-m-' + m.id">
                    <button type="button" class="jb-chip" :class="menuId === m.id ? 'is-active' : ''" @click="setMenuId(m.id)">
                        <div class="jb-chip__c">
                            <span x-text="window.jbEmoji(m.name)"></span>
                            <template x-if="menuThumb(m.id)"><img :src="menuThumb(m.id)" alt="" loading="lazy" onerror="this.style.display='none'" style="position:absolute;inset:0"></template>
                        </div>
                        <span class="jb-chip__t" x-text="m.name"></span>
                    </button>
                </template>
            </div>

            {{-- Promos --}}
            <div id="jb-promos" x-show="promoItems().length > 0 && !(search || '').trim()" x-cloak>
                <div class="jb-sec"><h3>@lang('modules.shop.promos') 🔥</h3></div>
                <div class="jb-promos">
                    <template x-for="item in promoItems()" :key="'jb-p-' + item.id">
                        @include('livewire.shop.partials.jimbe-card')
                    </template>
                </div>
            </div>

            {{-- Tous les plats --}}
            <div id="jb-menu" x-show="cfg.showMenu" x-cloak>
                <div class="jb-sec">
                    <h3 x-text="menuId === null ? @js(__('modules.shop.allDishes')) : ((menus.find(m => m.id === menuId) || {}).name || '')"></h3>
                    <span class="text-xs" style="color:#8a7f78" x-text="flatItems().length + ' ' + @js(__('modules.shop.dishes'))"></span>
                </div>
                <div class="jb-grid">
                    <template x-for="item in flatItems()" :key="'jb-i-' + item.id">
                        @include('livewire.shop.partials.jimbe-card')
                    </template>
                </div>
                <template x-if="flatItems().length === 0">
                    <div class="jb-empty" x-text="labels.noItemAdded"></div>
                </template>
            </div>

            {{-- Mini barre panier --}}
            <button type="button" class="jb-mini" x-show="cartCount() > 0" x-cloak x-transition @click="callLw('showCartItems')">
                <span><b x-text="cartCount()"></b> <span x-text="cartCount() > 1 ? @js(__('modules.shop.dishes')) : @js(__('modules.shop.dish'))"></span></span>
                <span>@lang('modules.shop.viewCart') →</span>
            </button>

            <script>
                window.jbEmoji = window.jbEmoji || function (text) {
                    const t = (text || '').toLowerCase();
                    const map = [
                        [/poulet|chicken|volaille|wing|aile/, '🍗'], [/burger|hamburger/, '🍔'], [/pizza/, '🍕'],
                        [/poisson|fish|crevette|shrimp|thon|capitaine/, '🐟'], [/salade|salad|legume|veg/, '🥗'],
                        [/riz|rice|biryani|attieke|attiéké|foutou|placali|alloco/, '🍚'], [/soupe|soup|sauce|ragout|ragoût/, '🍲'],
                        [/pain|naan|roti|bread|sandwich/, '🥖'], [/frite|fries|chips/, '🍟'], [/pates|pâtes|noodle|spaghetti/, '🍝'],
                        [/dessert|gateau|gâteau|cake|glace|ice/, '🍰'], [/cafe|café|coffee|the |thé/, '☕'],
                        [/boisson|drink|jus|juice|soda|eau|water|biere|bière|cocktail|bissap|gnamakoudji/, '🥤'],
                        [/viande|beef|boeuf|bœuf|steak|grill|brochette|mouton|porc/, '🥩'], [/oeuf|œuf|egg|omelette/, '🍳'],
                        [/fruit|mangue|ananas|banane/, '🍍'], [/entree|entrée|starter|spring|nem/, '🥟'],
                    ];
                    for (const [re, e] of map) { if (re.test(t)) return e; }
                    return '🍽️';
                };
            </script>

            {{-- Navigation du bas --}}
            <nav class="jb-nav" aria-label="Navigation">
                <button type="button" class="is-on" @click="window.scrollTo({top: 0, behavior: 'smooth'})">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    @lang('modules.shop.navHome')
                </button>
                <button type="button" @click="document.getElementById('jb-menu')?.scrollIntoView({behavior: 'smooth'})">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                    @lang('modules.shop.navMenu')
                </button>
                <button type="button" class="jb-nav__main" @click="callLw('showCartItems')">
                    <i>
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
                        <span class="jb-badge" x-show="cartCount() > 0" x-text="cartCount()"></span>
                    </i>
                    <span>@lang('modules.shop.navCart')</span>
                </button>
                <button type="button" @click="(document.getElementById('jb-promos') && promoItems().length ? document.getElementById('jb-promos') : document.getElementById('jb-menu'))?.scrollIntoView({behavior: 'smooth'})">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                    @lang('modules.shop.navPromos')
                </button>
                <button type="button" @click="document.querySelector('[wire\\:click=callWaiter]')?.click()">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    @lang('modules.shop.navWaiter')
                </button>
            </nav>
        </div>

        <div x-show="itemDetailOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 dark:bg-black/70"
            role="dialog" aria-modal="true" @keydown.escape.window="closeItemDetail()">
            <div class="flex flex-col w-full max-w-sm max-h-[85vh] overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-900"
                @click.stop>
                <div class="flex items-center justify-between flex-shrink-0 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="labels.itemDescription"></h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="closeItemDetail()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <template x-if="selectedPreviewItem">
                        <div class="flex flex-col flex-1 min-h-0 overflow-hidden">
                            <template x-if="!cfg.hideImages && selectedPreviewItem.item_photo_url">
                                <div class="flex w-full h-48 shrink-0 items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-800">
                                    <img :src="selectedPreviewItem.item_photo_url" :alt="selectedPreviewItem.item_name"
                                        class="max-h-48 max-w-full w-auto h-auto object-contain"
                                        loading="lazy"
                                        decoding="async" />
                                </div>
                            </template>
                            <template x-if="!cfg.hideImages && (selectedPreviewItem.extra_photo_urls || []).length">
                                <div class="flex gap-2 overflow-x-auto px-3 pt-2 shrink-0">
                                    <template x-for="u in selectedPreviewItem.extra_photo_urls" :key="u">
                                        <img :src="u" alt="" class="h-16 w-16 shrink-0 rounded-md object-cover cursor-pointer" loading="lazy"
                                            @click="selectedPreviewItem = { ...selectedPreviewItem, item_photo_url: u }" />
                                    </template>
                                </div>
                            </template>
                            <div class="flex flex-col flex-1 min-h-0 gap-2 px-4 pb-4 overflow-y-auto"
                                :class="(!cfg.hideImages && selectedPreviewItem.item_photo_url) ? 'pt-3' : 'pt-4'">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedPreviewItem.item_name"></h3>
                                    <template x-if="selectedPreviewItem.description">
                                        <div>
                                            <template x-if="String(selectedPreviewItem.description || '').length > 100">
                                                <div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        <span x-show="!itemDetailExpanded" x-text="truncate(selectedPreviewItem.description, 100)"></span>
                                                        <span x-show="itemDetailExpanded" x-text="selectedPreviewItem.description"></span>
                                                    </p>
                                                    <button type="button" class="mt-1 text-sm font-medium text-skin-base"
                                                        @click="itemDetailExpanded = !itemDetailExpanded"
                                                        x-text="itemDetailExpanded ? labels.showLess : labels.showMore"></button>
                                                </div>
                                            </template>
                                            <template x-if="String(selectedPreviewItem.description || '').length <= 100">
                                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedPreviewItem.description"></p>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="selectedPreviewItem.preparation_time">
                                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 shrink-0">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="labels.preparationTime + ' ' + (selectedPreviewItem.preparation_time >= 60 ? Math.floor(selectedPreviewItem.preparation_time / 60) + ' h' + (selectedPreviewItem.preparation_time % 60 ? ' ' + String(selectedPreviewItem.preparation_time % 60).padStart(2, '0') + ' min' : '') : selectedPreviewItem.preparation_time + ' ' + labels.minutes)"></span>
                                        </div>
                                    </template>
                                    <template x-if="cfg && cfg.euAllergensEnabled !== false && selectedPreviewItem.eu_allergens_display && selectedPreviewItem.eu_allergens_display.length">
                                        <div class="mt-2 rounded-lg border border-amber-200/90 bg-amber-50 px-3 py-2.5 dark:border-amber-700/60 dark:bg-amber-950/35"
                                            role="region"
                                            :aria-label="labels.allergensHeading">
                                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-900 dark:text-amber-200"
                                                x-text="labels.allergensHeading"></div>
                                            <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
                                                <template x-for="row in selectedPreviewItem.eu_allergens_display" :key="'eu-' + selectedPreviewItem.id + '-' + row.key">
                                                    <li class="flex items-center gap-3">
                                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center sm:h-10 sm:w-10" aria-hidden="true">
                                                            <img :src="row.icon" alt="" class="max-h-8 max-w-8 object-contain sm:max-h-9 sm:max-w-9" width="36" height="36" loading="lazy" />
                                                        </span>
                                                        <span class="flex-1 text-sm font-medium leading-normal text-gray-900 dark:text-gray-100" x-text="row.label"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="selectedPreviewItem.dietary_labels_display && selectedPreviewItem.dietary_labels_display.length">
                                        <div class="mt-2 rounded-lg border border-emerald-200/90 bg-emerald-50 px-3 py-2.5 dark:border-emerald-700/60 dark:bg-emerald-950/35"
                                            role="region"
                                            :aria-label="labels.dietaryLabelsHeading">
                                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-emerald-900 dark:text-emerald-200"
                                                x-text="labels.dietaryLabelsHeading"></div>
                                            <ul class="m-0 flex list-none flex-col gap-2.5 p-0">
                                                <template x-for="row in selectedPreviewItem.dietary_labels_display" :key="'diet-' + selectedPreviewItem.id + '-' + row.key">
                                                    <li class="flex items-center gap-3">
                                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center sm:h-10 sm:w-10" aria-hidden="true">
                                                            <img :src="row.icon" alt="" class="max-h-8 max-w-8 object-contain sm:max-h-9 sm:max-w-9" width="36" height="36" loading="lazy" />
                                                        </span>
                                                        <span class="flex-1 text-sm font-medium leading-normal text-gray-900 dark:text-gray-100" x-text="row.label"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                    <p class="text-base font-semibold text-gray-900 dark:text-white">
                                        <span x-text="itemPriceLabel(selectedPreviewItem)"></span>
                                        <span x-show="selectedPreviewItem.original_price_label" class="ml-1 text-sm font-normal text-gray-400 line-through" x-text="selectedPreviewItem.original_price_label"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end flex-shrink-0 px-4 py-3 space-x-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <button type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm bg-skin-base hover:opacity-90"
                        x-show="selectedPreviewItem && cfg.canCreateOrder && cfg.allowCustomerOrders && selectedPreviewItem.in_stock"
                        @click="browseCartMutate('add', selectedPreviewItem.id, selectedPreviewItem.variations_count || 0, selectedPreviewItem.modifier_groups_count || 0); closeItemDetail()"
                        x-text="labels.add"></button>
                    <button type="button" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                        @click="closeItemDetail()"
                        x-text="labels.cancel || 'Cancel'"></button>
                </div>
            </div>
        </div>
    </div>
</div>
