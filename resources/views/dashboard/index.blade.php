@extends('layouts.app')

@section('content')

@php
    $jbNow = now()->timezone(timezone());
    $jbFr = str_starts_with(app()->getLocale(), 'fr');
    $jbHour = (int) $jbNow->format('G');
    $jbHello = $jbFr
        ? ($jbHour < 12 ? 'Bonjour' : ($jbHour < 18 ? 'Bon après-midi' : 'Bonsoir'))
        : ($jbHour < 12 ? 'Good morning' : ($jbHour < 18 ? 'Good afternoon' : 'Good evening'));
    $jbName = explode(' ', trim(user()->name ?? ''))[0] ?? '';
@endphp

<section class="jb-hero-card" aria-label="@lang('menu.dashboard')">
    <div class="jb-hero-card__text">
        <span class="jb-hero-card__date">
            {{ $jbNow->translatedFormat('l') }}, {{ $jbNow->format(dateFormat() . ', ' . timeFormat()) }}
        </span>
        <h1>{{ $jbHello }}{{ $jbName ? ', ' . $jbName : '' }} 👋</h1>
        <p>{{ $jbFr ? "Voici le service d'aujourd'hui : commandes, tables et réservations en un coup d'œil." : "Here is today's service: orders, tables and reservations at a glance." }}</p>
        <div class="jb-hero-card__actions">
            @if (config('jimeb.simple_mode'))
                <a href="{{ route('orders.index') }}" class="jb-hero-btn jb-hero-btn--light">{{ $jbFr ? 'Commandes' : 'Orders' }}</a>
                <a href="{{ route('qrcodes.index') }}" class="jb-hero-btn">{{ $jbFr ? 'QR codes' : 'QR codes' }}</a>
                <a href="{{ route('menu-items.index') }}" class="jb-hero-btn">{{ $jbFr ? 'Plats' : 'Dishes' }}</a>
            @else
                <a href="{{ route('pos.index') }}" class="jb-hero-btn jb-hero-btn--light">{{ $jbFr ? 'Nouvelle commande' : 'New order' }}</a>
                <a href="{{ route('tables.index') }}" class="jb-hero-btn">{{ $jbFr ? 'Tables' : 'Tables' }}</a>
                <a href="{{ route('reservations.index') }}" class="jb-hero-btn">{{ $jbFr ? 'Réservations' : 'Reservations' }}</a>
            @endif
        </div>
    </div>
    <div class="jb-hero-card__art" aria-hidden="true">
        <img class="jb-hero-card__tile jb-hero-card__tile--a" src="{{ asset('landing/framer/TnplamgRY2ukmt782azuiyKM8Vw.png') }}" alt="">
        <img class="jb-hero-card__tile jb-hero-card__tile--b" src="{{ asset('landing/framer/e4YhOBRLMApy4qwgKe4RHRIHk0.png') }}" alt="">
        <img class="jb-hero-card__clip" src="{{ asset('landing/framer/PBpuxqRpmPqftU0hol2DVhW7bus.png') }}" alt="">
    </div>
</section>


@if (config('jimeb.simple_mode'))
    @php
        $jbDishCount = \App\Models\MenuItem::count();
        $jbTodo = [
            ['id' => 'qr', 'label' => $jbFr ? 'Télécharger et imprimer vos QR codes' : 'Download and print your QR codes', 'url' => route('qrcodes.index'), 'done' => false],
            ['id' => 'dishes', 'label' => $jbFr ? "Ajouter d'autres plats" : 'Add more dishes', 'url' => route('menu-items.create'), 'done' => $jbDishCount >= 5],
            ['id' => 'site', 'label' => $jbFr ? 'Voir ce que vos clients voient' : 'See what your customers see', 'url' => customer_preview_url(), 'done' => false, 'blank' => true],
        ];
    @endphp
    <section class="px-4 mt-4" x-data="{ hidden: false, ticked: {}, init() { try { this.hidden = localStorage.getItem('jb_todo_hidden') === '1'; this.ticked = JSON.parse(localStorage.getItem('jb_todo_ticked') || '{}'); } catch (e) {} }, tick(id) { this.ticked[id] = true; try { localStorage.setItem('jb_todo_ticked', JSON.stringify(this.ticked)); } catch (e) {} }, hide() { this.hidden = true; try { localStorage.setItem('jb_todo_hidden', '1'); } catch (e) {} } }" x-show="!hidden" x-cloak>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $jbFr ? 'À faire pour bien démarrer' : 'Getting started' }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $jbFr ? 'Vos tables et votre premier plat sont prêts. Il reste :' : 'Your tables and first dish are ready. Next:' }}</p>
                </div>
                <button type="button" class="text-sm text-gray-400 hover:text-gray-600" @click="hide()">{{ $jbFr ? 'Masquer' : 'Hide' }}</button>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach ($jbTodo as $t)
                    <li class="flex items-center gap-3">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border text-xs"
                              :class="({{ $t['done'] ? 'true' : 'false' }} || ticked['{{ $t['id'] }}']) ? 'border-green-500 bg-green-500 text-white' : 'border-gray-300 text-transparent'">✓</span>
                        <a href="{{ $t['url'] }}" @if (! empty($t['blank'])) target="_blank" @endif @click="tick('{{ $t['id'] }}')" class="text-sm text-skin-base hover:underline">{{ $t['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@if (
    (!empty(global_setting()->vapid_public_key) && !empty(global_setting()->vapid_private_key))
    || (pusherSettings()->beamer_status && !empty(pusherSettings()->instance_id))
)
    <div id="notification-alert" class="px-4" x-cloak>
        <x-alert type="warning" icon="info-circle">
            <div class="flex items-center gap-2">
                @lang('messages.pushNotificationMessage')
                <x-button id="subscribe-button">@lang('messages.enableNotifications')</x-button>
            </div>
        </x-alert>
    </div>
@endif

<x-banner />
@php
    $restaurantModules = restaurant_modules();
    $restaurantId = user()->restaurant_id;

    // Get stats for all three modules
    $orderStats = branch() ? getRestaurantOrderStats(branch()->id) : null;
    $staffStats = getRestaurantStaffStats($restaurantId);
    $menuItemStats = getRestaurantMenuItemStats($restaurantId);

    // Check which limits are exceeded
    $orderLimitExceeded = in_array('Order', $restaurantModules) && $orderStats && !$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'];
    $staffLimitExceeded = in_array('Staff', $restaurantModules) && $staffStats && !$staffStats['unlimited'] && $staffStats['current_count'] >= $staffStats['staff_limit'];
    $menuItemLimitExceeded = in_array('Menu Item', $restaurantModules) && $menuItemStats && !$menuItemStats['unlimited'] && $menuItemStats['current_count'] >= $menuItemStats['menu_items_limit'];

    $anyLimitExceeded = $orderLimitExceeded || $staffLimitExceeded || $menuItemLimitExceeded;
@endphp

@if($anyLimitExceeded)
<div class="px-4 py-4 space-y-3">
    @if($orderLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.order.orderLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.order.orderLimitExceededMessage', [
                        'current' => number_format($orderStats['current_count']),
                        'limit' => number_format($orderStats['order_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($staffLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.staff.staffLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.staff.staffLimitExceededMessage', [
                        'current' => number_format($staffStats['current_count']),
                        'limit' => number_format($staffStats['staff_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($menuItemLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.menu.menuItemLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.menu.menuItemLimitExceededMessage', [
                        'current' => number_format($menuItemStats['current_count']),
                        'limit' => number_format($menuItemStats['menu_items_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

@livewire('dashboard.restaurant-dashboard')

@endsection
