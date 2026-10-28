@php
    // Jimbe : titres et descriptions des groupes de réglages (voir Master::sections())
    $jbGroups = [
        'restaurant' => [__('modules.settings.groupRestaurant'), __('modules.settings.groupRestaurantHint')],
        'orders' => [__('modules.settings.groupOrders'), __('modules.settings.groupOrdersHint')],
        'payment' => [__('modules.settings.groupPayment'), __('modules.settings.groupPaymentHint')],
        'site' => [__('modules.settings.groupSite'), __('modules.settings.groupSiteHint')],
        'team' => [__('modules.settings.groupTeam'), __('modules.settings.groupTeamHint')],
        'account' => [__('modules.settings.groupAccount'), __('modules.settings.groupAccountHint')],
        'advanced' => [__('modules.settings.groupAdvanced'), __('modules.settings.groupAdvancedHint')],
    ];
    $jbCanStaff = in_array('Staff', restaurant_modules()) && user_can('Show Staff Member');
@endphp

<div class="min-h-0 lg:flex lg:h-[calc(100dvh-4rem)] lg:max-h-[calc(100dvh-4rem)] lg:min-h-0 lg:flex-col lg:overflow-hidden">

    <div class="flex min-h-0 flex-1 flex-col lg:h-full lg:min-h-0 lg:flex-row lg:items-stretch lg:overflow-x-hidden lg:overflow-y-visible">

        <div class="relative z-10 flex w-full min-h-0 shrink-0 flex-col border-b border-gray-100 bg-white text-sm font-medium text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 lg:sticky lg:top-0 lg:h-full lg:max-h-full lg:min-h-0 lg:w-60 lg:min-w-[240px] lg:overflow-x-hidden lg:border-b-0 lg:border-r"
            x-data="{
                menuOpen: false,
                largeScreen: typeof window !== 'undefined' && window.matchMedia('(min-width: 1024px)').matches,
                bindMq() {
                    const mq = window.matchMedia('(min-width: 1024px)');
                    mq.addEventListener('change', () => { this.largeScreen = mq.matches; });
                }
            }"
            x-init="bindMq()"
            @settings-close-sections-menu.window="menuOpen = false"
            @keydown.escape.window="if (!largeScreen) menuOpen = false">

            <div class="shrink-0 border-b border-gray-100 p-4 dark:border-gray-700 bg-skin-base/5 dark:bg-skin-base/20">
                <button type="button"
                    class="flex w-full items-center justify-between gap-2 rounded-lg py-0.5 text-left lg:hidden "
                    @click="menuOpen = !menuOpen"
                    :aria-expanded="menuOpen"
                    aria-controls="settingsTabsScroll">
                    <span class="min-w-0 flex-1 text-sm font-semibold text-gray-900 dark:text-white">@lang('menu.settings')</span>
                    <svg class="h-5 w-5 shrink-0 text-gray-500 transition-transform dark:text-gray-400" :class="menuOpen && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <h1 class="hidden text-sm font-semibold text-gray-900 dark:text-white lg:block">@lang('menu.settings')</h1>
            </div>

            <div class="shrink-0 border-b border-gray-100 px-3 py-2 dark:border-gray-700"
                :class="!largeScreen && !menuOpen ? 'hidden' : 'block'">
                <label for="settingsTabSearch" class="sr-only">{{ __('app.search') }}</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                        <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a.75.75 0 101.06-1.06l-2.249-2.249A6 6 0 009 3zm-4.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input id="settingsTabSearch" type="search" autocomplete="off" placeholder="{{ __('app.search') }}"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 ps-9 pe-9 text-sm text-gray-900 shadow-sm outline-none transition focus:border-skin-base focus:ring-2 focus:ring-skin-base/40 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    <button id="settingsTabSearchClear" type="button"
                        class="absolute inset-y-0 end-1 my-auto hidden h-7 w-7 shrink-0 items-center justify-center rounded-md text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        aria-label="{{ __('app.clear') }}">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.53-10.47a.75.75 0 00-1.06-1.06L10 8.94 8.53 7.47a.75.75 0 10-1.06 1.06L8.94 10l-1.47 1.47a.75.75 0 101.06 1.06L10 11.06l1.47 1.47a.75.75 0 101.06-1.06L11.06 10l1.47-1.47z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <nav id="settingsTabsScroll"
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain max-lg:max-h-[min(70vh,28rem)] max-lg:[-webkit-overflow-scrolling:touch] [scrollbar-width:thin] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:rounded-full [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-200 [&::-webkit-scrollbar-thumb]:bg-gray-400 hover:[&::-webkit-scrollbar-thumb]:bg-gray-500 dark:[&::-webkit-scrollbar-track]:bg-gray-700 dark:[&::-webkit-scrollbar-thumb]:bg-gray-500 dark:hover:[&::-webkit-scrollbar-thumb]:bg-gray-400"
                :class="!largeScreen && !menuOpen ? 'hidden' : 'block'"
                aria-label="{{ __('menu.settings') }}">
            <div id="settingsTabsList" class="w-full min-w-0 pb-4"
                @click="if (!largeScreen && $event.target.closest('a[href]')) { menuOpen = false }">

                {{-- Vue d'ensemble --}}
                <a href="{{ route('settings.index') }}" wire:navigate
                    @class([
                        'mx-2 mt-2 flex items-center rounded-lg px-3 py-2 text-sm font-semibold transition',
                        'bg-skin-base/10 text-skin-base' => $activeSetting === 'home',
                        'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/50' => $activeSetting !== 'home',
                    ])>
                    @lang('modules.settings.overview')
                </a>

                {{-- Groupes de réglages --}}
                @foreach ($sections as $group => $items)
                    @php $groupActive = collect($items)->contains(fn ($i) => $i[0] === $activeSetting); @endphp
                    @if ($group === 'advanced')
                        <details data-settings-group class="mt-4 [&[open]_.jb-chevron]:rotate-180" @if ($groupActive) open @endif>
                            <summary class="mx-2 flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 [&::-webkit-details-marker]:hidden">
                                {{ $jbGroups[$group][0] }}
                                <svg class="jb-chevron h-4 w-4 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                            </summary>
                    @else
                        <div data-settings-group class="mt-4">
                            <p class="mx-2 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $jbGroups[$group][0] }}</p>
                    @endif
                            <ul class="mt-0.5">
                                @foreach ($items as [$tab, $label])
                                    <li data-settings-item>
                                        <a href="{{ route('settings.index') }}?tab={{ $tab }}" wire:navigate
                                            @if ($activeSetting === $tab) aria-current="page" @endif
                                            @class([
                                                'mx-2 block rounded-lg px-3 py-1.5 text-sm transition',
                                                'bg-skin-base/10 font-semibold text-skin-base' => $activeSetting === $tab,
                                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700/50 dark:hover:text-white' => $activeSetting !== $tab,
                                            ])>{{ $label }}</a>
                                    </li>
                                @endforeach
                                @if ($group === 'team' && $jbCanStaff)
                                    <li data-settings-item>
                                        <a href="{{ route('staff.index') }}" wire:navigate
                                            class="mx-2 flex items-center justify-between rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700/50 dark:hover:text-white">
                                            @lang('modules.settings.staffLink')
                                            <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                    @if ($group === 'advanced')
                        </details>
                    @else
                        </div>
                    @endif
                @endforeach

                <!-- NAV ITEM - CUSTOM MODULES  -->
                <ul data-settings-group class="mt-2">
                    @foreach (custom_module_plugins() as $item)
                        @includeIf(strtolower($item) . '::sections.settings.restaurant.sidebar')
                    @endforeach
                </ul>
            </div>
            </nav>
        </div>
    @once
        @push('scripts')
            <script>
                (function () {
                    function initSettingsTabSearch() {
                        const input = document.getElementById('settingsTabSearch');
                        const clearBtn = document.getElementById('settingsTabSearchClear');
                        const list = document.getElementById('settingsTabsList');
                        if (!input || !list) return;
                        if (input.dataset.bound === '1') return;
                        input.dataset.bound = '1';

                        const filter = () => {
                            const q = (input.value || '').trim().toLowerCase();
                            if (clearBtn) {
                                clearBtn.classList.toggle('hidden', q.length === 0);
                                clearBtn.classList.toggle('inline-flex', q.length > 0);
                            }

                            // Filtre les réglages, masque les groupes vides, déplie « Avancé » pendant la recherche
                            list.querySelectorAll('li').forEach((item) => {
                                const text = (item.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                                item.classList.toggle('hidden', q.length > 0 && !text.includes(q));
                            });
                            list.querySelectorAll('[data-settings-group]').forEach((group) => {
                                const visible = group.querySelector('li:not(.hidden)');
                                group.classList.toggle('hidden', q.length > 0 && !visible);
                                if (group.tagName === 'DETAILS' && q.length > 0 && visible) group.open = true;
                            });
                        };

                        input.addEventListener('input', filter);
                        input.addEventListener('search', filter);
                        if (clearBtn) {
                            clearBtn.addEventListener('click', () => {
                                input.value = '';
                                input.focus();
                                filter();
                            });
                        }
                        input.addEventListener('keydown', (e) => {
                            if (e.key === 'Escape') {
                                input.value = '';
                                filter();
                                input.blur();
                            }
                        });

                        filter();
                    }

                    document.addEventListener('DOMContentLoaded', initSettingsTabSearch);
                    document.addEventListener('livewire:navigated', () => {
                        initSettingsTabSearch();
                        window.dispatchEvent(new CustomEvent('settings-close-sections-menu'));
                    });
                })();
            </script>
        @endpush
    @endonce

        <div class="min-h-0 min-w-0 flex-1 space-y-5 py-4 px-0 lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain [scrollbar-width:thin] [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-track]:rounded-xl [&::-webkit-scrollbar-thumb]:rounded-xl [&::-webkit-scrollbar-track]:bg-gray-300 [&::-webkit-scrollbar-thumb]:bg-gray-400 hover:[&::-webkit-scrollbar-thumb]:bg-gray-500 dark:[&::-webkit-scrollbar-track]:bg-gray-700 dark:[&::-webkit-scrollbar-thumb]:bg-gray-500 dark:hover:[&::-webkit-scrollbar-thumb]:bg-gray-400">
            <div class="grid grid-cols-1 dark:bg-gray-900">

                <div>
                    @switch($activeSetting)
                        @case('home')
                        @include('livewire.settings.partials.overview', ['sections' => $sections, 'groups' => $jbGroups, 'canStaff' => $jbCanStaff])
                        @break

                        @case('restaurant')
                        @livewire('settings.generalSettings', ['settings' => $settings])
                        @break

                        @case('app')
                        @livewire('settings.timezoneSettings', ['settings' => $settings])
                        @break

                        @case('email')
                        @livewire('settings.notificationSettings', ['settings' => $settings])
                        @break

                        @case('currency')
                        @livewire('settings.currencySettings')
                        @break

                        @case('payment')
                        @livewire('settings.paymentSettings', ['settings' => $settings])
                        @break

                        @case('theme')
                        @livewire('settings.themeSettings', ['settings' => $settings])
                        @break

                        @case('role')
                        @livewire('settings.roleSettings', ['settings' => $settings])
                        @break

                        @case('tax')
                        @livewire('settings.taxSettings', ['settings' => $settings])
                        @break

                        @case('reservation')
                        @livewire('settings.reservationSettings')
                        @break

                        @case('branch')
                        @livewire('settings.branchSettings')
                        @break
                        @case('billing')
                        @livewire('settings.billingSettings')
                        @break

                        @case('aboutus')
                        @livewire('settings.aboutUsSettings', ['settings' => $settings])
                        @break

                        @case('customerSite')
                        @livewire('settings.customerSiteSettings', ['settings' => $settings])
                        @break

                        @case('welcome')
                        @livewire('settings.welcomePageSettings', ['settings' => $settings])
                        @break

                        @case('banners')
                        @livewire('settings.bannerSettings', ['settings' => $settings])
                        @break

                        @case('customerDisplay')
                        @livewire('settings.customerBoardSettings', ['settings' => $settings])
                        @break

                        @case('receipt')
                        @livewire('settings.ReceiptSetting', ['settings' => $settings])
                        @break

                        @case('printer')
                        @livewire('settings.PrinterSetting', ['settings' => $settings])
                        @break

                        @case('downloads')
                        @livewire('settings.DownloadSettings')
                        @break

                        @case('menuItemImageSettings')
                        @livewire('settings.menuItemImageSettings', ['settings' => $settings])
                        @break

                        @case('euAllergens')
                        @livewire('settings.euAllergensSettings', ['settings' => $settings])
                        @break

                        @case('deliverySettings')
                        @livewire('settings.branchDeliverySettings', ['settings' => $settings])
                        @break

                        @case('operationalShifts')
                        @livewire('settings.branchOperationalShifts', ['settings' => $settings])
                        @break

                        @case('restaurantOpenClose')
                        @if (user()->hasRole('Admin_'.user()->restaurant_id) || user_can('Show Restaurant Open/Close'))
                            @livewire('settings.restaurantOpenCloseSettings', ['settings' => $settings])
                        @endif
                        @break

                        @case('kotSettings')
                        @livewire('settings.kotSettings', ['settings' => $settings])
                        @break

                        @case('cancelSettings')
                        @livewire('settings.CancellationSettings', ['settings' => $settings])
                        @break

                        @case('orderSettings')
                        @livewire('settings.OrderSettings', ['settings' => $settings])
                        @break

                        @case('refundReasons')
                        @livewire('settings.RefundReasonSettings', ['settings' => $settings])
                        @break


                        @default

                    @endswitch

                    <!-- NAV ITEM - CUSTOM MODULES  -->
                    @foreach (custom_module_plugins() as $item)
                        @if($activeSetting == strtolower($item).'Settings')
                            @livewire(strtolower($item).'::restaurant.setting', ['settings' => $settings])
                        @endif
                @endforeach
                </div>

            </div>
        </div>
    </div>

</div>
