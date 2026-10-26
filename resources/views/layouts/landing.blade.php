<!DOCTYPE html>
<html lang="{{ session('customer_locale') ?? global_setting()->locale }}" dir="{{ session('customer_is_rtl') ? 'rtl' : 'ltr' }}">

<head>
    <link rel="manifest" href="{{ asset('manifest.json') }}" crossorigin="use-credentials">
    <meta name="theme-color" content="#ffffff">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- FAVICONS --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ global_setting()->upload_fav_icon_apple_touch_icon_url }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ global_setting()->upload_fav_icon_android_chrome_192_url }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ global_setting()->upload_fav_icon_android_chrome_512_url }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ global_setting()->upload_fav_icon_16_url }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ global_setting()->upload_fav_icon_32_url }}">
    <link rel="shortcut icon" href="{{ global_setting()->favicon_url }}">


    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ global_setting()->logoUrl }}">

    {{-- META TAGS --}}
    <meta name="keywords" content="{{ global_setting()->meta_keyword ?? global_setting()->name }}">
    <meta name="description" content="{{ global_setting()->meta_description ?? global_setting()->name }}">

    @php
        $landingMetaTitle = global_setting()->meta_title ?? global_setting()->name;
        $landingMetaImage = global_setting()->meta_image_url ?? global_setting()->upload_fav_icon_android_chrome_512_url;
    @endphp
    <meta property="og:title" content="{{ $landingMetaTitle }}">
    <meta property="og:image" content="{{ $landingMetaImage }}">
    <meta property="og:image:alt" content="{{ $landingMetaTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $landingMetaTitle }}">
    <meta name="twitter:image" content="{{ $landingMetaImage }}">

    <title>{{ global_setting()->meta_title ?? global_setting()->name }}</title>

    {{-- Polices de la charte Jimbe --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arima+Madurai:wght@400;500;700&family=Baloo+Chettan+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles

    @include('sections.theme_style', [
        'baseColor' => global_setting()->theme_rgb,
        'baseColorHex' => global_setting()->theme_hex,
    ])

    <link href="{{ asset('css/jimbe-landing.css') }}?v={{ filemtime(public_path('css/jimbe-landing.css')) }}" rel="stylesheet">

    @if (File::exists(public_path() . '/css/app-custom.css'))
    <link href="{{ asset('css/app-custom.css') }}" rel="stylesheet">
    @endif

    {{-- Include file for widgets if exist --}}
    @includeIf('sections.custom_script_landing')
</head>

<body class="jb-body">
    @include('sections.offline-banner')

    @php
        $customMenu = App\Models\CustomMenu::orderBy('sort_order')->get();
        $headerMenus = $customMenu->filter(fn ($m) => $m->is_active && $m->position == 'header');
        $footerMenus = $customMenu->filter(fn ($m) => $m->is_active && $m->position == 'footer');
    @endphp

    <header class="jb-header">
        <nav class="jb-nav" aria-label="Navigation principale">
            <a href="{{ url('/') }}" class="jb-nav__logo" aria-label="Jimbe, accueil">
                <img src="{{ asset('landing/brand/jimbe-logo.png') }}" alt="Jimbe" width="150" height="40">
            </a>

            <ul class="jb-nav__links">
                <li><a href="{{ url('/') }}">{{ __('menu.home') }}</a></li>
                <li><a href="{{ url('/') }}#icon-features">{{ __('landing.features') }}</a></li>
                <li><a href="{{ url('/') }}#simple-pricing">{{ __('landing.pricing') }}</a></li>
                <li><a href="{{ url('/') }}#user-faqs">{{ __('landing.faq') }}</a></li>
                <li><a href="{{ url('/') }}#contact">{{ __('landing.contactTitle') }}</a></li>
                @foreach ($headerMenus as $menu)
                    <li><a href="{{ route('customMenu', ['slug' => $menu->menu_slug]) }}">{{ $menu->menu_name }}</a></li>
                @endforeach
            </ul>

            <div class="jb-nav__actions">
                @php
                    $currentLocale = session('customer_locale') ?? session('locale') ?? global_setting()->locale ?? 'fr';
                @endphp
                <div class="jb-lang-switch" role="group" aria-label="Langue">
                    <a href="{{ route('change.locale', 'fr') }}" class="jb-lang-btn {{ $currentLocale === 'fr' ? 'is-active' : '' }}" title="Passer en français">🇫🇷 FR</a>
                    <span class="jb-lang-sep">/</span>
                    <a href="{{ route('change.locale', 'en') }}" class="jb-lang-btn {{ $currentLocale === 'en' ? 'is-active' : '' }}" title="Switch to English">🇬🇧 EN</a>
                </div>

                <a href="{{ route('login') }}" class="jb-nav__login">{{ user() ? __('menu.dashboard') : __('app.login') }}</a>
                @if (!user())
                    <a href="{{ route('restaurant_signup') }}" class="jb-btn jb-btn--dark jb-btn--sm">{{ __('landing.getStarted') }}</a>
                @endif
                <button type="button" class="jb-nav__burger" id="jb-burger" aria-expanded="false" aria-controls="jb-panel" aria-label="Ouvrir le menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>
            </div>

            <div class="jb-nav__panel" id="jb-panel">
                <div class="jb-lang-switch mb-3" style="width: fit-content;" role="group" aria-label="Langue">
                    <a href="{{ route('change.locale', 'fr') }}" class="jb-lang-btn {{ $currentLocale === 'fr' ? 'is-active' : '' }}">🇫🇷 FR</a>
                    <span class="jb-lang-sep">/</span>
                    <a href="{{ route('change.locale', 'en') }}" class="jb-lang-btn {{ $currentLocale === 'en' ? 'is-active' : '' }}">🇬🇧 EN</a>
                </div>
                <a href="{{ url('/') }}">{{ __('menu.home') }}</a>
                <a href="{{ url('/') }}#icon-features">{{ __('landing.features') }}</a>
                <a href="{{ url('/') }}#simple-pricing">{{ __('landing.pricing') }}</a>
                <a href="{{ url('/') }}#user-faqs">{{ __('landing.faq') }}</a>
                <a href="{{ url('/') }}#contact">{{ __('landing.contactTitle') }}</a>
                @foreach ($headerMenus as $menu)
                    <a href="{{ route('customMenu', ['slug' => $menu->menu_slug]) }}">{{ $menu->menu_name }}</a>
                @endforeach
                <a href="{{ route('login') }}">{{ user() ? __('menu.dashboard') : __('app.login') }}</a>
                @if (!user())
                    <a href="{{ route('restaurant_signup') }}" class="jb-btn jb-btn--primary">{{ __('landing.getStarted') }}</a>
                @endif
            </div>
        </nav>
    </header>

    <main id="main-content">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    @stack('modals')

    <footer class="jb-footer">
        <div class="jb-container">
            <div class="jb-footer__top">
                <div>
                    <img class="jb-footer__logo" src="{{ asset('landing/brand/jimbe-wordmark-light.png') }}" alt="Jimbe">
                    <p class="jb-footer__text">
                        Jimbe est la plateforme tout-en-un pour les restaurants : menu QR, commandes, réservations et caisse réunis au même endroit. Simple pour vous. Meilleur pour vos clients.
                    </p>
                </div>
                <div class="jb-footer__cta">
                    <h3>Prêt à simplifier votre service ?</h3>
                    <a href="{{ route('restaurant_signup') }}" class="jb-btn jb-btn--primary">
                        Créer mon restaurant
                        <span class="jb-btn__arrow"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></span>
                    </a>
                </div>
            </div>

            <div class="jb-footer__cols">
                <ul>
                    <li><a href="{{ url('/') }}">{{ __('menu.home') }}</a></li>
                    <li><a href="{{ url('/') }}#icon-features">{{ __('landing.features') }}</a></li>
                </ul>
                <ul>
                    <li><a href="{{ url('/') }}#simple-pricing">{{ __('landing.pricing') }}</a></li>
                    <li><a href="{{ url('/') }}#user-faqs">{{ __('landing.faq') }}</a></li>
                </ul>
                <ul>
                    <li><a href="{{ url('/') }}#contact">{{ __('landing.contactTitle') }}</a></li>
                    @foreach ($footerMenus as $menu)
                        <li><a href="{{ route('customMenu', ['slug' => $menu->menu_slug]) }}">{{ $menu->menu_name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="jb-footer__bottom">
                <span>
                    <span dir="ltr">© {{ now()->year }} Jimbe.</span>
                    @lang('landing.rightsReserved')
                </span>
                <div class="jb-social">
                    @if (languages()->count() > 1)
                        @livewire('shop.languageSwitcher')
                    @endif

                    @if (global_setting()->facebook_link)
                        <a href="{{ global_setting()->facebook_link }}" aria-label="Facebook" rel="noopener">
                            <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif

                    @if (global_setting()->instagram_link)
                        <a href="{{ global_setting()->instagram_link }}" aria-label="Instagram" rel="noopener">
                            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" /></svg>
                        </a>
                    @endif

                    @if (global_setting()->twitter_link)
                        <a href="{{ global_setting()->twitter_link }}" aria-label="X" rel="noopener">
                            <svg fill="currentColor" viewBox="0 0 50 50" aria-hidden="true"><path d="M 6.9199219 6 L 21.136719 26.726562 L 6.2285156 44 L 9.40625 44 L 22.544922 28.777344 L 32.986328 44 L 43 44 L 28.123047 22.3125 L 42.203125 6 L 39.027344 6 L 26.716797 20.261719 L 16.933594 6 L 6.9199219 6 z" /></svg>
                        </a>
                    @endif

                    @if (global_setting()->yelp_link)
                        <a href="{{ global_setting()->yelp_link }}" aria-label="Yelp" rel="noopener">
                            <svg fill="currentColor" viewBox="0 0 30 30" aria-hidden="true"><path d="M13.961 22.279c0.246-0.273 0.601-0.444 0.995-0.444 0.739 0 1.338 0.599 1.338 1.338 0 0.016-0 0.032-0.001 0.048l0-0.002-0.237 6.483c-0.027 0.719-0.616 1.293-1.34 1.293-0.077 0-0.153-0.006-0.226-0.019l0.008 0.001c-1.763-0.303-3.331-0.962-4.69-1.902l0.039 0.025c-0.351-0.245-0.578-0.647-0.578-1.102 0-0.346 0.131-0.661 0.346-0.898l-0.001 0.001 4.345-4.829z" /></svg>
                        </a>
                    @endif

                    @if (global_setting()->google_business_link)
                        <a href="{{ global_setting()->google_business_link }}" aria-label="Google" rel="noopener">
                            <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.35 11.1h-9.3v2.95h5.43c-.235 1.34-1.22 3.925-5.43 3.925-3.27 0-5.94-2.705-5.94-6.05s2.67-6.05 5.94-6.05c1.87 0 3.12.8 3.84 1.48l2.62-2.52C17.65 2.3 15.84 1.4 13.5 1.4 7.68 1.4 3 5.92 3 11.4s4.68 10 10.5 10c5.97 0 9.9-4.22 9.9-10 0-.67-.08-1.33-.05-1.7z" /></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts

    @include('layouts.update-uri')

    @include('layouts.service-worker-js')

    @include('sections.pusher-script')

    <x-livewire-alert::flash />

    <script>
        (function () {
            var burger = document.getElementById('jb-burger');
            var panel = document.getElementById('jb-panel');
            if (!burger || !panel) { return; }
            burger.addEventListener('click', function () {
                var open = panel.classList.toggle('is-open');
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            panel.addEventListener('click', function (e) {
                if (e.target.closest('a')) { panel.classList.remove('is-open'); burger.setAttribute('aria-expanded', 'false'); }
            });
        })();
    </script>

    @stack('scripts')
</body>

</html>
