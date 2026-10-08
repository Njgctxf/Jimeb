<div class="deleted-order-report mx-auto px-3 sm:px-4 lg:px-6 pb-6 space-y-3 text-sm">
    <div class="p-3 sm:p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="mb-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white tracking-tight">@lang('menu.deleteOrderReport')</h1>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                @lang('modules.report.deleteOrderReportDescription')
            </p>
        </div>

        <div class="grid grid-cols-1 gap-2 mb-3 sm:grid-cols-2">
            <div class="p-3 bg-red-50 rounded-lg shadow-sm dark:bg-red-900/10 border border-red-100 dark:border-red-800">
                <div class="flex items-center justify-between mb-1.5">
                    <h3 class="text-xs font-medium text-gray-800 dark:text-gray-200">@lang('modules.report.totalDeletedOrders')</h3>
                    <div class="p-1.5 bg-red-100 text-red-600 rounded-md dark:bg-red-900/20 dark:text-red-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                </div>
                <p class="text-lg font-bold tabular-nums text-gray-900 dark:text-white">
                    {{ $totalDeletedOrders }}
                </p>
            </div>

            <div class="p-3 bg-orange-50 rounded-lg shadow-sm dark:bg-orange-900/10 border border-orange-100 dark:border-orange-800">
                <div class="flex items-center justify-between mb-1.5">
                    <h3 class="text-xs font-medium text-gray-800 dark:text-gray-200">@lang('modules.report.totalDeletedAmount')</h3>
                    <div class="p-1.5 bg-orange-100 text-orange-600 rounded-md dark:bg-orange-900/20 dark:text-orange-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-lg font-bold tabular-nums text-gray-900 dark:text-white">
                    {{ currency_format($totalDeletedAmount, $currencyId) }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap justify-between items-start gap-3 p-3 bg-gray-50 rounded-lg dark:bg-gray-700/80 border border-gray-200 dark:border-gray-600">
            <div class="w-full lg:flex-1 min-w-0">
                <form action="#" method="GET" class="w-full">
                    <div class="flex flex-wrap gap-2 items-center">
                        <x-select id="dateRangeType" class="block w-full sm:w-auto min-w-[7.5rem] text-xs" wire:model.live="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="currentWeek">@lang('app.currentWeek')</option>
                            <option value="lastWeek">@lang('app.lastWeek')</option>
                            <option value="last7Days">@lang('app.last7Days')</option>
                            <option value="currentMonth">@lang('app.currentMonth')</option>
                            <option value="lastMonth">@lang('app.lastMonth')</option>
                            <option value="currentYear">@lang('app.currentYear')</option>
                            <option value="lastYear">@lang('app.lastYear')</option>
                        </x-select>

                        <div class="flex flex-wrap items-center gap-2">
                            <div class="w-auto min-w-[6.875rem]">
                                <x-datepicker wire:model.change='startDate' placeholder="@lang('app.selectStartDate')" />
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-300 whitespace-nowrap">@lang('app.to')</span>
                            <div class="w-auto min-w-[6.875rem]">
                                <x-datepicker wire:model.live='endDate' placeholder="@lang('app.selectEndDate')" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <div class="w-auto min-w-[5.625rem]">
                                <label for="start-time" class="sr-only">@lang('modules.reservation.timeStart'):</label>
                                <x-time-picker wire:model.live.debounce.500ms="startTime" value="{{ $startTime }}" />
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-300 whitespace-nowrap">@lang('app.to')</span>
                            <div class="w-auto min-w-[5.625rem]">
                                <label for="end-time" class="sr-only">@lang('modules.reservation.timeEnd'):</label>
                                <x-time-picker wire:model.live.debounce.500ms="endTime" value="{{ $endTime }}" />
                            </div>
                        </div>

                        <select wire:model.live="selectedDeletedBy" class="px-2 py-1.5 text-xs font-medium text-gray-900 bg-white border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 w-full sm:w-auto min-w-[7.5rem]">
                            <option value="">@lang('modules.report.allUsers')</option>
                            @foreach($users ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="flex items-center w-full sm:w-auto shrink-0">
                <a href="javascript:;" wire:click='exportReport'
                    class="inline-flex items-center justify-center w-full sm:w-auto px-2 py-1.5 text-xs font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-md hover:bg-gray-100 focus:ring-2 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5 -ml-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                    @lang('app.export')
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
        <table class="w-full min-w-[48rem] text-xs text-left border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="text-[10px] uppercase tracking-wide text-gray-600 bg-gray-50 dark:bg-gray-700/80 dark:text-gray-300">
                <tr>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.orderNumber')</th>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.orderDate')</th>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.deletedDate')</th>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.customer')</th>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.placedBy')</th>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.orderType')</th>
                    <th scope="col" class="px-3 py-2">@lang('modules.report.deletedBy')</th>
                    <th scope="col" class="px-3 py-2 text-right">@lang('modules.report.orderTotal')</th>
                    <th scope="col" class="px-3 py-2 text-center">@lang('app.actions')</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/80 bg-white dark:bg-gray-800">
                @forelse ($deletedOrders ?? [] as $deletedOrder)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 align-top">
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                            {{ $deletedOrder->show_formatted_order_number }}
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white whitespace-nowrap tabular-nums">
                            @if($deletedOrder->order_date_time)
                                @php
                                    $orderDate = \Carbon\Carbon::parse($deletedOrder->order_date_time)->setTimezone(timezone());
                                @endphp
                                {{ $orderDate->format((restaurant()->date_format ?? 'd-m-Y') . ' ' . (restaurant()->time_format ?? 'h:i A')) }}
                            @else
                                {{ __('modules.report.notAvailable') }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white whitespace-nowrap tabular-nums">
                            @if($deletedOrder->order_deleted_at)
                                @php
                                    $deletedDate = \Carbon\Carbon::parse($deletedOrder->order_deleted_at)->setTimezone(timezone());
                                @endphp
                                {{ $deletedDate->format((restaurant()->date_format ?? 'd-m-Y') . ' ' . (restaurant()->time_format ?? 'h:i A')) }}
                            @else
                                {{ __('modules.report.notAvailable') }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white max-w-[12rem]">
                            <span class="font-medium text-[13px] leading-tight">{{ $deletedOrder->customer_name ?? __('modules.report.walkIn') }}</span>
                            @if($deletedOrder->customer_phone)
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $deletedOrder->customer_phone }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white">
                            {{ $deletedOrder->placed_by_name ?? __('modules.report.notAvailable') }}
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white whitespace-nowrap">
                            @if($deletedOrder->order_type && \Illuminate\Support\Facades\Lang::has('modules.order.' . $deletedOrder->order_type))
                                @lang('modules.order.' . $deletedOrder->order_type)
                            @else
                                {{ $deletedOrder->order_type ? ucwords(str_replace('_', ' ', $deletedOrder->order_type)) : __('modules.report.notAvailable') }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-white">
                            @if($deletedOrder->deleted_by_name || $deletedOrder->deletedBy)
                                <div class="font-medium text-[13px] leading-tight">
                                    {{ $deletedOrder->deleted_by_name ?? $deletedOrder->deletedBy?->name }}
                                </div>
                                @if($deletedOrder->deletedBy?->email)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $deletedOrder->deletedBy->email }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">@lang('modules.report.notAvailable')</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-semibold text-right tabular-nums text-gray-900 dark:text-white whitespace-nowrap">
                            {{ currency_format($deletedOrder->total, $currencyId) }}
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <button
                                type="button"
                                wire:click="openOrderDetailsModal({{ $deletedOrder->id }})"
                                class="inline-flex items-center px-2 py-1 text-[11px] font-medium text-skin-base bg-skin-base/10 border border-skin-base/30 rounded-md hover:bg-skin-base/20 focus:ring-2 focus:ring-skin-base/30 dark:bg-skin-base/20 dark:text-white dark:border-skin-base/40 dark:hover:bg-skin-base/30"
                            >
                                @lang('app.viewMore')
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('modules.report.noDeletedOrdersFound')
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($deletedOrders ?? []) > 0)
                <tfoot class="bg-gray-50 dark:bg-gray-700/60 border-t border-gray-200 dark:border-gray-600">
                    <tr>
                        <td colspan="7" class="px-3 py-2 text-xs font-semibold text-right uppercase tracking-wide text-gray-900 dark:text-white">
                            @lang('modules.report.total'):
                        </td>
                        <td class="px-3 py-2 text-sm font-bold text-right tabular-nums text-gray-900 dark:text-white bg-orange-50/80 dark:bg-orange-900/20 border-l border-orange-200/80 dark:border-orange-800">
                            {{ currency_format($totalDeletedAmount, $currencyId) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <x-dialog-modal wire:model.live="showOrderDetailsModal" maxWidth="4xl" maxHeight="full">
        <x-slot name="title">
            <div class="flex items-center gap-2 text-base font-semibold">
                <svg class="w-4 h-4 shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="leading-tight">
                    @lang('modules.settings.orderDetails')
                    @if($selectedDeletedOrder)
                        — {{ $selectedDeletedOrder->show_formatted_order_number }}
                    @endif
                </span>
            </div>
        </x-slot>

        <x-slot name="content">
            @if($selectedDeletedOrder)
                @php
                    $dateFormat = restaurant()->date_format ?? 'd-m-Y';
                    $timeFormat = restaurant()->time_format ?? 'h:i A';
                    $dateTimeFormat = $dateFormat . ' ' . $timeFormat;
                    $orderDate = $selectedDeletedOrder->order_date_time
                        ? \Carbon\Carbon::parse($selectedDeletedOrder->order_date_time)->setTimezone(timezone())->format($dateTimeFormat)
                        : __('modules.report.notAvailable');
                    $deletedDate = $selectedDeletedOrder->order_deleted_at
                        ? \Carbon\Carbon::parse($selectedDeletedOrder->order_deleted_at)->setTimezone(timezone())->format($dateTimeFormat)
                        : __('modules.report.notAvailable');
                    $orderTypeLabel = $selectedDeletedOrder->order_type && \Illuminate\Support\Facades\Lang::has('modules.order.' . $selectedDeletedOrder->order_type)
                        ? __('modules.order.' . $selectedDeletedOrder->order_type)
                        : ($selectedDeletedOrder->order_type ? ucwords(str_replace('_', ' ', $selectedDeletedOrder->order_type)) : __('modules.report.notAvailable'));
                    $paymentMethods = collect($selectedDeletedOrder->payment_methods ?? [])->filter()->implode(', ');
                @endphp

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.orderDate')</h4>
                            <p class="text-sm tabular-nums text-gray-900 dark:text-white">{{ $orderDate }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.deletedDate')</h4>
                            <p class="text-sm tabular-nums text-gray-900 dark:text-white">{{ $deletedDate }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.orderType')</h4>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $orderTypeLabel }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('app.status')</h4>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $selectedDeletedOrder->status ? ucwords(str_replace('_', ' ', $selectedDeletedOrder->status)) : __('modules.report.notAvailable') }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.order.noOfPax')</h4>
                            <p class="text-sm tabular-nums text-gray-900 dark:text-white">{{ $selectedDeletedOrder->number_of_pax ?? __('modules.report.notAvailable') }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.order.paymentMethod')</h4>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $paymentMethods ?: __('modules.report.notAvailable') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.customer')</h4>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedDeletedOrder->customer_name ?? __('modules.report.walkIn') }}</p>
                            @if($selectedDeletedOrder->customer_phone)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $selectedDeletedOrder->customer_phone }}</p>
                            @endif
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.placedBy')</h4>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $selectedDeletedOrder->placed_by_name ?? __('modules.report.notAvailable') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.tableWaiter')</h4>
                            <div class="space-y-1 text-sm text-gray-900 dark:text-white">
                                @if($selectedDeletedOrder->table_code)
                                    <div>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            @lang('modules.report.table'): {{ $selectedDeletedOrder->table_code }}
                                        </span>
                                    </div>
                                @endif
                                @if($selectedDeletedOrder->waiter_name)
                                    <div>
                                        @lang('modules.report.waiter'): <span class="font-medium">{{ $selectedDeletedOrder->waiter_name }}</span>
                                    </div>
                                @endif
                                @if(!$selectedDeletedOrder->table_code && !$selectedDeletedOrder->waiter_name)
                                    <span class="text-gray-400">@lang('modules.report.notAvailable')</span>
                                @endif
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.deletedBy')</h4>
                            @if($selectedDeletedOrder->deleted_by_name || $selectedDeletedOrder->deletedBy)
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $selectedDeletedOrder->deleted_by_name ?? $selectedDeletedOrder->deletedBy?->name }}
                                </p>
                                @if($selectedDeletedOrder->deletedBy?->email)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $selectedDeletedOrder->deletedBy->email }}</p>
                                @endif
                            @else
                                <p class="text-sm text-gray-400">@lang('modules.report.notAvailable')</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.order.subTotal')</h4>
                            <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ currency_format($selectedDeletedOrder->sub_total ?? 0, $currencyId) }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.order.totalTax')</h4>
                            <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ currency_format($selectedDeletedOrder->total_tax_amount ?? 0, $currencyId) }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.order.amountPaid')</h4>
                            <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ currency_format($selectedDeletedOrder->amount_paid ?? 0, $currencyId) }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                            <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">@lang('modules.report.orderTotal')</h4>
                            <p class="text-sm font-bold tabular-nums text-gray-900 dark:text-white">{{ currency_format($selectedDeletedOrder->total ?? 0, $currencyId) }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-2">@lang('modules.report.orderItems')</h4>
                        @if(!empty($modalOrderItems))
                            <div class="overflow-x-auto max-h-[min(50vh,24rem)] border border-gray-200 dark:border-gray-600 rounded-lg">
                                <table class="w-full min-w-[36rem] text-xs text-left">
                                    <thead class="text-[10px] uppercase tracking-wide text-gray-600 bg-gray-50 dark:bg-gray-700/80 dark:text-gray-300 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-3 py-2">@lang('modules.menu.itemName')</th>
                                            <th scope="col" class="px-3 py-2 text-center">@lang('modules.order.quantity')</th>
                                            <th scope="col" class="px-3 py-2">@lang('modules.menu.variation')</th>
                                            <th scope="col" class="px-3 py-2">@lang('modules.menu.modifiers')</th>
                                            <th scope="col" class="px-3 py-2">@lang('modules.order.note')</th>
                                            <th scope="col" class="px-3 py-2 text-right">@lang('modules.order.price')</th>
                                            <th scope="col" class="px-3 py-2 text-right">@lang('modules.order.amount')</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/80 bg-white dark:bg-gray-800">
                                        @foreach($modalOrderItems as $item)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 align-top">
                                                <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">
                                                    {{ $item['name'] ?: __('modules.report.notAvailable') }}
                                                </td>
                                                <td class="px-3 py-2 text-center tabular-nums text-gray-900 dark:text-white">
                                                    {{ $item['quantity'] }}
                                                </td>
                                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                                    {{ $item['variation'] ?: '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 max-w-[10rem]">
                                                    {{ !empty($item['modifiers']) ? implode(', ', $item['modifiers']) : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 max-w-[10rem]">
                                                    {{ $item['note'] ?: '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right tabular-nums text-gray-900 dark:text-white whitespace-nowrap">
                                                    {{ currency_format($item['price'], $currencyId) }}
                                                </td>
                                                <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                    {{ currency_format($item['amount'], $currencyId) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.report.notAvailable')</p>
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeOrderDetailsModal" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
