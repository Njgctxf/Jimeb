<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Jimbe – Simple pour vous. Meilleur pour vos clients.')</title>
    <meta name="description" content="@yield('description', 'Jimbe réunit menu QR, commandes, réservations et caisse dans une seule plateforme pour les restaurants.')">
    <meta name="robots" content="noindex">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/framer-copy.css') }}?v={{ filemtime(public_path('css/framer-copy.css')) }}" rel="stylesheet">
</head>

<body class="fm">
    @php
        $fa = fn (string $file) => asset('landing/framer/' . $file);
        $arrowUpRight = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';
        $home = url('/');
        $customMenu = $customMenu ?? collect();
        $footerMenus = $customMenu->filter(fn ($m) => $m->is_active && $m->position == 'footer');
        $gs = global_setting();
    @endphp

    <header class="fm-header">
        <div class="fm-header__bar">
            <a class="fm-header__logo" href="{{ $home }}" aria-label="Accueil">
                <img src="{{ $fa('qJ79a7uh7BDpTYRCcBzM6dJ5QYk.svg') }}" alt="Mobilo" width="130" height="32">
            </a>

            <nav class="fm-nav" aria-label="Navigation principale">
                <a href="{{ $home }}#features">Fonctionnalités</a>
                <a href="{{ $home }}#how">Comment ça marche</a>
                <a href="{{ $home }}#pricing">Tarifs</a>
                <a href="{{ $home }}#faq">FAQ</a>
            </nav>

            <div class="fm-header__actions">
                <a href="{{ route('login') }}" class="fm-header__login">Connexion</a>
                <a href="{{ route('restaurant_signup') }}" class="fm-btn">Commencer</a>
                <button type="button" class="fm-burger" id="fm-burger" aria-expanded="false" aria-controls="fm-panel" aria-label="Ouvrir le menu">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>
            </div>

            <div class="fm-panel" id="fm-panel">
                <a href="{{ $home }}#features">Fonctionnalités</a>
                <a href="{{ $home }}#how">Comment ça marche</a>
                <a href="{{ $home }}#pricing">Tarifs</a>
                <a href="{{ $home }}#faq">FAQ</a>
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ route('restaurant_signup') }}" class="fm-btn fm-btn--primary" style="justify-content:center">Commencer</a>
            </div>
        </div>
    </header>

    <div class="fm-page">
        @yield('content')

        <footer class="fm-footer" id="footer">
            <img class="fm-footer__bg" src="{{ $fa('lUXkjPHYIkhxTA6EKaah1icpg.png') }}" alt="" aria-hidden="true">

            <div class="fm-footer__top">
                <div class="fm-footer__brand">
                    <img src="{{ $fa('ecnC3SLFLuiRCRwPccplmpDSY.png') }}" alt="Mobilo" width="211" height="52">
                    <p>Jimbe est la plateforme tout-en-un des restaurants : menu QR, commandes, réservations et caisse réunis au même endroit. Simple pour vous. Meilleur pour vos clients.</p>
                </div>

                <div class="fm-news">
                    <p class="t20m" style="color:#fff">Une question ? Parlons-en.</p>
                    <p>Notre équipe vous répond et vous aide à démarrer avec votre restaurant.</p>
                    <div>
                        <a href="{{ $home }}#contact" class="fm-btn fm-btn--light">Nous contacter <span class="fm-btn__icon">{!! $arrowUpRight !!}</span></a>
                    </div>
                </div>
            </div>

            <nav class="fm-footer__links" aria-label="Pied de page">
                <a href="{{ $home }}">Accueil</a>
                <a href="{{ $home }}#features">Fonctionnalités</a>
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ $home }}#how">Comment ça marche</a>
                <a href="{{ $home }}#pricing">Tarifs</a>
                <a href="{{ route('restaurant_signup') }}">Créer mon restaurant</a>
                <a href="{{ $home }}#faq">FAQ</a>
                <a href="{{ $home }}#contact">Contact</a>
                @foreach ($footerMenus as $menu)
                    <a href="{{ route('customMenu', ['slug' => $menu->menu_slug]) }}">{{ $menu->menu_name }}</a>
                @endforeach
            </nav>

            <div class="fm-footer__bottom">
                <div class="fm-social">
                    @if ($gs->facebook_link)
                        <a href="{{ $gs->facebook_link }}" aria-label="Facebook" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7.5h2.5l.5-3h-3V8.7c0-.9.3-1.5 1.6-1.5h1.5V4.6c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.2H8v3h2.7V21h2.8Z"/></svg></a>
                    @endif
                    @if ($gs->instagram_link)
                        <a href="{{ $gs->instagram_link }}" aria-label="Instagram" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
                    @endif
                    @if ($gs->twitter_link)
                        <a href="{{ $gs->twitter_link }}" aria-label="X" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 3h3.1l-6.8 7.7L21.8 21h-6.2l-4.9-6.4L5.1 21H2l7.2-8.3L1.7 3h6.4l4.4 5.8L17.5 3Zm-1.1 16.2h1.7L7.4 4.7H5.6l10.8 14.5Z"/></svg></a>
                    @endif
                    @if ($gs->yelp_link)
                        <a href="{{ $gs->yelp_link }}" aria-label="Yelp" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 4 2 5h5l-4 3 2 5-5-3-5 3 2-5-4-3h5l2-5Z"/></svg></a>
                    @endif
                    @if ($gs->google_business_link)
                        <a href="{{ $gs->google_business_link }}" aria-label="Google" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1h-9.3v2.95h5.43c-.235 1.34-1.22 3.925-5.43 3.925-3.27 0-5.94-2.705-5.94-6.05s2.67-6.05 5.94-6.05c1.87 0 3.12.8 3.84 1.48l2.62-2.52C17.65 2.3 15.84 1.4 13.5 1.4 7.68 1.4 3 5.92 3 11.4s4.68 10 10.5 10c5.97 0 9.9-4.22 9.9-10 0-.67-.08-1.33-.05-1.7z"/></svg></a>
                    @endif
                </div>
                <p style="font-size:14px">© {{ now()->year }} Jimbe. Tous droits réservés.</p>
            </div>
        </footer>
    </div>

    <script>
        (function () {
            var burger = document.getElementById('fm-burger');
            var panel = document.getElementById('fm-panel');
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
</body>

</html>
