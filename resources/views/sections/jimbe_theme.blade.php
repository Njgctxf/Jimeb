{{-- Thème global du logiciel (design Mobilo). $brand = false pour conserver la couleur choisie par le restaurant (site client). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
@if (($brand ?? true) === true)
    <link href="{{ asset('css/jimbe-brand.css') }}?v={{ filemtime(public_path('css/jimbe-brand.css')) }}" rel="stylesheet">
@endif
<link href="{{ asset('css/jimbe-theme.css') }}?v={{ filemtime(public_path('css/jimbe-theme.css')) }}" rel="stylesheet">
