@php
    $frontDetails = $frontDetails ?? null;
    $frontFeatures = $frontFeatures ?? collect();
    $frontReviews = $frontReviews ?? collect();
    $frontFaqs = $frontFaqs ?? collect();
    $frontContact = $frontContact ?? null;

    $imageFeatures = $frontFeatures->where('type', 'image')->values();
    $iconFeatures = $frontFeatures->where('type', 'icon')->values();
    $featureFallbacks = ['landing/order-management.png', 'landing/table-reservation.png', 'landing/menu-management.png'];

    $arrow = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
    $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 12.5l4 4 8-9"/></svg>';

    $marquee = ['Menu QR', 'Commandes', 'Réservations', 'Caisse (POS)', 'Cuisine (KOT)', 'Plan de salle', 'Livraison', 'Personnel', 'Rapports'];
@endphp

{{-- ===================== HERO ===================== --}}
<section class="jb-hero">
    <div class="jb-container">
        <span class="jb-hero__rating">
            <img src="{{ asset('landing/brand/jimbe-icon.png') }}" alt="" width="28" height="28">
            <span>Menu QR · Commandes · Réservations · Caisse</span>
        </span>

        <h1>{{ $frontDetails->header_title ?? __('landing.heroTitle') }}</h1>

        <div class="jb-hero__sub jb-rich">{!! $frontDetails->header_description ?? __('landing.heroSubTitle') !!}</div>

        <div class="jb-hero__cta">
            <a href="{{ route('restaurant_signup') }}" class="jb-btn jb-btn--primary">
                @if ($trialPackage)
                    Essai gratuit de {{ $trialPackage->trial_days }} jours
                @else
                    Commencer gratuitement
                @endif
                <span class="jb-btn__arrow">{!! $arrow !!}</span>
            </a>
            <a href="#simple-pricing" class="jb-btn jb-btn--dark">Voir les tarifs</a>
        </div>

        <div class="jb-hero__stage">
            <img class="jb-hero__owl jb-hero__owl--a" src="{{ asset('landing/brand/jimbe-icon.png') }}" alt="" aria-hidden="true">
            <img class="jb-hero__owl jb-hero__owl--b" src="{{ asset('landing/brand/jimbe-icon.png') }}" alt="" aria-hidden="true">
            <div class="jb-frame">
                <img src="{{ $frontDetails->image_url ?? asset('landing/dashboard.png') }}" alt="Aperçu du tableau de bord Jimbe">
            </div>
        </div>
    </div>
</section>

{{-- ===================== BANDEAU DÉFILANT ===================== --}}
<section class="jb-marquee" aria-label="Modules inclus">
    <div class="jb-container">
        <p class="jb-marquee__label">Tout votre restaurant, au même endroit</p>
    </div>
    <div class="jb-marquee__viewport">
        <div class="jb-marquee__track">
            @foreach ([1, 2] as $loopCopy)
                @foreach ($marquee as $item)
                    <span class="jb-chip" @if ($loopCopy === 2) aria-hidden="true" @endif>{!! $check !!}{{ $item }}</span>
                @endforeach
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== VALEURS ===================== --}}
<section class="jb-section jb-section--cream" id="values">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Nos valeurs</span>
            <h2>Les fondations de notre approche</h2>
            <p>Nous construisons Jimbe autour de trois principes : la clarté, l'expérience client et la confiance. C'est ce qui guide chaque fonctionnalité.</p>
        </div>

        <div class="jb-grid-3">
            <article class="jb-card">
                <div class="jb-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r=".6" fill="currentColor"/></svg>
                </div>
                <h3>Simple pour vous</h3>
                <p>Une seule plateforme pour piloter vos commandes, votre menu et vos tables. Moins de gestes, plus de temps pour vos équipes et vos clients.</p>
            </article>
            <article class="jb-card">
                <div class="jb-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-4.6-9.2-9.1C1.4 8.8 3 5.5 6.3 5.2c2-.2 3.7.8 4.7 2.4 1-1.6 2.7-2.6 4.7-2.4 3.3.3 4.9 3.6 3.5 6.7C19 16.4 12 21 12 21z"/></svg>
                </div>
                <h3>Meilleur pour vos clients</h3>
                <p>Un menu clair en un scan, des commandes rapides et un service fluide. Le digital accompagne le service humain, il ne le remplace pas.</p>
            </article>
            <article class="jb-card">
                <div class="jb-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>
                </div>
                <h3>La confiance avant tout</h3>
                <p>Vos données et celles de vos clients restent séparées et protégées. Une plateforme fiable, y compris en plein service.</p>
            </article>
        </div>
    </div>
</section>

{{-- ===================== FONCTIONNALITÉS PRINCIPALES ===================== --}}
@if ($imageFeatures->isNotEmpty())
<section class="jb-section" id="key-features">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Fonctionnalités clés</span>
            <h2>{{ $frontDetails->feature_with_image_heading ?? __('landing.featureSection1') }}</h2>
        </div>

        <div class="jb-bento">
            @foreach ($imageFeatures as $index => $feature)
                <article class="jb-bento__card">
                    <div class="jb-bento__body">
                        <h3>{{ $feature->title }}</h3>
                        <div class="jb-rich">{!! $feature->description !!}</div>
                        <a href="{{ route('restaurant_signup') }}" class="jb-btn jb-btn--blue">
                            Essayer
                            <span class="jb-btn__arrow">{!! $arrow !!}</span>
                        </a>
                    </div>
                    <div class="jb-bento__media">
                        <img loading="lazy"
                            src="{{ $feature->image ? asset('user-uploads/front_feature/' . $feature->image) : asset($featureFallbacks[$index % count($featureFallbacks)]) }}"
                            alt="{{ $feature->title }}">
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===================== POURQUOI JIMBE (ICÔNES) ===================== --}}
@if ($iconFeatures->isNotEmpty())
<section class="jb-section jb-section--soft" id="icon-features">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Pourquoi Jimbe ?</span>
            <h2>{{ $frontDetails->feature_with_icon_heading ?? __('landing.featureSection2') }}</h2>
        </div>

        <div class="jb-grid-4">
            @foreach ($iconFeatures as $feature)
                <article class="jb-feature">
                    <div class="jb-feature__icon">
                        @if (!empty($feature->image))
                            {!! $feature->image !!}
                        @else
                            {!! $check !!}
                        @endif
                    </div>
                    <h3>{{ $feature->title }}</h3>
                    <div class="jb-rich">{!! $feature->description !!}</div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@else
    <span id="icon-features"></span>
@endif

{{-- ===================== TARIFS ===================== --}}
<section class="jb-section" id="simple-pricing">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Tarifs</span>
            <h2>{{ $frontDetails->price_heading ?? __('landing.pricingTitle1') }}</h2>
            <div class="jb-rich">{!! $frontDetails->price_description ?? __('landing.pricingSubTitle1') !!}</div>
        </div>

        @include('landing.pricing', ['packages' => $packages, 'modules' => $AllModulesWithFeature])
    </div>
</section>

{{-- ===================== AVIS ===================== --}}
@if ($frontReviews->isNotEmpty())
<section class="jb-section jb-section--soft" id="reviews">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Témoignages</span>
            <h2>{{ $frontDetails->review_heading ?? __('landing.testimonialSection1') }}</h2>
        </div>

        <div class="jb-quotes">
            @foreach ($frontReviews as $review)
                <figure class="jb-quote" style="margin:0">
                    <div class="jb-stars" aria-label="5 étoiles sur 5">★★★★★</div>
                    <blockquote>« {{ $review->reviews }} »</blockquote>
                    <footer>
                        <span class="jb-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->reviewer_name, 0, 1)) }}</span>
                        <div>
                            <strong>{{ $review->reviewer_name }}</strong>
                            <span class="jb-role">{{ $review->reviewer_designation }}</span>
                        </div>
                    </footer>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===================== INTÉGRATIONS ===================== --}}
<section class="jb-section" id="integrations">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Intégrations</span>
            <h2>Reliez Jimbe aux outils que vous utilisez déjà</h2>
            <p>Encaissez en ligne et suivez le service en direct, sans changer vos habitudes.</p>
        </div>

        <div class="jb-integ">
            <article class="jb-integ__card">
                <h3>Paiements en ligne</h3>
                <p>Vos clients paient depuis leur téléphone, vous recevez les confirmations automatiquement.</p>
                <div class="jb-integ__chips">
                    @foreach (['Stripe', 'Flutterwave', 'Paystack', 'Razorpay', 'PayPal', 'Mollie'] as $gateway)
                        <span class="jb-chip">{!! $check !!}{{ $gateway }}</span>
                    @endforeach
                </div>
            </article>
            <article class="jb-integ__card">
                <h3>Suivi en temps réel</h3>
                <p>Commandes, bons de cuisine et appels serveur s'affichent dès qu'ils arrivent.</p>
            </article>
        </div>
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
@if ($frontFaqs->isNotEmpty())
<section class="jb-section jb-section--cream" id="user-faqs">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>FAQ</span>
            <h2>{{ $frontDetails->faq_heading ?? __('landing.faqTitle1') }}</h2>
            <div class="jb-rich">{!! $frontDetails->faq_description ?? __('landing.faqSubTitle1') !!}</div>
        </div>

        <div class="jb-faq">
            @foreach ($frontFaqs as $frontFaq)
                <details @if ($loop->first) open @endif>
                    <summary>{{ $frontFaq->question }}</summary>
                    <div class="jb-faq__a jb-rich">{!! $frontFaq->answer !!}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@else
    <span id="user-faqs"></span>
@endif

{{-- ===================== CONTACT ===================== --}}
<section class="jb-section" id="contact">
    <div class="jb-container">
        <div class="jb-head">
            <span class="jb-pill"><i>{!! $check !!}</i>Contact</span>
            <h2>{{ $frontDetails->contact_heading ?? __('landing.contactTitle') }}</h2>
        </div>

        <div class="jb-contact">
            <div class="jb-contact__media">
                @if (!empty($frontContact->image))
                    <img loading="lazy" src="{{ asset('user-uploads/contact_image/' . $frontContact->image) }}" alt="Contact">
                @else
                    <img loading="lazy" src="{{ asset('landing/contact-image.png') }}" alt="Contact">
                @endif
            </div>

            <div class="jb-contact__info">
                <div class="jb-contact__row">
                    <div class="jb-card__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <small>{{ $frontContact->contact_company ?? __('landing.contactCompany') }}</small>
                        <address>{{ $frontContact->address ?? __('landing.contactAddress') }}</address>
                    </div>
                </div>

                <div class="jb-contact__row">
                    <div class="jb-card__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/></svg>
                    </div>
                    <div>
                        <small>@lang('landing.emailTitle')</small>
                        <a href="mailto:{{ $frontContact->email ?? __('landing.contactEmail') }}">{{ $frontContact->email ?? __('landing.contactEmail') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
