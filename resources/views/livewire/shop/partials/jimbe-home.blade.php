@php
    $jbBanners = \App\Models\RestaurantBanner::forRestaurant($restaurant->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    $jbHeroB = $jbBanners->where('position', 'hero')->values();
    $jbMidB = $jbBanners->where('position', 'middle')->first();
    // Action déclenchée par le bouton d'une bannière (expression Alpine)
    $jbAct = function ($b) {
        $id = (int) $b->target_id;
        return match ($b->target_type) {
            'dish' => $id ? "openItemDetail($id)" : "setBadgeFilter(null); go('menu')",
            'promo' => "setBadgeFilter('promo'); go('menu')",
            'call' => "\$dispatch('jb-call-open')",
            'cart' => "callLw('showCartItems')",
            'category' => $id
                ? "setBadgeFilter(null); go('menu'); \$nextTick(() => { const gi = groupedForDisplay().findIndex(g => g.items.length && Number(g.items[0].item_category_id) === $id); setTimeout(() => document.getElementById('jb-sec-' + gi)?.scrollIntoView({behavior: 'smooth', block: 'start'}), 120); })"
                : "setBadgeFilter(null); go('menu')",
            default => "setBadgeFilter(null); go('menu')",
        };
    };
@endphp

{{-- Page menu façon « app de commande » : en-tête, recherche + filtre, bannière à diapositives,
     catégories rondes, plats populaires, seconde bannière, puis toute la carte. Scope Alpine : ttShopClientMenuBrowse --}}
@php
    $jbHeroCover = !empty($restaurant->welcome_cover) ? asset_url_local_s3($restaurant->welcome_cover) : null;
    $jbHeroTitle = trim((string) $restaurant->welcome_title);
    $jbTagline = $restaurant->welcome_subtitle ?: __('modules.shop.heroSub');
    $jbTableTxt = null;
    if (!empty($table?->table_code)) {
        $w = $restaurant->welcome_table_label ?: __('modules.shop.tableWord');
        $jbTableTxt = \Illuminate\Support\Str::startsWith(mb_strtolower($table->table_code), mb_strtolower($w)) ? $table->table_code : $w . ' ' . $table->table_code;
    }
@endphp

{{-- ===== En-tête ===== --}}
<div class="jb-hm-top">
    <div class="jb-hm-top__logo"><x-restaurant-logo :restaurant="$restaurant" class="h-8" /></div>
    <div class="jb-hm-top__txt">
        <div class="jb-hm-top__hello" x-text="(new Date().getHours() < 12 ? @js(__('modules.shop.goodMorning')) : (new Date().getHours() < 18 ? @js(__('modules.shop.goodAfternoon')) : @js(__('modules.shop.goodEvening')))) + ' 👋'"></div>
        <div class="jb-hm-top__name">{{ $restaurant->name }}</div>
        <div class="jb-hm-top__tag">@if ($jbTableTxt)<b>🪑 {{ $jbTableTxt }}</b> · @endif{{ $jbTagline }}</div>
    </div>
    <div class="jb-hm-top__act">
        @if ($jbCanCall)
            <button type="button" class="jb-ibtn jb-ibtn--bell" aria-label="@lang('app.callWaiter')" @click="$dispatch('jb-call-open')">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            </button>
        @endif
        <button type="button" class="jb-ibtn" id="jb-cart-top" aria-label="@lang('modules.order.viewCart')" @click="callLw('showCartItems')">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
            <span class="jb-badge" x-show="cartCount() > 0" x-text="cartCount()"></span>
        </button>
    </div>
</div>

{{-- ===== Recherche + filtre ===== --}}
@include('livewire.shop.partials.jimbe-searchrow', ['jbSearchGoesToMenu' => true])

{{-- ===== Bannière à diapositives ===== --}}
@if ($jbHeroB->count())
    <div class="jb-hm-hero" id="jb-hero"
        x-data="{
            i: 0, timer: null, sx: 0, n: {{ $jbHeroB->count() }},
            slides() { return this.n; },
            start() { this.stop(); if (this.n > 1) { this.timer = setInterval(() => { this.i = (this.i + 1) % this.n; }, 5200); } },
            stop() { if (this.timer) clearInterval(this.timer); },
            go2(k) { this.i = k; this.start(); },
            swipe(dx) { if (this.n > 1 && Math.abs(dx) > 40) { this.i = (this.i + (dx < 0 ? 1 : -1) + this.n) % this.n; this.start(); } },
        }" x-init="start()"
        @touchstart.passive="sx = $event.touches[0].clientX" @touchend.passive="swipe($event.changedTouches[0].clientX - sx)">

        @foreach ($jbHeroB as $k => $b)
            @php
                $hasText = $b->title || $b->subtitle || $b->button_text;
                $bg = $b->image_url
                    ? ($hasText ? "linear-gradient(95deg, rgba(8,20,18,.82) 6%, rgba(8,20,18,.45) 55%, rgba(8,20,18,.05)), " : '') . "url('" . $b->image_url . "')"
                    : null;
            @endphp
            <div class="jb-hm-slide jb-hm-slide--img {{ $b->image_url ? '' : 'jb-hm-slide--a' }}" :class="i === {{ $k }} ? 'is-on' : ''"
                @if ($bg) style="background-image: {{ $bg }};" @endif
                @unless ($b->button_text) @click="{{ $jbAct($b) }}" style="cursor:pointer;{{ $bg ? 'background-image: ' . $bg . ';' : '' }}" @endunless>
                @if ($hasText)
                    <div class="jb-hm-slide__txt">
                        @if ($b->title) <h2>{{ $b->title }}</h2> @endif
                        @if ($b->subtitle) <p>{{ $b->subtitle }}</p> @endif
                        @if ($b->button_text)
                            <button type="button" class="jb-hm-cta" @click.stop="{{ $jbAct($b) }}">{{ $b->button_text }} <span>→</span></button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

        @if ($jbHeroB->count() > 1)
            <div class="jb-hm-dots">
                <template x-for="k in slides()" :key="k">
                    <button type="button" :class="i === k - 1 ? 'is-on' : ''" @click="go2(k - 1)" :aria-label="'Slide ' + k"></button>
                </template>
            </div>
        @endif
    </div>
@else
<div class="jb-hm-hero" id="jb-hero"
    x-data="{
        i: 0, timer: null, sx: 0,
        slides() { return 2 + (promoItems().length > 0 ? 1 : 0); },
        start() { this.stop(); this.timer = setInterval(() => { this.i = (this.i + 1) % this.slides(); }, 5200); },
        stop() { if (this.timer) clearInterval(this.timer); },
        go(k) { this.i = k; this.start(); },
        swipe(dx) { if (Math.abs(dx) > 40) { this.i = (this.i + (dx < 0 ? 1 : -1) + this.slides()) % this.slides(); this.start(); } },
    }" x-init="start()"
    @touchstart.passive="sx = $event.touches[0].clientX" @touchend.passive="swipe($event.changedTouches[0].clientX - sx)">

    {{-- 1 : bienvenue --}}
    <div class="jb-hm-slide jb-hm-slide--a" :class="i === 0 ? 'is-on' : ''"
        @if ($jbHeroCover) style="background-image: linear-gradient(95deg, rgba(8,28,26,.93) 8%, rgba(8,28,26,.55) 55%, rgba(8,28,26,.15)), url('{{ $jbHeroCover }}');" @endif>
        <div class="jb-hm-slide__txt">
            <h2>
                @if ($jbHeroTitle !== '') {{ $jbHeroTitle }} @else @lang('modules.shop.hm1a')<br>@lang('modules.shop.hm1b') @endif
                <em>@lang('modules.shop.hm1c')</em>
            </h2>
            <p>@lang('modules.shop.hm1sub')</p>
            <button type="button" class="jb-hm-cta" @click="go('menu')">@lang('modules.shop.orderBtn') <span>→</span></button>
        </div>
        @unless ($jbHeroCover)
            <div class="jb-hm-slide__art"><span class="a1">🍗</span><span class="a2">🥗</span><span class="a3">🍲</span></div>
        @endunless
    </div>

    {{-- 2 : promos (si il y en a) --}}
    <div class="jb-hm-slide jb-hm-slide--b" :class="i === 1 && promoItems().length > 0 ? 'is-on' : ''" x-show="promoItems().length > 0" x-cloak>
        <div class="jb-hm-slide__txt">
            <h2>@lang('modules.shop.hm2a')<br>@lang('modules.shop.hm2b') <em x-text="'-' + (Math.max(...promoItems().map(p => Number(p.discount_percent || 0)), 0) || '') + '%'"></em></h2>
            <p>@lang('modules.shop.hm2sub')</p>
            <button type="button" class="jb-hm-cta" @click="setBadgeFilter('promo'); go('menu')">@lang('modules.shop.promoCta') <span>→</span></button>
        </div>
        <div class="jb-hm-slide__art"><span class="a1">🏷️</span><span class="a2">🍕</span><span class="a3">🍰</span></div>
    </div>

    {{-- 3 : appeler un serveur --}}
    <div class="jb-hm-slide jb-hm-slide--c" :class="i === (promoItems().length > 0 ? 2 : 1) ? 'is-on' : ''">
        <div class="jb-hm-slide__txt">
            <h2>@lang('modules.shop.hm3a')<br>@lang('modules.shop.hm3b') <em>@lang('modules.shop.hm3c')</em></h2>
            <p>@lang('modules.shop.hm3sub')</p>
            @if ($jbCanCall)
                <button type="button" class="jb-hm-cta" @click="$dispatch('jb-call-open')">@lang('modules.waiterRequest.callTitle') <span>🔔</span></button>
            @else
                <button type="button" class="jb-hm-cta" @click="go('menu')">@lang('modules.shop.heroCta') <span>→</span></button>
            @endif
        </div>
        <div class="jb-hm-slide__art"><span class="a1">🙋</span><span class="a2">🥤</span><span class="a3">🧾</span></div>
    </div>

    <div class="jb-hm-dots">
        <template x-for="k in slides()" :key="k">
            <button type="button" :class="i === k - 1 ? 'is-on' : ''" @click="go(k - 1)" :aria-label="'Slide ' + k"></button>
        </template>
    </div>
</div>
@endif

{{-- ===== Catégories rondes ===== --}}
<div class="jb-hm-cats" x-show="visibleCategories().length > 1">
    <button type="button" class="jb-hm-cat" @click="setBadgeFilter(null); go('menu')">
        <span class="jb-hm-cat__c">🍽️</span><span class="jb-hm-cat__t">@lang('modules.shop.all')</span>
    </button>
    <template x-for="c in visibleCategories()" :key="'jb-hc-' + c.id">
        <button type="button" class="jb-hm-cat" @click="setBadgeFilter(null); go('menu'); $nextTick(() => { const gi = groupedForDisplay().findIndex(g => g.items.length && Number(g.items[0].item_category_id) === Number(c.id)); setTimeout(() => document.getElementById('jb-sec-' + gi)?.scrollIntoView({behavior: 'smooth', block: 'start'}), 120); })">
            <span class="jb-hm-cat__c">
                <b x-text="window.jbEmoji(c.name)"></b>
                <template x-if="catThumb(c.id)"><img :src="catThumb(c.id)" alt="" loading="lazy" x-on:error="$el.style.display='none'"></template>
            </span>
            <span class="jb-hm-cat__t" x-text="c.name"></span>
        </button>
    </template>
</div>

{{-- ===== Sections de plats (défilement horizontal) ===== --}}
@php
    $jbSections = [
        ['fn' => 'popularItems', 'id' => 'jb-popular', 'emoji' => '🔥', 'title' => 'popularPicks', 'sub' => null],
        ['fn' => 'cheapItems', 'id' => 'jb-cheap', 'emoji' => '💸', 'title' => 'cheapPicks', 'sub' => 'cheapSub'],
    ];
@endphp

@foreach ($jbSections as $sec)
    <div id="{{ $sec['id'] }}" x-show="{{ $sec['fn'] }}().length > 0 && !(search || '').trim()" x-cloak>
        <div class="jb-hm-sec">
            <h3>{{ $sec['emoji'] }} @lang('modules.shop.' . $sec['title'])</h3>
            <button type="button" @click="go('menu')">@lang('modules.shop.viewAll') →</button>
        </div>
        @if ($sec['sub'])
            <p class="jb-hm-sec__sub">@lang('modules.shop.' . $sec['sub'])</p>
        @endif
        <div class="jb-hm-picks">
            <template x-for="item in {{ $sec['fn'] }}()" :key="'{{ $sec['id'] }}-' + item.id">
                @include('livewire.shop.partials.jimbe-pick')
            </template>
        </div>
    </div>
@endforeach

{{-- ===== Seconde bannière ===== --}}
@if ($jbMidB)
    @php
        $midHasText = $jbMidB->title || $jbMidB->subtitle || $jbMidB->button_text;
        $midBg = $jbMidB->image_url
            ? ($midHasText ? "linear-gradient(95deg, rgba(8,20,18,.82) 6%, rgba(8,20,18,.4) 58%, rgba(8,20,18,.05)), " : '') . "url('" . $jbMidB->image_url . "')"
            : null;
    @endphp
    <div class="jb-hm-banner jb-hm-banner--img" x-show="!(search || '').trim()" x-cloak
        style="{{ $midBg ? 'background-image: ' . $midBg . ';' : '' }}{{ $jbMidB->button_text ? '' : 'cursor:pointer;' }}"
        @unless ($jbMidB->button_text) @click="{{ $jbAct($jbMidB) }}" @endunless>
        @if ($midHasText)
            <div class="jb-hm-banner__txt">
                @if ($jbMidB->title) <h3>{{ $jbMidB->title }}</h3> @endif
                @if ($jbMidB->subtitle) <p>{{ $jbMidB->subtitle }}</p> @endif
                @if ($jbMidB->button_text)
                    <button type="button" class="jb-hm-cta jb-hm-cta--dark" @click.stop="{{ $jbAct($jbMidB) }}">{{ $jbMidB->button_text }} <span>→</span></button>
                @endif
            </div>
        @endif
    </div>
@else
<div class="jb-hm-banner" x-show="!(search || '').trim()" x-cloak
    :class="promoItems().length > 0 ? 'is-promo' : ''">
    <div class="jb-hm-banner__txt">
        <small x-text="promoItems().length > 0 ? @js(__('modules.shop.exclusive')) : @js(__('modules.shop.ourMenu'))"></small>
        <h3 x-text="promoItems().length > 0 ? @js(__('modules.shop.upTo')) + ' ' + (Math.max(...promoItems().map(p => Number(p.discount_percent || 0)), 0)) + '% ' + @js(__('modules.shop.off')) : @js(__('modules.shop.fullMenu'))"></h3>
        <p x-text="promoItems().length > 0 ? @js(__('modules.shop.onSelected')) : @js(__('modules.shop.fullMenuSub'))"></p>
        <button type="button" class="jb-hm-cta jb-hm-cta--dark" @click="promoItems().length > 0 ? setBadgeFilter('promo') : null; go('menu')">@lang('modules.shop.orderBtn') <span>→</span></button>
    </div>
    <div class="jb-hm-banner__art"><span class="a1">🍔</span><span class="a2">🥤</span><span class="a3">🍟</span></div>
    <div class="jb-hm-banner__seal" x-show="promoItems().length > 0"><b x-text="Math.max(...promoItems().map(p => Number(p.discount_percent || 0)), 0) + '%'"></b><small>@lang('modules.shop.offShort')</small></div>
</div>
@endif

@php
    $jbSections2 = [
        ['fn' => 'newItems', 'id' => 'jb-new', 'emoji' => '✨', 'title' => 'newPicks', 'sub' => 'newSub'],
        ['fn' => 'quickItems', 'id' => 'jb-quick', 'emoji' => '⚡', 'title' => 'quickPicks', 'sub' => 'quickSub'],
    ];
@endphp

@foreach ($jbSections2 as $sec)
    <div id="{{ $sec['id'] }}" x-show="{{ $sec['fn'] }}().length > 0 && !(search || '').trim()" x-cloak>
        <div class="jb-hm-sec">
            <h3>{{ $sec['emoji'] }} @lang('modules.shop.' . $sec['title'])</h3>
            <button type="button" @click="go('menu')">@lang('modules.shop.viewAll') →</button>
        </div>
        <p class="jb-hm-sec__sub">@lang('modules.shop.' . $sec['sub'])</p>
        <div class="jb-hm-picks">
            <template x-for="item in {{ $sec['fn'] }}()" :key="'{{ $sec['id'] }}-' + item.id">
                @include('livewire.shop.partials.jimbe-pick')
            </template>
        </div>
    </div>
@endforeach

{{-- Accès direct à toute la carte --}}
<button type="button" class="jb-hm-allbtn" @click="go('menu')">
    <span>📖</span> @lang('modules.shop.seeFullMenu') <i>→</i>
</button>
