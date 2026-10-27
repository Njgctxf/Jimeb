<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column - Product Information -->
            <div class="space-y-4">
                <div class="bg-white space-y-4 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        @lang('modules.menu.productInformation')
                    </h3>
                    <!-- Language Selection -->
                    @if(count($languages) > 1)
                    <div class="mb-4">
                        <x-label for="language" :value="__('modules.menu.selectLanguage')" />
                        <div class="relative mt-1">
                            @php
                            $languageSettings = collect(App\Models\LanguageSetting::LANGUAGES)
                            ->keyBy('language_code')
                            ->map(function ($lang) {
                            return [
                            'flag_url' => asset('flags/1x1/' . strtolower($lang['flag_code']) . '.svg'),
                            'name' => App\Models\LanguageSetting::LANGUAGES_TRANS[$lang['language_code']] ??
                            $lang['language_name']
                            ];
                            });
                            @endphp
                            <x-select class="block w-full pl-10" wire:model.live="currentLanguage">
                                @foreach($languages as $code => $name)
                                <option value="{{ $code }}"
                                    data-flag="{{ $languageSettings->get($code)['flag_url'] ?? asset('flags/1x1/' . strtolower($code) . '.svg') }}"
                                    class="flex items-center py-2" wire:key="lang-option-{{ $name }}">
                                    {{ $languageSettings->get($code)['name'] ?? $name }}
                                </option>
                                @endforeach
                            </x-select>

                            {{-- Current Selected Flag --}}
                            @php
                            $currentFlagCode = collect(App\Models\LanguageSetting::LANGUAGES)
                            ->where('language_code', $currentLanguage)
                            ->first()['flag_code'] ?? $currentLanguage;
                            @endphp
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <img src="{{ asset('flags/1x1/' . strtolower($currentFlagCode) . '.svg') }}"
                                     alt="{{ $currentLanguage }}" class="w-5 h-5 rounded-sm object-cover" />
                            </div>
                        </div>
                    </div>
                    @endif


                    <!-- Item Name and Description with Translation -->
                    <div class="mb-4">
                        <x-label for="itemName" :value="__('modules.menu.itemName') . ' (' . $languages[$currentLanguage] . ')'" />
                        <x-input id="itemName" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.menuItemNamePlaceholder') }}" wire:model.defer="itemName" wire:change="updateTranslation" />
                        <x-input-error for="translationNames.{{ $globalLocale }}" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="itemDescription" :value="__('modules.menu.itemDescription') . ' (' . $languages[$currentLanguage] . ')'" />
                        <x-textarea class="block mt-1 w-full" :placeholder="__('placeholders.itemDescriptionPlaceholder')" wire:model='itemDescription'
                            rows='2' wire:change="updateTranslation" data-gramm="false" />
                        <x-input-error for="itemDescription" class="mt-2" />
                    </div>

                    <!-- Translation Preview -->
                    <div>
                        @if(count($languages) > 1 && (array_filter($translationNames) ||
                        array_filter($translationDescriptions)))
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                            <x-label :value="__('modules.menu.translations')" class="text-sm mb-2 last:mb-0" />
                            <div class="divide-y divide-gray-200 dark:divide-gray-600">
                                @foreach($languages as $lang => $langName)
                                @if(!empty($translationNames[$lang]) || !empty($translationDescriptions[$lang]))
                                <div class="flex flex-col gap-1.5 py-2"
                                     wire:key="translation-details-{{ $loop->index }}">
                                    <div class="flex items-center gap-3">
                                        <span class="min-w-[80px] text-xs font-medium text-gray-600 dark:text-gray-300">
                                            {{ $languageSettings->get($lang)['name'] ?? strtoupper($lang) }}
                                        </span>
                                        <div class="flex-1">
                                            @if(!empty($translationNames[$lang]))
                                            <div class="mb-1">
                                                <span
                                                     class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('app.name'):</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-200 ml-1">{{
                                                    $translationNames[$lang] }}</span>
                                            </div>
                                            @endif
                                            @if(!empty($translationDescriptions[$lang]))
                                            <div>
                                                <span
                                                     class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('app.description'):</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-200 ml-1">{{
                                                    $translationDescriptions[$lang] }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4">
@unless (config('jimeb.simple_mode'))
                            <div>
                                <x-label for="menu" :value="__('modules.menu.chooseMenu')" />
                                <x-select id="menu" class="mt-1 block w-full" wire:model.defer="menu">
                                    <option value="">--</option>
                                    @foreach ($menus as $item)
                                    <option value="{{ $item->id }}">{{ $item->menu_name }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error for="menu" class="mt-2" />
                            </div>
@endunless

                            
                        </div>

                        {{-- Le type de plat n'est plus demandé : valeur par défaut « autre » --}}

                        @include('livewire.forms.partials.menu-item-badges', ['badgeDomPrefix' => 'upd-badge'])
                        @unless (config('jimeb.simple_mode'))
                        @include('livewire.forms.partials.menu-item-dietary-label-chips', ['dietaryDomPrefix' => 'update-mi'])
                        @endunless

                        @include('livewire.forms.partials.menu-item-eu-allergen-chips', ['euAllergenDomPrefix' => 'update-mi'])

                        <!-- Additional Settings -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label :value="__('modules.menu.preparationTime')" />
                                <div class="mt-1 grid grid-cols-2 gap-2">
                                    <x-select id="prepHours" class="block w-full" wire:model.defer="prepHours" aria-label="h">
                                        @for ($h = 0; $h <= 23; $h++)
                                            <option value="{{ $h }}">{{ $h }} h</option>
                                        @endfor
                                    </x-select>
                                    <x-select id="prepMinutes" class="block w-full" wire:model.defer="prepMinutes" aria-label="min">
                                        @for ($m = 0; $m <= 59; $m++)
                                            <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }} min</option>
                                        @endfor
                                    </x-select>
                                </div>
                                <x-input-error for="prepHours" class="mt-2" />
                                <x-input-error for="prepMinutes" class="mt-2" />
                            </div>

                            @unless (config('jimeb.simple_mode'))

                            <div>
                                <x-label for="isAvailable" :value="__('modules.menu.isAvailable')" />
                                <x-select id="isAvailable" class="mt-1 block w-full" wire:model.live="isAvailable">
                                    <option value="1">@lang('app.yes')</option>
                                    <option value="0">@lang('app.no')</option>
                                </x-select>
                                <x-input-error for="isAvailable" class="mt-2" />
                            </div>

                            @endunless
                        </div>

                        @if ((module_enabled('Inventory') && in_array('Inventory', restaurant_modules())))
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label for="inStock" :value="__('modules.menu.inStock')" />
                                <x-select id="inStock" class="mt-1 block w-full" wire:model.defer="inStock">
                                    <option value="1">@lang('app.yes')</option>
                                    <option value="0">@lang('app.no')</option>
                                </x-select>
                                <x-input-error for="inStock" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="showOnCustomerSite" :value="__('modules.menu.showOnCustomerSite')" />
                                <x-select id="showOnCustomerSite" class="mt-1 block w-full" wire:model.live="showOnCustomerSite">
                                    <option value="1">@lang('app.yes')</option>
                                    <option value="0">@lang('app.no')</option>
                                </x-select>
                                <x-input-error for="showOnCustomerSite" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-label for="directSaleInventoryItemId" :value="__('modules.menu.directSaleSku')" />
                            <x-select id="directSaleInventoryItemId" class="mt-1 block w-full" wire:model.defer="directSaleInventoryItemId">
                                <option value="">@lang('modules.menu.directSaleSkuNone')</option>
                                @foreach($inventoryItemsForDirectSale as $invItem)
                                    <option value="{{ $invItem->id }}">
                                        {{ $invItem->name }}@if($invItem->unit) ({{ $invItem->unit->symbol }})@endif
                                    </option>
                                @endforeach
                            </x-select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('modules.menu.directSaleSkuHelp')</p>
                            <x-input-error for="directSaleInventoryItemId" class="mt-2" />
                        </div>
                        @else
                        <div>
                            <x-label for="showOnCustomerSite" :value="__('modules.menu.showOnCustomerSite')" />
                            <x-select id="showOnCustomerSite" class="mt-1 block w-full" wire:model.live="showOnCustomerSite">
                                <option value="1">@lang('app.yes')</option>
                                <option value="0">@lang('app.no')</option>
                            </x-select>
                            <x-input-error for="showOnCustomerSite" class="mt-2" />
                        </div>
                        @endif

                        @if (in_array('Kitchen', restaurant_modules()))
                        <div>
                            <x-label for="kitchenType" :value="__('modules.menu.kitchenType')" />
                            <x-select id="kitchenType" class="mt-1 block w-full" wire:model.defer="kitchenType">
                                <option value="">@lang('modules.menu.SelectKitchenType')</option>
                                @foreach($kitchenTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error for="kitchenType" class="mt-2" />
                        </div>
                        @endif

                        @include('livewire.forms.partials.menu-item-media', ['mediaMode' => 'update'])
                    </div>

                </div>


                <!-- tax modes here work-->
                @if ($isTaxModeItem)
                <!-- Tax Settings Section -->
                <div
                     class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                 d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                            </path>
                        </svg>
                        @lang('modules.menu.taxSettings')
                    </h3>
                    <div class="mb-4">
                        <x-label for="selectedTaxes" class="mb-1" :value="__('modules.menu.selectTaxes')" />
                        <div x-data="{
                        isOpen: false,
                        selectedTaxes: @entangle('selectedTaxes').live,
                    }" @click.away="isOpen = false" wire:key="tax-selector">
                            <div class="relative">
                                <div @click="isOpen = !isOpen"
                                     class="w-full flex items-center justify-between bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 cursor-pointer">
                                    <div class="flex flex-wrap gap-1">
                                        @if(empty($selectedTaxes))
                                        <span
                                             class="text-gray-500 dark:text-gray-400">@lang('modules.menu.selectTaxes')</span>
                                        @else
                                        @foreach(collect($taxes)->whereIn('id', $selectedTaxes) as $tax)
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-600 rounded-md text-sm mr-1 flex items-center"
                                             wire:key="tax-badge-{{ $tax->id }}">
                                            {{ $tax->tax_name }} ({{ $tax->tax_percent }}%)
                                        </span>
                                        @endforeach
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>

                                <!-- Dropdown menu -->
                                <div x-show="isOpen" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md overflow-auto">
                                    <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">@lang('modules.menu.selectTaxes')</span>
                                    </div>
                                    <ul class="py-1">
                                        @foreach($taxes as $tax)
                                        <li class="px-2" wire:key="tax-option-{{ $tax->id }}">
                                            <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md cursor-pointer">
                                                <input type="checkbox" value="{{ $tax->id }}" x-model="selectedTaxes"
                                                    @click="$wire.set('selectedTaxes', selectedTaxes);"
                                                    class="rounded border-gray-300 text-skin-base shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                                <span class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $tax->tax_name }} ({{ $tax->tax_percent }}%)
                                                </span>
                                            </label>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <x-input-error for="selectedTaxes" class="mt-2" />
                    </div>
                </div>
                @endif

                @include('livewire.forms.partials.side-groups')

                <!-- Action Buttons -->
                <div class="flex w-full space-x-3 mt-2">
                    <x-button wire:loading.attr="disabled" wire:target="submitForm" class="flex-1">
                        <span wire:loading.remove wire:target="submitForm">@lang('app.save')</span>
                        <span wire:loading wire:target="submitForm" class="flex items-center">
                            Saving...
                        </span>
                    </x-button>
                    <x-secondary-link href="{{ route('menu-items.index') }}" wire:navigate wire:loading.attr="disabled" wire:target="submitForm"
                        class="flex-1">@lang('app.cancel')</x-secondary-link>
                </div>
            </div>

            <!-- Right Column - Pricing Details -->
            <div class="lg:col-span-1 space-y-4">
                <!-- Pricing Configuration -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-500" width="24" height="24" viewBox="0 0 24 24" stroke="currentColor" fill="currentColor" xmlns="http://www.w3.org/2000/svg" data-name="Layer 1"><path d="M9.5 10.5H12a1 1 0 0 0 0-2h-1V8a1 1 0 0 0-2 0v.55a2.5 2.5 0 0 0 .5 4.95h1a.5.5 0 0 1 0 1H8a1 1 0 0 0 0 2h1v.5a1 1 0 0 0 2 0v-.55a2.5 2.5 0 0 0-.5-4.95h-1a.5.5 0 0 1 0-1M21 12h-3V3a1 1 0 0 0-.5-.87 1 1 0 0 0-1 0l-3 1.72-3-1.72a1 1 0 0 0-1 0l-3 1.72-3-1.72a1 1 0 0 0-1 0A1 1 0 0 0 2 3v16a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1M5 20a1 1 0 0 1-1-1V4.73l2 1.14a1.08 1.08 0 0 0 1 0l3-1.72 3 1.72a1.08 1.08 0 0 0 1 0l2-1.14V19a3 3 0 0 0 .18 1Zm15-1a1 1 0 0 1-2 0v-5h2Z"/></svg>
                        </svg>
                        @lang('modules.menu.pricingDetails')
                    </h3>

@if (! config('jimeb.simple_mode') || $hasVariations)
                    <!-- Variations Toggle Switch -->
                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <x-checkbox name="hasVariations" id="hasVariations" wire:model.live='hasVariations' wire:change="checkVariations()" />
                        <div class="ml-3 flex-1">
                            <x-label for="hasVariations" :value="__('modules.menu.hasVariations')" class="!mb-1 font-medium" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.menu.hasVariationsHelp')</p>
                        </div>
                    </div>
@endif

                    <!-- Variations Management Section -->
                    @if(!$showItemPrice)
                    <div class="space-y-4 mt-4" x-data="{ openVariation: null }">
                        <!-- Variations List -->
                        @foreach($inputs as $key => $value)
                        <div wire:key="variation-full-details-{{ $key }}">
                        <div @class([
                            'bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 transition-all duration-200',
                            'ring-1 ring-skin-base shadow-md' => 'openVariation === ' . $key
                        ])>

                            <!-- Variation Header - Clickable -->
                            <div class="cursor-pointer" @click="openVariation = openVariation === {{ $key }} ? null : {{ $key }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400 transition-transform duration-200"
                                             :class="openVariation === {{ $key }} ? 'rotate-90' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-white">
                                                    {{ !empty($variationName[$key]) ? $variationName[$key] : 'Variation ' . ($key + 1) }}
                                                </h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <span class="font-medium">@lang('modules.menu.setPrice'):</span> {{ restaurant()->currency->currency_symbol }}{{ !empty($variationPrice[$key]) ? $variationPrice[$key] : '0.00' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remove Button - Only shown when there are more than 1 variations -->
                                    @if(count($inputs) > 1)
                                    <button type="button"
                                            wire:click.stop="removeField({{ $key }})" wire:key='remove-variation-{{ $key.rand() }}'
                                            class="p-2 text-red-500 hover:text-red-700 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-md transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Expanded Content -->
                            <div x-show="openVariation === {{ $key }}"
                                x-cloak
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-4">

                                <!-- Variation Name and Price -->
                                <div class="grid grid-cols-2 gap-4" wire:key='variation-item-number-{{ $value }}'>
                                    <div>
                                        <x-label for="variationName.{{ $key }}" :value="__('modules.menu.variationName')" />
                                        <x-input id="variationName.{{ $key }}" class="block mt-1 w-full" type="text"
                                            :placeholder="__('placeholders.itemVariationPlaceholder')"
                                            wire:model.blur='variationName.{{ $key }}' />
                                        <x-input-error for="variationName.{{ $key }}" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-label for="variationPrice.{{ $key }}" :value="__('modules.menu.setPrice')" />
                                        <div class="relative rounded-md mt-1">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                            </div>
                                            <x-input id="variationPrice.{{ $key }}" type="number" step="0.001" min="0"
                                                wire:model.live='variationPrice.{{ $key }}'
                                                class="block w-full rounded pl-10 text-gray-900 placeholder:text-gray-400" placeholder="0.00" />
                                        </div>
                                        <x-input-error for="variationPrice.{{ $key }}" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Order Types Pricing -->
                                @if($orderTypes->isNotEmpty())
                                <div>
                                    <x-label :value="__('modules.menu.orderTypesPricing')" class="mb-3 text-base font-semibold" />
                                    <div class="space-y-2">
                                        @foreach($orderTypes->reject(fn($type) => strtolower($type->slug ?? $type->name) === 'delivery') as $orderType)
                                        <div wire:key="variation-order-type-{{ $key }}-{{ $orderType->id }}">
                                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                                <div class="inline-flex items-center space-x-2 rtl:space-x-reverse">
                                                    <div @class(['w-3 h-3 rounded-full flex-shrink-0', $this->orderTypeColor($orderType->id)])></div>
                                                    <span class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $orderType->order_type_name }}</span>
                                                </div>
                                                <div class="flex items-center gap-1 flex-shrink-0">
                                                    <button type="button" wire:click="toggleVariationOrderTypePriceLink({{ $key }}, {{ $orderType->id }})"
                                                        class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                                        title="{{ ($variationLinkedOrderTypePrices[$key][$orderType->id] ?? true) ? __('modules.menu.syncedWithBasePrice') : __('modules.menu.customPrice') }}">
                                                        @if($variationLinkedOrderTypePrices[$key][$orderType->id] ?? true)
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock w-3.5 h-3.5 text-skin-base" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
                                                        </svg>
                                                        @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock w-3.5 h-3.5 text-gray-400" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M12 0a4 4 0 0 1 4 4v2.5h-1V4a3 3 0 1 0-6 0v2h.5A2.5 2.5 0 0 1 12 8.5v5A2.5 2.5 0 0 1 9.5 16h-7A2.5 2.5 0 0 1 0 13.5v-5A2.5 2.5 0 0 1 2.5 6H8V4a4 4 0 0 1 4-4M2.5 7A1.5 1.5 0 0 0 1 8.5v5A1.5 1.5 0 0 0 2.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 9.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
                                                        </svg>
                                                        @endif
                                                    </button>
                                                    <div class="relative">
                                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                            <span class="text-gray-500 text-sm">{{ restaurant()->currency->currency_symbol }}</span>
                                                        </div>
                                                        <x-input type="number" step="0.001" min="0"
                                                                wire:model.blur="variationOrderTypePrices.{{ $key }}.{{ $orderType->id }}"
                                                                class="block pl-8 pr-3 w-32"
                                                                placeholder="0.00" />
                                                    </div>
                                                </div>
                                            </div>
                                            <x-input-error for="variationOrderTypePrices.{{ $key }}.{{ $orderType->id }}" class="mt-2" />
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <!-- Delivery Platforms -->
                                @if($deliveryApps->isNotEmpty())
                                <div>
                                    <x-label :value="__('modules.menu.deliveryPlatforms')" class="mb-3 text-base font-semibold" />
                                    <div class="space-y-2">
                                        <!-- Base Delivery Price -->
                                        <div>
                                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-200" fill="currentColor" height="20" viewBox="0 0 64 64" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M4 16h14.001a3 3 0 0 1 3 3v11.001a3 3 0 0 1-3 3h-14a3 3 0 0 1-3.001-3v-11a3 3 0 0 1 3-3"/><circle cx="33.002" cy="7" r="5"/><path d="M12.003 35.852a5.92 5.92 0 0 0 1.7 4.15H29.96v-4.155a.996.996 0 0 0-.996-.996H12.998a1 1 0 0 0-.995 1.001"/><path d="M61.737 51.359a8.13 8.13 0 0 0-8.322-5.994 7 7 0 0 0 .24-1.791A5.93 5.93 0 0 0 51 38.75c-2.147-1.425-3.753-5.048-3.996-8.858h1.916a2.99 2.99 0 0 0 2.991-2.982v-1.986a2.99 2.99 0 0 0-2.991-2.982h-6.84c-5.782-1.665-7.522-3.583-8.561-4.732l-.063-.07a3.71 3.71 0 0 0-2.018-3.813 3.64 3.64 0 0 0-5.122 2.497l-2.869 13.71a2.983 2.983 0 0 0 2.598 3.571l4.917.544a.994.994 0 0 1 .887 1.043l-.774 13.106a5.27 5.27 0 0 1-1.477-5.796H14.313c-1.612 2.671-4.193 7.679-3.149 10.936a4.04 4.04 0 0 0 2.609 2.622 3.7 3.7 0 0 0 1.39.15 6.406 6.406 0 0 0 12.78 0h17.14a1.26 1.26 0 0 0 .875-.423 7 7 0 0 0 .587 1.703.996.996 0 0 0 1.716.14q.176-.25.376-.491a6.4 6.4 0 1 0 12.484-2.718.986.986 0 0 0 .875-1.075 8 8 0 0 0-.26-1.487m-40.184 8.318a4.407 4.407 0 0 1-4.385-3.967h8.77a4.407 4.407 0 0 1-4.385 3.967M40.94 48.754h-3.885l1.718-16.24a2.98 2.98 0 0 0-1.926-3.104l-4.9-1.829a.99.99 0 0 1-.622-1.149l.745-3.215a17.1 17.1 0 0 0 8.87 3.633zm14.586 11.218a4.413 4.413 0 0 1-4.961-4.86l.304-.38a11.08 11.08 0 0 1 7.676-1.51l.236.183a4.4 4.4 0 0 1-3.255 6.567"/></svg>
                                                    <span class="font-medium text-gray-900 dark:text-white text-sm">@lang('modules.menu.baseDeliveryPrice')</span>
                                                </div>
                                                <div class="flex items-center gap-1 flex-shrink-0">
                                                    <button type="button" wire:click="toggleVariationDeliveryPriceLink({{ $key }})"
                                                        class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                                        title="{{ ($variationLinkedDeliveryPrice[$key] ?? true) ? __('modules.menu.syncedWithBasePrice') : __('modules.menu.customPrice') }}">
                                                        @if($variationLinkedDeliveryPrice[$key] ?? true)
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock w-3.5 h-3.5 text-skin-base" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
                                                        </svg>
                                                        @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock w-3.5 h-3.5 text-gray-400" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M12 0a4 4 0 0 1 4 4v2.5h-1V4a3 3 0 1 0-6 0v2h.5A2.5 2.5 0 0 1 12 8.5v5A2.5 2.5 0 0 1 9.5 16h-7A2.5 2.5 0 0 1 0 13.5v-5A2.5 2.5 0 0 1 2.5 6H8V4a4 4 0 0 1 4-4M2.5 7A1.5 1.5 0 0 0 1 8.5v5A1.5 1.5 0 0 0 2.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 9.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
                                                        </svg>
                                                        @endif
                                                    </button>
                                                    <div class="relative">
                                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                            <span class="text-gray-500 text-sm">{{ restaurant()->currency->currency_symbol }}</span>
                                                        </div>
                                                        <x-input type="number" step="0.001"
                                                                wire:model.live="variationBaseDeliveryPrice.{{ $key }}"
                                                                class="block pl-8 pr-3 w-32"
                                                                min="0"
                                                                placeholder="0.00" />
                                                    </div>
                                                </div>
                                            </div>
                                            <x-input-error for="variationBaseDeliveryPrice.{{ $key }}" class="mt-2" />
                                        </div>

                                        <!-- Delivery Apps -->
                                        @foreach($deliveryApps as $app)
                                        <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600" wire:key="delivery-app-{{ $key }}-{{ $app->id }}">
                                            <div  class="flex items-center space-x-3 rtl:space-x-reverse">
                                                <div class="w-8 h-8">
                                                    @if($app->logo)
                                                    <img class="w-8 h-8 rounded-lg object-cover border border-gray-200"
                                                        src="{{ $app->logo_url ?? asset('images/default-logo.png') }}"
                                                        alt="{{ $app->name }}">
                                                    @else
                                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="font-medium text-gray-900 dark:text-white text-sm">{{ $app->name }}</span>
                                                    <div class="text-xs text-gray-500">
                                                        Commission: {{ $app->commission_value ?? 0 }}%
                                                    </div>
                                                </div>
                                            </div>
                                            <div  class="flex items-center space-x-3 rtl:space-x-reverse">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox"
                                                        wire:model.defer="variationPlatformAvailability.{{ $key }}.{{ $app->id }}"
                                                        class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                                </label>
                                                <div class="text-right">
                                                    <div class="font-semibold text-sm text-gray-900 dark:text-white">
                                                        {{ restaurant()->currency->currency_symbol }}{{ $variationDeliveryPrices[$key][$app->id] ?? '0.00' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">Final</div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <!-- Tax Breakdown -->
                                @if($isTaxModeItem && !empty($variationBreakdowns[$key]['breakdown']))
                                <div class="bg-gray-100 dark:bg-gray-600 rounded-lg p-3" wire:key="variation-breakdown-{{ $key }}">
                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.taxBreakdown')</h5>
                                    <div class="text-xs space-y-1">
                                        <div class="flex justify-between">
                                            <span>@lang('modules.menu.basePrice'):</span>
                                            <span>{{ restaurant()->currency->currency_symbol }}{{ $variationBreakdowns[$key]['breakdown']['base_price'] ?? '0.00' }}</span>
                                        </div>
                                        @if(!empty($variationBreakdowns[$key]['breakdown']['tax_breakdown']))
                                        @foreach($variationBreakdowns[$key]['breakdown']['tax_breakdown'] as $tax)
                                        <div class="flex justify-between text-gray-600 dark:text-gray-400" wire:key="tax-{{ $key }}-{{ $tax['id'] }}">
                                            <span>{{ $tax['name'] }} ({{ $tax['rate'] }}%):</span>
                                            <span>{{ restaurant()->currency->currency_symbol }}{{ $tax['amount'] }}</span>
                                        </div>
                                        @endforeach
                                        @endif
                                        <div class="flex justify-between font-medium pt-1 border-t border-gray-300 dark:border-gray-500">
                                            <span>@lang('modules.menu.total'):</span>
                                            <span>{{ restaurant()->currency->currency_symbol }}{{ $variationBreakdowns[$key]['breakdown']['final_price'] ?? '0.00' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        </div>
                        @endforeach

                        <!-- Add Variation Button -->
                        @if ($hasVariations)
                        <x-secondary-button wire:click="addMoreField({{ $i }})" wire:key='add-variation-{{ $i }}' class="w-full">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            @lang('modules.menu.addVariations')
                        </x-secondary-button>
                        @endif
                    </div>
                    @endif

                    <!-- Order Type Pricing Section -->
                    @if ($showItemPrice)
                    <div class="space-y-6 mt-4" wire:key="simple-pricing-section">
                        <!-- Default Price (itemPrice) -->
                        <div>
                            <x-label for="itemPrice" :value="$onPromo ? __('modules.menu.normalPrice') : 'Prix du plat'" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="itemPrice" type="number" step="0.001" min="0" wire:model.live.debounce.500ms="itemPrice" class="block w-full pl-10 pr-4" placeholder="0.00" />
                            </div>
                            <x-input-error for="itemPrice" class="mt-1" />
                            
                        </div>

                        <!-- Promotion : coche puis prix promo -->
                        <div>
                            <label for="onPromo" class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700">
                                <x-checkbox id="onPromo" name="onPromo" wire:model.live="onPromo" />
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Ce plat est en promotion</span>
                            </label>
                        </div>

                        @if ($onPromo)
                        <div>
                            <x-label for="promoPrice" value="Prix en promotion" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="promoPrice" type="number" step="0.001" min="0" wire:model.defer="promoPrice" class="block w-full pl-10 pr-4" placeholder="0.00" />
                            </div>
                            <x-input-error for="promoPrice" class="mt-1" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Le client verra ce prix. Le prix normal apparaîtra barré.</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- For Normal Price breakdowns -->
                    @if (!$hasVariations && $taxInclusivePriceDetails)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mt-4" wire:key="tax-details-section">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            @lang('modules.menu.taxBreakdown')</h4>
                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">@lang('modules.menu.basePrice'):</span>
                                <span class="font-medium">{{ currency_format($taxInclusivePriceDetails['base_raw'] ?? 0,
                                    restaurant()->currency_id) }}</span>
                            </div>
                            @if(!empty($taxInclusivePriceDetails['tax_breakdown']))
                            <div class="ml-2 my-1">
                                @foreach($taxInclusivePriceDetails['tax_breakdown'] as $tax)
                                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>{{ $tax['name'] }} ({{ $tax['rate'] }}%)</span>
                                    <span>{{ currency_format($tax['amount'], restaurant()->currency_id) }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">@lang('modules.menu.tax') ({{
                                    $taxInclusivePriceDetails['tax_percent'] }}%):</span>
                                <span class="font-medium">{{ currency_format($taxInclusivePriceDetails['tax_raw'] ?? 0,
                                    restaurant()->currency_id) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600 mt-2">
                                <span
                                     class="text-gray-700 dark:text-gray-300 font-semibold">@lang('modules.menu.total'):</span>
                                <span class="font-semibold text-lg">{{
                                    currency_format($taxInclusivePriceDetails['total_raw'] ?? 0,
                                    restaurant()->currency_id) }}</span>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                     d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @if($taxInclusivePriceDetails['inclusive'])
                            <span>
                                @lang('modules.menu.taxInclusiveInfo', [
                                'percent' => $taxInclusivePriceDetails['tax_percent'],
                                'tax' => currency_format($taxInclusivePriceDetails['tax_raw'] ?? 0,
                                restaurant()->currency_id),
                                'base' => currency_format($taxInclusivePriceDetails['base_raw'] ?? 0,
                                restaurant()->currency_id)
                                ])
                            </span>
                            @else
                            <span>
                                @lang('modules.menu.taxExclusiveInfo', [
                                'percent' => $taxInclusivePriceDetails['tax_percent'],
                                'tax' => currency_format($taxInclusivePriceDetails['tax_raw'] ?? 0,
                                restaurant()->currency_id),
                                'base' => currency_format($taxInclusivePriceDetails['base_raw'] ?? 0,
                                restaurant()->currency_id)
                                ])
                            </span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <x-dialog-modal wire:model.live="showMenuCategoryModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.menu.itemCategory')
        </x-slot>

        <x-slot name="content">
            @livewire('forms.addItemCategory')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showMenuCategoryModal')" wire:loading.attr="disabled">
                @lang('app.cancel')</x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function () {
                    const select = document.querySelector('select[wire\\:model\\.live="currentLanguage"]');

                    if (select) {
                        // Style the select options with flags when using a custom select library
                        if (typeof Choices !== 'undefined') {
                            new Choices(select, {
                                templateResult: function(option) {
                                    if (!option.element) return option.text;

                                    return $(`
                                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                            <img src="${option.element.dataset.flag}" class="w-5 h-5 rounded-sm" />
                                            <span>${option.text}</span>
                                        </div>
                                    `);
                                }
                            });
                        }
                    }
                });
    </script>
    @endpush
</div>
