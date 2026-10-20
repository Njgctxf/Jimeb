/*
 * Ajustements Jimbe par-dessus l'export Framer « Alytics ».
 * Les boutons « Get Started » pointaient vers le template du créateur : ils portent maintenant une adresse-témoin
 * (inscription.jimbe.invalid) que l'on intercepte au clic pour ouvrir la page d'inscription de l'application.
 * Framer ignore les chemins internes qu'il ne connaît pas, d'où ce détour.
 */
(function () {
    var SIGNUP = (document.currentScript && document.currentScript.getAttribute('data-signup')) || '/restaurant-signup';

    var LOGIN = (document.currentScript && document.currentScript.getAttribute('data-login')) || '/login';

    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href^="https://inscription.jimbe.invalid"], a[href^="https://connexion.jimbe.invalid"]');
        if (!a) return;
        e.preventDefault();
        e.stopPropagation();
        window.location.href = a.getAttribute('href').indexOf('connexion.') > -1 ? LOGIN : SIGNUP;
    }, true);
})();
