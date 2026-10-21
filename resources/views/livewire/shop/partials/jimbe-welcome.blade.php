{{-- Page d'accueil du QR code : photo du restaurant + numéro de table + bouton « Voir le menu ».
     Personnalisable dans Paramètres → Page d'accueil client. --}}
@php
    $jbCover = !empty($restaurant->welcome_cover) ? asset_url_local_s3($restaurant->welcome_cover) : null;
    $jbTitle = $restaurant->welcome_title ?: __('modules.shop.welcomeAt', ['name' => $restaurant->name]);
    $jbSub = $restaurant->welcome_subtitle ?: __('modules.shop.w1Sub');
    $jbBtn = $restaurant->welcome_button_text ?: __('modules.shop.seeMenu');
    $jbTableWord = $restaurant->welcome_table_label ?: __('modules.shop.tableWord');
    $jbTableCode = !empty($table?->table_code) ? $table->table_code : null;
    $jbTableText = $jbTableCode
        ? (\Illuminate\Support\Str::startsWith(mb_strtolower($jbTableCode), mb_strtolower($jbTableWord)) ? $jbTableCode : $jbTableWord . ' ' . $jbTableCode)
        : null;
@endphp

<script>
    window.jbEmoji = window.jbEmoji || function (text) {
        const t = (text || '').toLowerCase();
        const map = [
            [/poulet|chicken|volaille|wing|aile/, '🍗'], [/burger|hamburger/, '🍔'], [/pizza/, '🍕'],
            [/poisson|fish|crevette|shrimp|thon|capitaine/, '🐟'], [/salade|salad|legume|légume|veg/, '🥗'],
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

<div class="jb-welcome" x-cloak
    x-data="{
        show: false,
        key: 'jbWelcome:' + @js($jbTableCode ?? 'all'),
        init() {
            if (new URLSearchParams(location.search).has('jb')) return;
            try { this.show = !sessionStorage.getItem(this.key); } catch (e) { this.show = true; }
        },
        go() { try { sessionStorage.setItem(this.key, '1'); } catch (e) {} this.show = false; window.scrollTo(0, 0); },
    }"
    x-show="show" x-transition.opacity.duration.300ms>

    <div class="jb-welcome__top {{ $jbCover ? 'has-cover' : '' }}"
        @if ($jbCover) style="background-image: linear-gradient(to top, rgba(0,0,0,.35), rgba(0,0,0,0) 55%), url('{{ $jbCover }}');" @endif>
        <span class="jb-welcome__blob b1"></span><span class="jb-welcome__blob b2"></span>

        <div class="jb-welcome__brand">
            <div class="jb-welcome__logo-sm"><x-restaurant-logo :restaurant="$restaurant" class="h-8" /></div>
            <span>{{ $restaurant->name }}</span>
        </div>

        @unless ($jbCover)
            <div class="jb-welcome__stage">
                <div class="jb-welcome__plate">
                    <div class="jb-welcome__logo"><x-restaurant-logo :restaurant="$restaurant" class="h-14" /></div>
                </div>
                <span class="jb-welcome__food f1">🍗</span>
                <span class="jb-welcome__food f2">🥗</span>
                <span class="jb-welcome__food f3">🍲</span>
                <span class="jb-welcome__food f4">🥤</span>
                <span class="jb-welcome__food f5">🍰</span>
            </div>
        @endunless
    </div>

    <div class="jb-welcome__body">
        @if ($jbTableCode)
            <div class="jb-welcome__table">
                <span class="jb-welcome__table-i">🪑</span>
                <b>{{ $jbTableText }}</b>
            </div>
        @endif

        <h1 class="jb-welcome__title">{{ $jbTitle }}</h1>
        <p class="jb-welcome__sub">{{ $jbSub }}</p>

        <button type="button" class="jb-welcome__cta" @click="go()">{{ $jbBtn }}</button>
    </div>
</div>
