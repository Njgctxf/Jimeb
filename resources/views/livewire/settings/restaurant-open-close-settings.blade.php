<div>
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-2 text-xl font-semibold dark:text-white">@lang('modules.settings.restaurantOpenCloseSettings')</h3>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.restaurantOpenCloseSettingsInfo')</p>

        @if(!$openCloseColumnsAvailable)
            <div class="mb-4 p-3 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                @lang('messages.restaurantOpenCloseSchemaMissing')
            </div>
        @endif

        <form wire:submit="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label @class([
                    'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                    'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $openCloseAuto,
                    'border-gray-200 dark:border-gray-700' => !$openCloseAuto
                ])>
                    <div class="flex items-center justify-between mb-2">
                        <span @class([
                            'font-medium',
                            'text-skin-base' => $openCloseAuto,
                            'text-gray-900 dark:text-white' => !$openCloseAuto
                        ])>
                            @lang('modules.settings.openCloseAutoMode')
                        </span>
                        <x-checkbox wire:model.live="openCloseAuto" />
                    </div>
                </label>

                <label @class([
                    'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                    'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $openCloseManual,
                    'border-gray-200 dark:border-gray-700' => !$openCloseManual
                ])>
                    <div class="flex items-center justify-between mb-2">
                        <span @class([
                            'font-medium',
                            'text-skin-base' => $openCloseManual,
                            'text-gray-900 dark:text-white' => !$openCloseManual
                        ])>
                            @lang('modules.settings.openCloseManualMode')
                        </span>
                        <x-checkbox wire:model.live="openCloseManual" />
                    </div>
                </label>
            </div>

            @if($openCloseManual)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label @class([
                        'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                        'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $manualUseToggle,
                        'border-gray-200 dark:border-gray-700' => !$manualUseToggle
                    ])>
                        <div class="flex items-center justify-between">
                            <span @class([
                                'font-medium',
                                'text-skin-base' => $manualUseToggle,
                                'text-gray-900 dark:text-white' => !$manualUseToggle
                            ])>
                                @lang('modules.settings.manualControlByToggle')
                            </span>
                            <x-checkbox wire:model.live="manualUseToggle" />
                        </div>
                    </label>

                    <label @class([
                        'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                        'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $manualUseTime,
                        'border-gray-200 dark:border-gray-700' => !$manualUseTime
                    ])>
                        <div class="flex items-center justify-between">
                            <span @class([
                                'font-medium',
                                'text-skin-base' => $manualUseTime,
                                'text-gray-900 dark:text-white' => !$manualUseTime
                            ])>
                                @lang('modules.settings.manualControlByTime')
                            </span>
                            <x-checkbox wire:model.live="manualUseTime" />
                        </div>
                    </label>
                </div>

                @if($manualUseToggle)
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        @lang('modules.settings.manualControlByToggleInfo')
                    </p>
                @endif

                @if($manualUseTime)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-label for="manualOpenTime" value="{{ __('modules.settings.manualOpenTime') }}" />
                            <input
                                id="manualOpenTime"
                                type="time"
                                step="60"
                                class="block mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-skin-base focus:ring-skin-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                wire:model.live="manualOpenTime"
                                onclick="this.showPicker && this.showPicker()"
                                onfocus="this.showPicker && this.showPicker()"
                            />
                            <x-input-error for="manualOpenTime" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="manualCloseTime" value="{{ __('modules.settings.manualCloseTime') }}" />
                            <input
                                id="manualCloseTime"
                                type="time"
                                step="60"
                                class="block mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-skin-base focus:ring-skin-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                wire:model.live="manualCloseTime"
                                onclick="this.showPicker && this.showPicker()"
                                onfocus="this.showPicker && this.showPicker()"
                            />
                            <x-input-error for="manualCloseTime" class="mt-2" />
                        </div>
                    </div>
                @endif
            @endif

            <label @class([
                'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $isTemporarilyClosed,
                'border-red-300 dark:border-red-700' => !$isTemporarilyClosed && (count($closeBlockTables) > 0 || count($closeBlockOrders) > 0),
                'border-gray-200 dark:border-gray-700' => !$isTemporarilyClosed && count($closeBlockTables) === 0 && count($closeBlockOrders) === 0
            ])>
                <div class="flex items-center justify-between mb-2">
                    <span @class([
                        'font-medium',
                        'text-skin-base' => $isTemporarilyClosed,
                        'text-gray-900 dark:text-white' => !$isTemporarilyClosed
                    ])>
                        @lang('modules.settings.temporarilyCloseRestaurant')
                    </span>
                    <x-checkbox wire:model.live="isTemporarilyClosed" />
                </div>
            </label>

            @if (count($closeBlockTables) > 0 || count($closeBlockOrders) > 0)
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20" role="alert">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        @lang('modules.settings.cannotCloseRestaurantTitle')
                    </p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                        @lang('modules.settings.cannotCloseRestaurantDetail')
                    </p>

                    <div class="mt-3 space-y-3">
                        @if (count($closeBlockTables) > 0)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">
                                    @lang('modules.settings.cannotCloseOpenTables')
                                </p>
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($closeBlockTables as $table)
                                        <li class="inline-flex items-center rounded-md bg-white px-2.5 py-1 text-sm font-medium text-red-800 dark:bg-red-950/40 dark:text-red-200">
                                            {{ $table['table_code'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (count($closeBlockOrders) > 0)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">
                                    @lang('modules.settings.cannotCloseUnpaidBilledOrders')
                                </p>
                                <ul class="mt-2 space-y-1.5">
                                    @foreach ($closeBlockOrders as $order)
                                        <li class="text-sm text-red-800 dark:text-red-200">
                                            {{ $order['show_formatted_order_number'] ?? (__('modules.order.orderNumber') . ' #' . $order['order_number']) }}
                                            @if (!empty($order['table_code']))
                                                <span class="text-red-600 dark:text-red-400">({{ $order['table_code'] }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div>
                <x-button>@lang('app.save')</x-button>
            </div>
        </form>
    </div>
</div>
