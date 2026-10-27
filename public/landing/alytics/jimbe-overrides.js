/*
 * Ajustements Jimbe par-dessus l'export Framer « Alytics ».
 * Les boutons « Get Started » pointaient vers le template du créateur : ils portent maintenant une adresse-témoin
 * (inscription.jimbe.invalid) que l'on intercepte au clic pour ouvrir la page d'inscription de l'application.
 * Framer ignore les chemins internes qu'il ne connaît pas, d'où ce détour.
 */
(function () {
    var SIGNUP = (document.currentScript && document.currentScript.getAttribute('data-signup')) || '/restaurant-signup';

    var LOGIN = (document.currentScript && document.currentScript.getAttribute('data-login')) || '/login';
    var LOCALE = (document.currentScript && document.currentScript.getAttribute('data-locale')) || 'fr';

    // Injection du sélecteur de langue flottant (FR / EN), une fois la page construite
    function addLangSwitch() {
        if (!document.body || document.querySelector('.jb-alytics-lang-switch')) return;
        var langDiv = document.createElement('div');
        langDiv.className = 'jb-alytics-lang-switch';
        langDiv.innerHTML = '<a href="/change-locale/fr" class="' + (LOCALE === 'fr' ? 'active' : '') + '">🇫🇷 FR</a>'
            + '<span class="sep">/</span>'
            + '<a href="/change-locale/en" class="' + (LOCALE === 'en' ? 'active' : '') + '">🇬🇧 EN</a>';
        document.body.appendChild(langDiv);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addLangSwitch);
    } else {
        addLangSwitch();
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href*="inscription"], a[href*="restaurant-signup"], a[href*="connexion"], a[href*="/login"]');
        if (!a) return;
        var href = a.getAttribute('href') || '';
        if (href.indexOf('login') > -1 || href.indexOf('connexion') > -1) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = LOGIN;
        } else if (href.indexOf('inscription') > -1 || href.indexOf('restaurant-signup') > -1) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = SIGNUP;
        }
    }, true);
})();
