{{-- Style « Foodslice » de la page client : police Montserrat + feuille de style (à charger après jimbe-shop.css) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap">
<link rel="stylesheet" href="{{ asset('css/jimbe-foodslice.css') }}?v={{ @filemtime(public_path('css/jimbe-foodslice.css')) }}">
{{-- Essai « verre liquide » : retirer cette ligne pour revenir au style Foodslice simple --}}
<link rel="stylesheet" href="{{ asset('css/jimbe-liquid-glass.css') }}?v={{ @filemtime(public_path('css/jimbe-liquid-glass.css')) }}">
