@if (function_exists('isHotelModuleEnabled') && isHotelModuleEnabled() && ($hotelRoomNumber = $order->context_room_number))
    @php
        $hotelStay = $order->hotelStay;
        $hotelStayNumber = $order->context_stay_number ?? $hotelStay?->stay_number;
        $hotelGuestName = $hotelStay?->stayGuests?->first()?->guest?->full_name;
    @endphp
    <div class="flex flex-wrap gap-2 mb-2 sm:mb-3 p-2 sm:p-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800 mt-2 sm:mt-3">
        <div class="flex items-center gap-1.5 text-xs">
            <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-gray-500 dark:text-gray-400">@lang('hotel::modules.folio.room'):</span>
            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $hotelRoomNumber }}</span>
        </div>
        @if ($hotelStayNumber)
            <span class="text-gray-300 dark:text-gray-600 text-xs self-center">|</span>
            <div class="flex items-center gap-1.5 text-xs">
                <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-gray-500 dark:text-gray-400">@lang('hotel::modules.folio.stay'):</span>
                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $hotelStayNumber }}</span>
            </div>
        @endif
        @if ($hotelGuestName)
            <span class="text-gray-300 dark:text-gray-600 text-xs self-center">|</span>
            <div class="flex items-center gap-1.5 text-xs">
                <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-gray-500 dark:text-gray-400">@lang('hotel::modules.guest.guest'):</span>
                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $hotelGuestName }}</span>
            </div>
        @endif
        @if ($order->bill_to)
            <span class="text-gray-300 dark:text-gray-600 text-xs self-center">|</span>
            <div class="flex items-center gap-1.5 text-xs">
                <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="text-gray-500 dark:text-gray-400">@lang('hotel::modules.roomService.billTo'):</span>
                <span @class([
                    'font-semibold',
                    'text-orange-600 dark:text-orange-400' => $order->bill_to === 'POST_TO_ROOM',
                    'text-green-600 dark:text-green-400' => $order->bill_to !== 'POST_TO_ROOM',
                ])>
                    {{ $order->bill_to === 'POST_TO_ROOM' ? __('hotel::modules.roomService.postToRoom') : __('hotel::modules.roomService.payNow') }}
                </span>
            </div>
        @endif
    </div>
@endif
