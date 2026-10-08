<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">
                @lang('superadmin.orderMoneyRecalculation.title')
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @lang('superadmin.orderMoneyRecalculation.description')
            </p>
        </div>
    </div>

    <div class="px-4 pb-6">
        <x-alert type="warning">
            @lang('superadmin.orderMoneyRecalculation.menuPriceWarning')
        </x-alert>

        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <x-label for="restaurantSearch" value="{{ __('modules.restaurant.name') }}" required />
                    <x-input id="restaurantSearch" class="block mt-1 w-full" type="text"
                        wire:model.live.debounce.400ms="restaurantSearch"
                        placeholder="{{ __('app.search') }}" />
                    <x-select id="restaurantId" class="block mt-2 w-full" wire:model.live="restaurantId">
                        <option value="">{{ __('app.select') }}</option>
                        @foreach ($restaurants as $restaurant)
                            <option value="{{ $restaurant->id }}">{{ $restaurant->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="restaurantId" class="mt-2" />
                </div>

                <div>
                    <x-label for="branchId" value="{{ __('modules.settings.branchName') }}" />
                    <x-select id="branchId" class="block mt-1 w-full" wire:model.live="branchId" :disabled="!$restaurantId">
                        <option value="">{{ __('superadmin.orderMoneyRecalculation.allBranches') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-label for="orderNumber" value="{{ __('modules.order.orderNumber') }}" required />
                    <x-input id="orderNumber" class="block mt-1 w-full" type="text" wire:model="orderNumber"
                        wire:keydown.enter.prevent="previewOrder"
                        placeholder="218" />
                    <x-input-error for="orderNumber" class="mt-2" />
                </div>

                <div class="flex items-end">
                    <x-button type="button" target="previewOrder" wire:click="previewOrder" class="w-full sm:w-auto">
                        @lang('modules.order.preview')
                    </x-button>
                </div>
            </div>
        </div>

        @if (count($matches) > 0 && !$preview)
            <div class="mt-4 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 bg-white dark:bg-gray-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @lang('superadmin.orderMoneyRecalculation.multipleMatches')
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">ID</th>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">@lang('modules.settings.branchName')</th>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">@lang('modules.order.date')</th>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">@lang('modules.order.total')</th>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('app.action')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                            @foreach ($matches as $match)
                                <tr wire:key="order-match-{{ $match['id'] }}">
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">{{ $match['id'] }}</td>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">{{ $match['branch_name'] ?? '—' }}</td>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">{{ $match['date_time'] ?? '—' }}</td>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">{{ $match['total'] }}</td>
                                    <td class="py-2.5 px-4 text-sm text-right">
                                        <x-secondary-button type="button" target="selectMatch" wire:click="selectMatch({{ $match['id'] }})">
                                            @lang('superadmin.orderMoneyRecalculation.useThisOrder')
                                        </x-secondary-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($preview)
            @php
                $decimals = (int) ($preview['decimals'] ?? 2);
                $syncPaymentDisabled = !($preview['can_sync_payment'] ?? false) || $applied;
                $applyDisabled = $applied || !($preview['has_changes'] ?? false);
            @endphp

            <div class="mt-4 p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-1 mb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                            @lang('modules.order.orderNumber') #{{ $preview['order_number'] }}
                            <span class="font-normal text-gray-500 dark:text-gray-400">(ID {{ $preview['order_id'] }})</span>
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $preview['restaurant_name'] }}
                            @if (!empty($preview['branch_name']))
                                · {{ $preview['branch_name'] }}
                            @endif
                            · {{ $decimals }} @lang('superadmin.orderMoneyRecalculation.decimalPlaces')
                        </p>
                    </div>
                    @if ($applied)
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">
                            @lang('superadmin.orderMoneyRecalculation.applied')
                        </span>
                    @elseif (!($preview['has_changes'] ?? false))
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            @lang('superadmin.orderMoneyRecalculation.noChanges')
                        </span>
                    @endif
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">@lang('modules.menu.item')</th>
                                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.oldPrice')</th>
                                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.newPrice')</th>
                                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.oldAmount')</th>
                                    <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.newAmount')</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                                @foreach ($preview['lines'] as $line)
                                    <tr @class(['bg-amber-50 dark:bg-amber-900/20' => $line['changed'] ?? false])>
                                        <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">{{ $line['name'] }}</td>
                                        <td class="py-2.5 px-4 text-sm text-right tabular-nums text-gray-900 dark:text-white">{{ number_format($line['before_price'], $decimals) }}</td>
                                        <td class="py-2.5 px-4 text-sm text-right tabular-nums text-gray-900 dark:text-white">{{ number_format($line['new_price'], $decimals) }}</td>
                                        <td class="py-2.5 px-4 text-sm text-right tabular-nums text-gray-900 dark:text-white">{{ number_format($line['before_amount'], $decimals) }}</td>
                                        <td class="py-2.5 px-4 text-sm text-right tabular-nums text-gray-900 dark:text-white">{{ number_format($line['new_amount'], $decimals) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 mb-4 max-w-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.field')</th>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.oldValue')</th>
                                <th class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">@lang('superadmin.orderMoneyRecalculation.newValue')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                            @foreach (['sub_total', 'total_tax_amount', 'total', 'amount_paid'] as $field)
                                @php $row = $preview['totals'][$field] ?? ['old' => 0, 'new' => 0, 'changed' => false]; @endphp
                                <tr @class(['bg-amber-50 dark:bg-amber-900/20' => $row['changed'] ?? false])>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">{{ $field }}</td>
                                    <td class="py-2.5 px-4 text-sm text-right tabular-nums text-gray-900 dark:text-white">{{ number_format($row['old'], $decimals) }}</td>
                                    <td class="py-2.5 px-4 text-sm text-right tabular-nums text-gray-900 dark:text-white">
                                        @if ($field === 'amount_paid' && $syncPayment && ($preview['can_sync_payment'] ?? false) && !$applied)
                                            {{ number_format($preview['totals']['total']['new'], $decimals) }}
                                        @else
                                            {{ number_format($row['new'], $decimals) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <label class="inline-flex items-start gap-2 {{ ($preview['can_sync_payment'] ?? false) ? 'cursor-pointer' : 'opacity-60' }}">
                            <x-checkbox id="syncPayment" wire:model.live="syncPayment"
                                :disabled="$syncPaymentDisabled" />
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('superadmin.orderMoneyRecalculation.syncPayment')
                                </span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    @if ($preview['can_sync_payment'] ?? false)
                                        @lang('superadmin.orderMoneyRecalculation.syncPaymentHelp')
                                    @else
                                        @lang('superadmin.orderMoneyRecalculation.syncPaymentDisabled')
                                    @endif
                                </span>
                            </span>
                        </label>
                    </div>

                    <x-button type="button" target="confirmApply" wire:click="confirmApply"
                        :disabled="$applyDisabled">
                        @lang('superadmin.orderMoneyRecalculation.apply')
                    </x-button>
                </div>
            </div>
        @endif
    </div>
</div>
