@extends('framer.layout')

@section('content')
@php
    use App\Enums\PackageType;

    $fa = fn (string $file) => asset('landing/framer/' . $file);
    $arrowUpRight = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';
    $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 12.5l4 4 8-9"/></svg>';
    $label = fn (string $text) => '<span class="fm-label"><i>' . $check . '</i>' . e($text) . '</span>';

    $packages = $packages ?? collect();
    $allModulesWithFeature = $allModulesWithFeature ?? [];
    $trialPackage = $trialPackage ?? null;
    $frontContact = $frontContact ?? null;

    $chips = ['Menu QR', 'Commandes', 'Réservations', 'Caisse (POS)', 'Cuisine (KOT)', 'Plan de salle', 'Livraison', 'Personnel', 'Rapports'];

    $values = [
        ['glyph' => 'code', 'title' => 'Simple pour vous', 'text' => "Une seule plateforme pour piloter vos commandes, votre menu et vos tables. Moins de gestes, plus de temps pour vos équipes.", 'img' => 'TnplamgRY2ukmt782azuiyKM8Vw.png', 'rot' => 28],
        ['glyph' => 'rocket', 'title' => 'Meilleur pour vos clients', 'text' => "Un menu clair en un scan, des commandes rapides et un service fluide. Le digital accompagne le service humain, il ne le remplace pas.", 'img' => 'e4YhOBRLMApy4qwgKe4RHRIHk0.png', 'rot' => -8],
        ['glyph' => 'check-check', 'title' => 'La confiance avant tout', 'text' => "Les données de chaque restaurant restent séparées et protégées. Une plateforme fiable, y compris en plein service.", 'img' => 'mLxRMBkrwwHGCftOANUhjYQUKs.png', 'rot' => 24],
    ];

    $steps = [
        ['Créez votre restaurant', "Inscrivez-vous en quelques minutes et renseignez les informations de votre établissement."],
        ['Ajoutez votre menu et vos tables', "Créez vos catégories et vos plats, dessinez votre salle et générez un QR code pour chaque table."],
        ['Recevez et suivez les commandes', "Les commandes arrivent en cuisine et en caisse en temps réel. Vous encaissez et suivez vos ventes."],
    ];

    $features = [
        ['qr', 'Menu QR', "Un menu consultable et commandable depuis le téléphone de vos clients, sans contact."],
        ['card', 'Paiement en ligne', "Des paiements rapides et sécurisés grâce aux passerelles de paiement connectées."],
        ['users', 'Gestion du personnel', "Un accès par rôle (manager, serveur, cuisine) avec des droits différents."],
        ['monitor', 'Caisse (POS)', "Une caisse complète pour prendre les commandes et encaisser en salle."],
        ['layout', 'Plans de salle', "Dessinez la disposition de votre restaurant et suivez vos tables en direct."],
        ['chef', 'Bons de cuisine (KOT)', "Un flux de cuisine efficace : chaque commande arrive au bon poste."],
        ['printer', 'Impression des additions', "Imprimez additions et tickets sur vos imprimantes."],
        ['chart', 'Rapports', "Suivez vos ventes et exportez vos données pour piloter votre activité."],
    ];

    $reviews = [
        ["« Cela a complètement transformé notre façon de travailler. Gérer commandes, tables et équipe depuis une seule plateforme a réduit notre charge de travail et rendu le service plus fluide. »", 'John Martin', 'Propriétaire, Riverbend Bistro'],
        ["« Le menu QR et l'intégration des paiements ont fait une vraie différence. Les clients adorent la simplicité et nos tables tournent plus vite. »", 'Emily Thompson', 'Responsable, Lakeside Grill'],
        ["« Nous suivons chaque commande en temps réel, gardons notre menu à jour et encaissons rapidement. C'est comme avoir une paire de mains en plus dans le restaurant. »", 'Michael Scott', 'Propriétaire, Downtown Eats'],
    ];

    $faqs = [
        ["Qu'est-ce que Jimbe ?", "Jimbe est une plateforme tout-en-un pour les restaurants : menu QR, prise de commandes, réservations, caisse (POS), cuisine et rapports, réunis au même endroit."],
        ["Comment fonctionne le menu QR ?", "Chaque table reçoit son QR code. Vos clients le scannent avec leur téléphone pour consulter le menu et passer commande, sans installer d'application."],
        ["Puis-je gérer plusieurs restaurants ?", "Oui. Jimbe gère plusieurs restaurants et plusieurs établissements, chacun avec son menu, ses tables et son équipe."],
        ["Quels moyens de paiement puis-je utiliser ?", "Vous pouvez encaisser en caisse et en ligne. Jimbe se connecte à plusieurs passerelles de paiement : Stripe, Flutterwave, Paystack, Razorpay, PayPal et d'autres."],
        ["Mon équipe peut-elle utiliser Jimbe ?", "Oui. Créez des accès pour vos managers, vos serveurs et votre cuisine, avec des droits différents selon le rôle."],
        ["Y a-t-il un essai gratuit ?", "Un essai gratuit peut être proposé selon le forfait. Créez votre restaurant et testez Jimbe avant de choisir un tarif."],
    ];

    $integrations = [
        ['Paiements en ligne', "Vos clients paient depuis leur téléphone et vous recevez les confirmations automatiquement.", 'PKPv4vxxcYv4uSDBasG4eQASV0.png'],
        ['Suivi en temps réel', "Commandes, bons de cuisine et appels serveur s'affichent dès qu'ils arrivent.", 'WxccJh2dGkuVNr7RVtWuAz12Bq8.png'],
        ['Portail client QR', "Un menu accessible en un scan, avec le suivi de la commande pour vos clients.", 'F9u8Tn3UpjWdSLPt06qBjEHqk4k.png'],
    ];

    $hasMonthly = $packages->contains(fn ($p) => $p->package_type == PackageType::STANDARD && $p->monthly_status && !is_null($p->monthly_price));
    $hasAnnual = $packages->contains(fn ($p) => $p->package_type == PackageType::STANDARD && $p->annual_status && !is_null($p->annual_price));
    $startPeriod = !$hasMonthly && $hasAnnual ? 'annual' : 'monthly';
@endphp

{{-- décorations de page (crayons du template) --}}
<img class="fm-pencil" style="top:228px;left:-1px;transform:rotate(4deg)" src="{{ $fa('tlyfjZtRf6X0C43hvgkBF06i7U.png') }}" alt="" aria-hidden="true">
<img class="fm-pencil" style="top:160px;right:-33px;transform:rotate(235deg)" src="{{ $fa('tlyfjZtRf6X0C43hvgkBF06i7U.png') }}" alt="" aria-hidden="true">

{{-- ===== HERO ===== --}}
<section class="fm-hero">
    <img class="fm-hero__bg" src="{{ $fa('FaGBRvf1oWLONyYhDxPNrADSV0o.png') }}" alt="" aria-hidden="true">

    <div class="fm-hero__text">
        <div class="fm-hero__copy">
            <span class="fm-rating">
                <img src="{{ asset('landing/brand/jimbe-icon.png') }}" alt="" width="28" height="28" style="width:28px;height:28px">
                <span>Menu QR · Commandes · Réservations · Caisse</span>
            </span>
            <img class="fm-clip" style="top:-16px;left:53%;width:48px;height:72px" src="{{ $fa('PBpuxqRpmPqftU0hol2DVhW7bus.png') }}" alt="" aria-hidden="true">

            <h1>Gérez votre restaurant simplement, du menu à l'addition.</h1>
            <p class="t18">Commandes, menus, tables et réservations réunis au même endroit. Gagnez du temps, réduisez les erreurs et développez votre activité.</p>
        </div>

        <div class="fm-hero__cta">
            <a class="fm-btn fm-btn--primary" href="{{ route('restaurant_signup') }}">
                @if ($trialPackage) Essai gratuit de {{ $trialPackage->trial_days }} jours @else Commencer gratuitement @endif
                <span class="fm-btn__icon">{!! $arrowUpRight !!}</span>
            </a>
            <a class="fm-btn" href="#pricing">Voir les tarifs</a>
        </div>
    </div>

    <div class="fm-hero__image">
        {{-- capture d'écran du template : à remplacer par le tableau de bord Jimbe --}}
        <img class="main" src="{{ $fa('mnPeqXYNDr2xVCbRAyLgW0GSjs.png') }}" alt="Aperçu du tableau de bord Jimbe">
        <img class="fm-clip" style="top:-10px;right:60px;width:182px;height:156px;z-index:3" src="{{ $fa('5hLMgXFugiKc3BG3FbP64U9U0.png') }}" alt="" aria-hidden="true">
        <img class="fm-clip" style="top:-139px;left:-93px;width:250px;height:269px;z-index:3" src="{{ $fa('F2pjgdkTIXmpoLiaNVSJEi55RI.png') }}" alt="" aria-hidden="true">
    </div>
    <div class="fm-blur" style="top:1250px"></div>
</section>

{{-- ===== BANDEAU ===== --}}
<section class="fm-ticker">
    <div class="fm-blur" style="top:-130px"></div>
    <div class="fm-ticker__inner">
        <p class="t18m">Tout votre restaurant, au même endroit</p>
        <div class="fm-ticker__viewport">
            <div class="fm-ticker__track">
                @foreach ([1, 2, 3] as $copy)
                    @foreach ($chips as $chip)
                        <span class="fm-chip" @if ($copy > 1) aria-hidden="true" @endif>{!! $check !!}{{ $chip }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ===== VALEURS ===== --}}
<section class="fm-section" id="values">
    <div class="fm-head">
        <div class="fm-head__lbl">
            {!! $label('Nos valeurs') !!}
            <h2 style="max-width:620px">Les fondations de notre approche</h2>
        </div>
        <p class="t18" style="max-width:56%">Nous construisons Jimbe autour de trois principes : la simplicité, l'expérience de vos clients et la confiance.</p>
    </div>

    <div class="fm-cards3">
        @foreach ($values as $i => $v)
            <article class="fm-vcard">
                <img class="fm-vcard__clip" style="transform:rotate({{ $v['rot'] }}deg)" src="{{ $fa('PBpuxqRpmPqftU0hol2DVhW7bus.png') }}" alt="" aria-hidden="true">
                @include('framer._badge', ['glyph' => $v['glyph'], 'id' => 'v' . $i])
                <div class="fm-vcard__texts">
                    <h4>{{ $v['title'] }}</h4>
                    <p class="t16">{{ $v['text'] }}</p>
                </div>
                <div class="fm-vcard__img"><img src="{{ $fa($v['img']) }}" alt="" loading="lazy"></div>
            </article>
        @endforeach
    </div>
</section>

{{-- ===== FONCTIONNALITÉS CLÉS ===== --}}
<section class="fm-section" id="features">
    <div class="fm-head">
        <div class="fm-head__lbl">
            {!! $label('Fonctionnalités clés') !!}
            <h2 style="max-width:800px">Prenez le contrôle de votre restaurant</h2>
        </div>
        <p class="t18" style="max-width:70%">Commandes, réservations et menu : les outils du quotidien, pensés pour que le service reste fluide, du premier au dernier couvert.</p>
    </div>

    <div class="fm-fcards">
        <div class="fm-frow">
            <article class="fm-fcard">
                <div class="fm-fcard__texts">
                    <h3>Simplifiez la gestion de vos commandes</h3>
                    <p class="t16">Ne perdez plus jamais une commande. Sur place, à emporter ou en livraison, tout est organisé et accessible au même endroit. Accélérez le service et gardez une cuisine fluide.</p>
                </div>
                <div class="fm-fcard__img"><img src="{{ $fa('VlksoS1tVElhLr8nie8fMqR2drE.png') }}" alt="" loading="lazy"></div>
            </article>
            <article class="fm-fcard">
                <div class="fm-fcard__texts">
                    <h3>Optimisez vos réservations de tables</h3>
                    <p class="t16">Suivez vos tables et vos réservations en temps réel. Réduisez l'attente et évitez les tables vides aux heures de pointe pour améliorer l'expérience de vos clients.</p>
                </div>
                <div class="fm-fcard__img"><img src="{{ $fa('vkeqfK8AcVG1JlnsPzqxXBpmP7Y.png') }}" alt="" loading="lazy"></div>
            </article>
        </div>

        <article class="fm-fcard fm-fcard--wide">
            <div class="fm-fcard__left">
                <div class="fm-fcard__texts">
                    <h3>Gérez votre menu sans effort</h3>
                    <p class="t16">Ajoutez, modifiez ou retirez des plats en quelques clics. Mettez vos plats du jour en avant et ajustez vos prix : votre équipe et vos clients voient toujours la dernière version.</p>
                </div>
                <a class="fm-btn fm-btn--primary" href="{{ route('restaurant_signup') }}">Essayer Jimbe <span class="fm-btn__icon">{!! $arrowUpRight !!}</span></a>
            </div>
            <div class="fm-fcard__img"><img src="{{ $fa('gzZPVGY3G2GRa4eee8dB8spcYw.png') }}" alt="" loading="lazy"></div>
        </article>
    </div>
</section>

{{-- ===== COMMENT ÇA MARCHE (nouvelle section) ===== --}}
<section class="fm-section" id="how">
    <div class="fm-head">
        <div class="fm-head__lbl">
            {!! $label('Comment ça marche') !!}
            <h2 style="max-width:760px">Prêt à servir en trois étapes</h2>
        </div>
        <p class="t18" style="max-width:56%">Pas de matériel compliqué : vous configurez votre restaurant, vos clients scannent, vous servez.</p>
    </div>

    <div class="fm-cards3">
        @foreach ($steps as $i => $step)
            <article class="fm-step">
                <span class="fm-step__num">{{ $i + 1 }}</span>
                <h4>{{ $step[0] }}</h4>
                <p class="t16">{{ $step[1] }}</p>
            </article>
        @endforeach
    </div>
</section>

{{-- ===== POURQUOI JIMBE ===== --}}
<section class="fm-section" id="why">
    <div class="fm-head">
        <div class="fm-head__lbl">
            {!! $label('Pourquoi Jimbe ?') !!}
            <h2 style="max-width:800px">Les essentiels qui font la différence au quotidien</h2>
        </div>
        <p class="t18" style="max-width:70%">Découvrez les outils qui font de Jimbe une solution complète pour gérer vos commandes, votre salle et votre équipe.</p>
    </div>

    <div class="fm-why">
        <div class="fm-why__row">
            <div class="fm-why__img"><img src="{{ $fa('PxTmDeAUHOdCUS1fykrhEISqyFQ.png') }}" alt="" loading="lazy"></div>
            <div class="fm-why__content">
                <div class="fm-why__texts">
                    <h3>Gardez la main sur chaque commande, du menu QR à la caisse.</h3>
                    <p class="t16">Toutes vos commandes dans une vue claire. Restez organisé, gardez le rythme en cuisine et en salle, sans rien perdre en route.</p>
                </div>
                <div class="fm-why__infos">
                    <div class="fm-why__info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M16 12h1"/></svg>
                        <div><p class="t20m">Menu QR</p><p class="t16">Vos clients commandent depuis leur téléphone, sans contact et sans attente.</p></div>
                    </div>
                    <div class="fm-why__info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        <div><p class="t20m">Caisse (POS)</p><p class="t16">Prenez les commandes et encaissez en salle depuis une caisse intégrée.</p></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fm-why__row">
            <div class="fm-why__content" style="gap:40px">
                <div class="fm-why__texts">
                    <h3>Suivez votre activité avec tout au même endroit.</h3>
                    <div class="fm-checks">
                        @foreach (["Commandes sur place, à emporter et en livraison réunies dans le même outil.", "Bons de cuisine (KOT) envoyés au bon poste, pour un service plus rapide et sans oubli.", "Rapports de ventes et exports pour mesurer ce qui marche et décider vite."] as $line)
                            <div class="fm-check"><span class="fm-check__dot">{!! $check !!}</span><p class="t16">{{ $line }}</p></div>
                        @endforeach
                    </div>
                </div>
                <a class="fm-btn fm-btn--primary" href="#pricing">Voir les tarifs <span class="fm-btn__icon">{!! $arrowUpRight !!}</span></a>
            </div>
            <div class="fm-why__img">
                <img src="{{ $fa('LgJeoelKBoR3iBmayZlXR6lmWY.png') }}" alt="" loading="lazy">
                <img class="fm-clip" style="top:13px;right:-60px;width:151px;height:86px;z-index:2" src="{{ $fa('TUEPOhSBMjWwrf9K8jtcgFsK3As.png') }}" alt="" aria-hidden="true">
            </div>
        </div>
    </div>
</section>

{{-- ===== TOUT CE QU'IL FAUT (nouvelle section) ===== --}}
<section class="fm-section" id="all-features">
    <div class="fm-head">
        <div class="fm-head__lbl">
            {!! $label('Toutes les fonctionnalités') !!}
            <h2 style="max-width:840px">Tout ce qu'il faut pour piloter votre restaurant</h2>
        </div>
    </div>

    <div class="fm-grid4">
        @foreach ($features as $i => $f)
            <article class="fm-vcard fm-vcard--compact">
                @include('framer._badge', ['glyph' => $f[0], 'id' => 'f' . $i])
                <div class="fm-vcard__texts">
                    <h4 style="font-size:22px">{{ $f[1] }}</h4>
                    <p class="t16">{{ $f[2] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- ===== TARIFS (forfaits lus en base) ===== --}}
<section class="fm-section" id="pricing">
    <div class="fm-head">
        <div class="fm-head__lbl" style="width:60%">
            {!! $label('Tarifs') !!}
            <h2>Des tarifs simples et transparents</h2>
        </div>
        <p class="t18" style="width:54%">Un forfait abordable pour gérer tout votre restaurant. Commencez simplement et évoluez à votre rythme.</p>
    </div>

    @if ($hasMonthly && $hasAnnual)
        <div class="fm-switch" role="group" aria-label="Période de facturation" id="fm-billing">
            <button type="button" aria-pressed="{{ $startPeriod === 'monthly' ? 'true' : 'false' }}" data-period="monthly">Mensuel</button>
            <button type="button" aria-pressed="{{ $startPeriod === 'annual' ? 'true' : 'false' }}" data-period="annual">Annuel</button>
        </div>
    @endif

    <div class="fm-plans" id="fm-plans" data-period="{{ $startPeriod }}">
        @forelse ($packages as $package)
            @php
                $isLifetime = $package->package_type == PackageType::LIFETIME;
                $isFree = $package->package_type == PackageType::FREE;
                $isStandard = $package->package_type == PackageType::STANDARD;
                $enabledModules = array_merge(
                    $package->modules->pluck('name')->toArray(),
                    $package->additional_features ? json_decode($package->additional_features, true) : []
                );
            @endphp
            <article class="fm-plan"
                data-always="{{ ($isLifetime || $isFree) ? 1 : 0 }}"
                data-monthly="{{ ($isStandard && $package->monthly_status) ? 1 : 0 }}"
                data-annual="{{ ($isStandard && $package->annual_status) ? 1 : 0 }}">
                <div class="fm-plan__top">
                    <div>
                        <h4>{{ $package->package_name }}</h4>
                        @if (!empty($package->description))<p class="t16">{{ $package->description }}</p>@endif
                    </div>
                    <div>
                        @if ($isFree)
                            <div class="fm-plan__price"><strong>Gratuit</strong></div>
                        @endif
                        @if ($isLifetime)
                            <div class="fm-plan__price"><strong>{{ global_currency_format($package->price, $package->currency_id) }}</strong><span>paiement unique</span></div>
                        @endif
                        @if ($isStandard && !is_null($package->monthly_price) && $package->monthly_status)
                            <div class="fm-plan__price" data-price="monthly"><strong>{{ global_currency_format($package->monthly_price, $package->currency_id) }}</strong><span>/ mois</span></div>
                        @endif
                        @if ($isStandard && !is_null($package->annual_price) && $package->annual_status)
                            <div class="fm-plan__price" data-price="annual"><strong>{{ global_currency_format($package->annual_price, $package->currency_id) }}</strong><span>/ an</span></div>
                        @endif
                    </div>
                    <a href="{{ route('restaurant_signup') }}" class="fm-btn fm-btn--block {{ $loop->iteration === 2 ? 'fm-btn--green' : '' }}">Commencer</a>
                </div>
                <div class="fm-plan__features">
                    <h5>Fonctionnalités incluses :</h5>
                    @foreach ($allModulesWithFeature as $moduleName)
                        @continue(!in_array($moduleName, $enabledModules))
                        @php
                            $limit = match ($moduleName) {
                                'Menu Item' => $package->menu_items_limit,
                                'Order' => $package->order_limit,
                                'Staff' => $package->staff_limit,
                                default => null,
                            };
                        @endphp
                        <div class="fm-check">
                            <span class="fm-check__dot">{!! $check !!}</span>
                            <p class="t16" style="color:#000">
                                {{ __('permissions.modules.' . $moduleName) }}
                                @if (!is_null($limit))
                                    <span style="color:var(--fm-primary)">
                                        @if ($limit == -1) (illimité) @elseif ($moduleName === 'Order') ({{ number_format($limit) }} par jour) @else ({{ number_format($limit) }}) @endif
                                    </span>
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </article>
        @empty
            <p class="t18">Les forfaits seront bientôt disponibles.</p>
        @endforelse
    </div>

    <div class="fm-custom">
        <img class="fm-clip" style="top:-29px;left:-22px;width:64px;height:95px" src="{{ $fa('fQEwOULU2aiGa2wv5VG8aG7yAg.png') }}" alt="" aria-hidden="true">
        <div class="fm-custom__text">
            <h4>Besoin d'autre chose ?</h4>
            <p class="t16">Vous cherchez une offre sur mesure ? Contactez-nous : nous vous aidons à construire la solution adaptée à votre restaurant.</p>
        </div>
        <div><a class="fm-btn" href="#contact">Nous contacter</a></div>
        <div class="fm-custom__art"><img src="{{ $fa('kfw802fDWxQ8WaJKGAOaXw2XlzQ.png') }}" alt="" loading="lazy"></div>
    </div>
</section>

{{-- ===== TÉMOIGNAGES (exemples issus des données de démonstration de Jimeb : à remplacer par de vrais avis) ===== --}}
<section class="fm-section" id="testimonials">
    <div class="fm-head">
        <div class="fm-head__lbl" style="width:60%">
            {!! $label('Témoignages') !!}
            <h2>Ce que disent les restaurateurs</h2>
        </div>
        <p class="t18" style="width:54%">Découvrez comment Jimbe aide les restaurants à rester organisés et à servir plus vite.</p>
    </div>

    <div class="fm-reviews">
        @foreach ([[$reviews, ''], [array_reverse($reviews), 'fm-reviews__row--rev']] as [$set, $dir])
            <div class="fm-reviews__viewport">
                <div class="fm-reviews__row {{ $dir }}">
                    @foreach ([1, 2, 3] as $copy)
                        @foreach ($set as $r)
                            <figure class="fm-review" style="margin:0" @if ($copy > 1) aria-hidden="true" @endif>
                                <div class="fm-review__stars" aria-label="5 étoiles sur 5">★★★★★</div>
                                <blockquote class="fm-review__quote" style="margin:0">{{ $r[0] }}</blockquote>
                                <figcaption class="fm-review__who">
                                    <span class="fm-review__ini" aria-hidden="true">{{ mb_strtoupper(mb_substr($r[1], 0, 1)) }}</span>
                                    <div><b>{{ $r[1] }}</b><span>{{ $r[2] }}</span></div>
                                </figcaption>
                            </figure>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ===== INTÉGRATIONS ===== --}}
<section class="fm-section" id="integrations">
    <div class="fm-head">
        <div class="fm-head__lbl" style="width:56%">
            {!! $label('Intégrations') !!}
            <h2>Reliez Jimbe aux outils que vous utilisez déjà.</h2>
        </div>
        <p class="t18" style="width:54%">Encaissez en ligne et suivez le service en direct, sans changer vos habitudes.</p>
    </div>

    <div class="fm-integ">
        <div class="fm-integ__bg">
            <img src="{{ $fa('6y1NozjCvCalQpj8h9P0WuGZWw.png') }}" alt="" aria-hidden="true">
            <a class="fm-btn fm-btn--primary" href="#features">Voir les fonctionnalités <span class="fm-btn__icon">{!! $arrowUpRight !!}</span></a>
        </div>

        <div class="fm-icards">
            @foreach ($integrations as $i => $card)
                <article class="fm-icard">
                    <img class="fm-icard__mask" src="{{ $fa('cGS1duqPiO7LZAUo0YhZiZJphs.svg') }}" alt="" aria-hidden="true">
                    <div>
                        <div class="fm-icard__title">
                            @include('framer._badge', ['glyph' => ['card', 'chart', 'qr'][$i], 'id' => 'i' . $i, 'class' => 'fm-vcard__badge'])
                            <h4>{{ $card[0] }}</h4>
                        </div>
                        <p class="t16" style="margin-top:16px">{{ $card[1] }}</p>
                    </div>
                    <div class="fm-icard__img"><img src="{{ $fa($card[2]) }}" alt="" loading="lazy"></div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== FAQ ===== --}}
<section class="fm-section fm-faq" id="faq">
    <div class="fm-head">
        <div class="fm-head__lbl" style="width:40%">
            {!! $label('FAQ') !!}
            <h2>Les réponses à vos questions</h2>
        </div>
    </div>

    <div class="fm-faq__list">
        @foreach ($faqs as $i => $faq)
            <details class="fm-faq__item" @if ($i === 0) open @endif>
                <summary>{{ $faq[0] }}</summary>
                <p class="fm-faq__a t16">{{ $faq[1] }}</p>
            </details>
        @endforeach
    </div>

    <div style="display:flex;flex-direction:column;align-items:center;gap:12px">
        <p class="t20m">Vous n'avez pas trouvé votre réponse ?</p>
        <a class="fm-btn fm-btn--primary" href="#contact">Nous contacter <span class="fm-btn__icon">{!! $arrowUpRight !!}</span></a>
    </div>
</section>

{{-- ===== CONTACT (nouvelle section, données de l'administration) ===== --}}
<section class="fm-section" id="contact" style="padding-bottom:0">
    <div class="fm-head">
        <div class="fm-head__lbl">
            {!! $label('Contact') !!}
            <h2>Parlons de votre restaurant</h2>
        </div>
    </div>

    <div class="fm-contact">
        <div class="fm-contact__card">
            <span class="fm-contact__ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <div>
                <p class="t16">{{ $frontContact->contact_company ?? 'Jimbe' }}</p>
                <p class="t20m">{{ $frontContact->address ?? 'Abidjan, Côte d\'Ivoire' }}</p>
            </div>
        </div>
        <div class="fm-contact__card">
            <span class="fm-contact__ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/></svg></span>
            <div>
                <p class="t16">E-mail</p>
                @php($mail = $frontContact->email ?? null)
                @if ($mail)<a class="t20m" href="mailto:{{ $mail }}">{{ $mail }}</a>@else<p class="t20m">Écrivez-nous depuis votre espace</p>@endif
            </div>
        </div>
    </div>
</section>

{{-- ===== PRÉ-PIED DE PAGE ===== --}}
<section class="fm-pre">
    <div class="fm-pre__box">
        <img class="fm-pre__bg" src="{{ $fa('FaGBRvf1oWLONyYhDxPNrADSV0o.png') }}" alt="" aria-hidden="true">
        <img class="fm-pre__deco" style="left:-58px;top:324px;width:292px" src="{{ $fa('d5CcIY6r8UTKtze9VdciUd2sak.png') }}" alt="" aria-hidden="true">
        <img class="fm-pre__deco" style="left:-49px;top:496px;width:333px" src="{{ $fa('i9941GyxcyZnGWcAaT52pWjgg9M.png') }}" alt="" aria-hidden="true">
        <img class="fm-pre__deco" style="right:-60px;top:300px;width:297px" src="{{ $fa('iU2q6qrsgiPdy2YOatf5bS0nCFk.png') }}" alt="" aria-hidden="true">
        <img class="fm-pre__deco" style="right:-60px;top:475px;width:280px" src="{{ $fa('e5ukRb2KBQGOHzvH9gxkKadp494.png') }}" alt="" aria-hidden="true">
        <img class="fm-pre__deco" style="left:-26px;top:134px;width:260px" src="{{ $fa('F2pjgdkTIXmpoLiaNVSJEi55RI.png') }}" alt="" aria-hidden="true">

        <div class="fm-pre__text">
            <h2>Simplifiez votre service avec Jimbe</h2>
            <p class="t18">Jimbe apporte clarté et rapidité à votre restaurant : vos commandes, votre menu et votre équipe réunis dans une seule plateforme. Commencez dès aujourd'hui.</p>
            <a class="fm-btn fm-btn--primary" href="{{ route('restaurant_signup') }}">Créer mon restaurant <span class="fm-btn__icon">{!! $arrowUpRight !!}</span></a>
        </div>
    </div>
</section>

<script>
    (function () {
        var plans = document.getElementById('fm-plans');
        var group = document.getElementById('fm-billing');
        if (!plans) { return; }

        function apply(period) {
            plans.dataset.period = period;
            plans.querySelectorAll('.fm-plan').forEach(function (card) {
                var show = card.dataset.always === '1' || card.dataset[period] === '1';
                card.hidden = !show;
                card.querySelectorAll('[data-price]').forEach(function (p) { p.hidden = p.dataset.price !== period; });
            });
        }
        apply(plans.dataset.period);

        if (group) {
            group.addEventListener('click', function (e) {
                var btn = e.target.closest('button');
                if (!btn) { return; }
                group.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
                apply(btn.dataset.period);
            });
        }
    })();
</script>
@endsection
