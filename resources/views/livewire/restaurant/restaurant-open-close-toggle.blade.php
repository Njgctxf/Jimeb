<div>
    @if ($showToggle)
        @php
            $tooltipId = 'restaurant-open-close-tooltip';
            $tooltipText = $isRestaurantOpen
                ? __('modules.settings.manualToggleCloseTooltip')
                : __('modules.settings.manualToggleOpenTooltip');
            $confirmHeading = $closeBlocked
                ? __('modules.settings.cannotCloseRestaurantTitle')
                : ($isRestaurantOpen
                    ? __('modules.settings.manualToggleConfirmCloseTitle')
                    : __('modules.settings.manualToggleConfirmOpenTitle'));
            $confirmDetail = $closeBlocked
                ? __('modules.settings.cannotCloseRestaurantDetail')
                : ($isRestaurantOpen
                    ? __('modules.settings.manualToggleConfirmCloseDetail')
                    : __('modules.settings.manualToggleConfirmOpenDetail'));
            $actionLabel = $isRestaurantOpen
                ? __('modules.settings.manualToggleActionClose')
                : __('modules.settings.manualToggleActionOpen');
        @endphp

        <button
            type="button"
            wire:click="openConfirmModal"
            data-tooltip-target="{{ $tooltipId }}"
            data-tooltip-placement="bottom"
            class="inline-flex items-center gap-2 px-2 sm:px-3 py-1 text-xs rounded-lg border {{ $isRestaurantOpen ? 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100 dark:border-green-600 dark:text-green-300 dark:bg-green-900/30 dark:hover:bg-green-900/50' : 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100 dark:border-red-600 dark:text-red-300 dark:bg-red-900/30 dark:hover:bg-red-900/50' }}"
        >
            <span class="h-2 w-2 rounded-full {{ $isRestaurantOpen ? 'bg-green-500' : 'bg-red-500' }}"></span>
            {{ $isRestaurantOpen ? __('app.close') : __('app.open') }}
        </button>

        <div id="{{ $tooltipId }}" role="tooltip"
            class="absolute z-10 invisible inline-block px-3 py-2 text-xs font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip max-w-52">
            {{ $tooltipText }}
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>

        <x-dialog-modal wire:model.live="showConfirmModal">
            <x-slot name="title">
                {{ $confirmHeading }}
            </x-slot>

            <x-slot name="content">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $confirmDetail }}
                </p>

                @if ($closeBlocked)
                    <div class="mt-4 space-y-4">
                        @if (count($closeBlockTables) > 0)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    @lang('modules.settings.cannotCloseOpenTables')
                                </p>
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($closeBlockTables as $table)
                                        <li class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-sm font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                            {{ $table['table_code'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (count($closeBlockOrders) > 0)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    @lang('modules.settings.cannotCloseUnpaidBilledOrders')
                                </p>
                                <ul class="mt-2 space-y-1.5">
                                    @foreach ($closeBlockOrders as $order)
                                        <li class="text-sm text-gray-800 dark:text-gray-200">
                                            {{ $order['show_formatted_order_number'] ?? (__('modules.order.orderNumber') . ' #' . $order['order_number']) }}
                                            @if (!empty($order['table_code']))
                                                <span class="text-gray-500 dark:text-gray-400">({{ $order['table_code'] }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="closeConfirmModal" wire:loading.attr="disabled">
                    @if ($closeBlocked)
                        @lang('app.ok')
                    @else
                        @lang('app.close')
                    @endif
                </x-secondary-button>

                @if (!$closeBlocked)
                    <x-button class="ms-3" wire:click="toggleRestaurantState" wire:loading.attr="disabled">
                        {{ $actionLabel }}
                    </x-button>
                @endif
            </x-slot>
        </x-dialog-modal>
    @endif
</div>
