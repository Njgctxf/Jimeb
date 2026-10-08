@php
    $receiptLanguages = array_values($receiptLanguages ?? ['en']);
    $appLocale = $receiptLanguages[0] ?? app()->getLocale();
    app()->setLocale($appLocale);
    // Mobile Print button is ONLY for browser popup print (user must tap Print).
    // Direct print captures this HTML to PNG/PDF — never include the button.
    $isMobilePrint = empty($generateImage)
        && ($printingChoice ?? 'browserPopupPrint') === 'browserPopupPrint'
        && (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', request()->userAgent() ?? '');
@endphp
<!DOCTYPE html>
<html lang="{{ $appLocale }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ restaurant()->name }} - {{ $order->show_formatted_order_number ?? "" }}</title>
    <script>
        window.__isMobilePrint = {{ $isMobilePrint ? 'true' : 'false' }};
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
        }

        [dir="rtl"] {
            text-align: right;
        }

        [dir="ltr"] {
            text-align: left;
        }

        .receipt {
            width: {{ $width - 10 }}mm;
            @if ($isMobilePrint)
            max-width: 100%;
            margin: 0 auto;
            @endif
            padding: {{ $thermal ? '1mm' : '6.35mm' }};
            page-break-after: {{ !empty($generateImage) ? 'avoid' : 'always' }};
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .restaurant-logo {
            width: 80px;
            height: 80px;
            margin-top: 3px;
            object-fit: contain;
        }

        .restaurant-name {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        .restaurant-name img {
            display: block;
            margin: 0 auto 2mm;
        }

        .qr-code-img {
            width: 100px;
            height: 100px;
        }

        .restaurant-info {
            font-size: 9pt;
            margin-bottom: 1mm;
        }

        .order-info {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2mm 0;
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .order-number{
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .items-table th {
            padding: 1mm;
            border-bottom: 1px solid #000;
        }

        [dir="rtl"] .items-table th {
            text-align: right;
        }

        [dir="ltr"] .items-table th {
            text-align: left;
        }

        .items-table td {
            padding: 1mm 0.5mm;
            vertical-align: top;
        }

        .qty {
            width: 10%;
            text-align: center;
        }

        .description {
            width: 52%;
        }

        .payment-method {
            width: 28%;
        }

        [dir="rtl"] .price,
        [dir="rtl"] .amount {
            text-align: left;
        }

        [dir="ltr"] .price,
        [dir="ltr"] .amount {
            text-align: right;
        }

        .price {
            width: 18%;
            padding-right: 1mm;
        }

        .amount {
            width: 16%;
            padding-left: 1mm;
        }

        .summary {
            font-size: 9pt;
            margin-top: 2mm;
        }

        .summary-row {
            width: 100%;
            margin-bottom: 1mm;
        }
        .summary-row table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-row td {
            padding: 0;
        }
        .summary-row td:first-child {
            text-align: left;
        }
        .summary-row td:last-child {
            text-align: right;
        }
        .summary-row.secondary {
            font-size: 8pt;
            color: #555;
            margin-bottom: 0.5mm;
        }

        .summary-grid {
            width: 100%;
            margin-bottom: 1mm;
        }
        .summary-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            width: 50%;
            padding: 2px 5px;
            vertical-align: top;
        }

        .total {
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #000;
            padding-top: 1mm;
            margin-top: 1mm;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 9pt;
            padding-top: 2mm;
            border-top: 1px dashed #000;
            padding-bottom: 5mm;
        }
        .img-qr-code {
            width: 100px;
            height: 100px;
        }

        .qr_code {
            margin-top: 5mm;
            margin-bottom: 3mm;
        }

        .modifiers {
            font-size: 8pt;
            color: #555;
        }

        .back-button {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .back-button:hover {
            background-color: #2563eb;
        }

        @media print {
            html, body {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                margin: 0;
                padding: 0;
                background: #fff !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                margin: 0;
                size: {{ $width }}mm auto;
            }

            .receipt {
                width: 100% !important;
                max-width: {{ $width - 10 }}mm;
                margin: 0 auto;
                page-break-after: avoid !important;
                page-break-inside: avoid;
            }

            .back-button,
            .mobile-print-bar {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            body {
                padding-bottom: 0 !important;
            }
        }

        @if ($isMobilePrint)
        html, body {
            height: auto !important;
            min-height: 100%;
            overflow-x: hidden;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        body {
            padding-bottom: 80px;
        }
        @endif

        .mobile-print-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 12px 16px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
        }

        .mobile-print-bar button {
            width: 100%;
            padding: 14px 20px;
            background-color: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <!-- Back button for PWA mode -->
    {{-- <button class="back-button" onclick="goBack()" id="backButton" style="display: none;">
        ← @lang('app.back')
    </button> --}}
    <div class="receipt">
        <div class="header">
            <div class="restaurant-name">
                @if ($receiptSettings->show_restaurant_logo)
                    @php
                        $logoUrl = restaurant()->logo_url;
                        $logoBase64 = null;
                        $logoAbsoluteUrl = $logoUrl;
                        if ($logoUrl && !preg_match('/^https?:\/\//', $logoUrl)) {
                            $logoAbsoluteUrl = url($logoUrl);
                        }
                        if ($logoUrl) {
                            try {
                                $fetchLogoUrl = $logoAbsoluteUrl;
                                $logoImageContents = @file_get_contents($fetchLogoUrl);
                                if ($logoImageContents !== false) {
                                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoImageContents);
                                }
                            } catch (\Exception $e) {
                                $logoBase64 = null;
                            }
                        }
                    @endphp
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="{{ $orderBranch->name ?? restaurant()->name }}" class="restaurant-logo" loading="eager">
                    @elseif ($logoAbsoluteUrl)
                        <img src="{{ $logoAbsoluteUrl }}" alt="{{ $orderBranch->name ?? restaurant()->name }}" class="restaurant-logo" loading="eager">
                    @endif
                @endif
                @if ($receiptSettings->show_restaurant_name)
                    <div>{{ restaurant()->name }}</div>
                @endif
            </div>
            @if (!$receiptSettings->show_restaurant_name && $receiptSettings->show_branch_name)
                <div class="restaurant-name" style="margin-top:0;margin-bottom:1mm;">
                    {{ $orderBranch->name ?? restaurant()->name }}
                </div>
            @elseif ($receiptSettings->show_restaurant_name && $receiptSettings->show_branch_name)
                <div class="restaurant-info">{{ $orderBranch->name ?? restaurant()->name }}</div>
            @endif

            @if ($receiptSettings->show_branch_address)
                <div class="restaurant-info">{!! nl2br($orderBranch->address ?? restaurant()->address ?? '') !!}</div>
            @endif
            <div class="restaurant-info">
                {{ flipText(__('modules.customer.phone'), $appLocale, $printingChoice) }}
                @if (count($receiptLanguages) > 1)
                <br>{{ flipText(__('modules.customer.phone', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                @endif:
                <span dir="ltr" style="unicode-bidi: embed;">{{ $orderBranch->phone ?: restaurant()->phone_number }}</span></div>
            @if ($receiptSettings->show_tax)
                @if (empty($orderBranch->cr_number) && empty($orderBranch->vat_number))
                    @foreach ($taxDetails as $taxDetail)
                        <div class="restaurant-info">{{ $taxDetail->tax_name }}: {{ $taxDetail->tax_id }}</div>
                    @endforeach
                @endif
            @endif
            @if ($receiptSettings->show_cr_number && !empty($orderBranch->cr_number))
                <div class="restaurant-info">
                    {{ flipText(__('modules.settings.branchCrNumber'), $appLocale, $printingChoice) }}
                    @if (count($receiptLanguages) > 1)
                    <br>{{ flipText(__('modules.settings.branchCrNumber', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                    @endif: <span dir="ltr" style="unicode-bidi: embed;">{{ $orderBranch->cr_number }}</span></div>
            @endif
            @if ($receiptSettings->show_vat_number && !empty($orderBranch->vat_number))
                <div class="restaurant-info">
                    {{ flipText(__('modules.settings.branchVatNumber'), $appLocale, $printingChoice) }}
                    @if (count($receiptLanguages) > 1)
                    <br>{{ flipText(__('modules.settings.branchVatNumber', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                    @endif: <span dir="ltr" style="unicode-bidi: embed;">{{ $orderBranch->vat_number }}</span></div>
            @endif

        </div>

        <div class="order-info">

            <div class="">
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>
                                <span class="order-number">{{ $order->show_formatted_order_number }}</span>
                            </td>
                            <td class="space_left">{{ $order->date_time->timezone(timezone())->format(dateFormat() . ' ' . timeFormat()) }}</td>
                        </tr>
                    </table>
                </div>
                @php
                    $tokenNumber = $order->kot->whereNotNull('token_number')->first()?->token_number;
                @endphp
                @if ($tokenNumber)
                    <div class="summary-row">
                        <span>
                            {{ flipText(__('modules.order.tokenNumber'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            <br>{{ flipText(__('modules.order.tokenNumber', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif
                        </span>: {{ $tokenNumber }}</span>
                    </div>
                @endif
                @if(
                    ($receiptSettings->show_table_number && $order->isDineInOrder() && $order->table && $order->table->table_code)
                    || ($receiptSettings->show_total_guest && $order->number_of_pax)
                )
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>
                                @if ($receiptSettings->show_table_number && $order->isDineInOrder() && $order->table && $order->table->table_code)
                                    {{ flipText(__('modules.settings.tableNumber'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('modules.settings.tableNumber', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif: {{ $order->table->table_code }}
                                @endif
                            </td>
                            <td>
                                @if ($receiptSettings->show_total_guest && $order->number_of_pax)
                                    {{ flipText(__('modules.order.noOfPax'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('modules.order.noOfPax', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif: {{ $order->number_of_pax }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                @endif
                @if ($receiptSettings->show_waiter && $order->waiter && $order->waiter->name)
                    <div class="summary-row">
                                <span>{{ flipText(__('modules.order.waiter'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    {{ flipText(__('modules.order.waiter', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif: <span class="">{{ $order->waiter->name }}</span></span>
                    </div>
                @endif
                @if ($receiptSettings->show_order_type )
                    <div class="summary-row">
                            @php
                                $billPrintOrderType = (string) $order->order_type;
                                $billPrintOrderTypeKey = 'modules.order.' . $billPrintOrderType;
                                $billPrintOrderTypeLabel = __($billPrintOrderTypeKey);
                                if ($billPrintOrderTypeLabel === $billPrintOrderTypeKey) {
                                    $billPrintOrderTypeLabel = Str::title(str_replace('_', ' ', $billPrintOrderType));
                                }
                            @endphp
                            <span> {{ flipText($billPrintOrderTypeLabel, $appLocale, $printingChoice) }}
                                @if ($order->order_type === 'pickup')
                                    @if ($order->pickup_date)
                                        <span class="">
                                            : {{ \Carbon\Carbon::parse($order->pickup_date)->format(dateFormat() . ' ' . timeFormat()) }}
                                        </span>
                                    @endif
                                @endif
                            </span>

                    </div>
                @endif
                @if ($receiptSettings->show_customer_name && $order->customer && $order->customer->name)
                    <div class="summary-row">
                        <span class="showData">{{ flipText(__('modules.customer.customer'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            {{ flipText(__('modules.customer.customer', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif: <span class="">{{ $order->customer->name }}</span></span>
                    </div>
                @endif


                @if ($receiptSettings->show_customer_address && $order->customer && $order->customer->delivery_address)
                    <div class="summary-row">
                        <span>{{ flipText(__('modules.customer.customerAddress'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            {{ flipText(__('modules.customer.customerAddress', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif: <span class="">{{ $order->customer->delivery_address }}</span></span>
                    </div>
                @endif

                @if ($receiptSettings->show_customer_phone && $order->customer && $order->customer->phone)
                    <div class="summary-row">
                        <span>{{ flipText(__('modules.customer.phone'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            {{ flipText(__('modules.customer.phone', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif: <span dir="ltr" style="unicode-bidi: embed;">{{ $order->customer->phone }}</span></span>
                    </div>
                @endif

                @if (function_exists('isHotelModuleEnabled') && isHotelModuleEnabled() && ($hotelRoomNumber = $order->context_room_number))
                    @php
                        $hotelStay = $order->hotelStay;
                        $hotelStayNumber = $order->context_stay_number ?? $hotelStay?->stay_number;
                    @endphp
                    <div class="summary-row">
                        <span>{{ flipText(__('hotel::modules.folio.room'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            {{ flipText(__('hotel::modules.folio.room', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif: <span class="">{{ $hotelRoomNumber }}</span></span>
                    </div>
                    @if ($hotelStayNumber)
                        <div class="summary-row">
                            <span>{{ flipText(__('hotel::modules.folio.stay'), $appLocale, $printingChoice) }}
                                @if (count($receiptLanguages) > 1)
                                {{ flipText(__('hotel::modules.folio.stay', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif: <span class="">{{ $hotelStayNumber }}</span></span>
                        </div>
                    @endif
                    @if ($hotelStay?->stayGuests && $hotelStay->stayGuests->isNotEmpty() && $hotelStay->stayGuests->first()?->guest)
                        <div class="summary-row">
                            <span>{{ flipText(__('hotel::modules.guest.guest'), $appLocale, $printingChoice) }}
                                @if (count($receiptLanguages) > 1)
                                {{ flipText(__('hotel::modules.guest.guest', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif: <span class="">{{ $hotelStay->stayGuests->first()->guest->full_name }}</span></span>
                        </div>
                    @endif
                    @if ($order->bill_to)
                        <div class="summary-row">
                            <span>{{ flipText(__('hotel::modules.roomService.billTo'), $appLocale, $printingChoice) }}: <span class="">{{ $order->bill_to === 'POST_TO_ROOM' ? __('hotel::modules.roomService.postToRoom') : __('hotel::modules.roomService.payNow') }}</span> </span>
                        </div>
                    @endif
                @endif
            </div>

        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="qty">{{ flipText(__('modules.order.qty'), $appLocale, $printingChoice) }}
                        @if (count($receiptLanguages) > 1)
                        <br>{{ flipText(__('modules.order.qty', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                        @endif
                    </th>
                    <th class="description">{{ flipText(__('modules.menu.itemName'), $appLocale, $printingChoice) }}
                        @if (count($receiptLanguages) > 1)
                        <br>{{ flipText(__('modules.menu.itemName', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}    
                        @endif
                    </th>
                    <th class="price">{{ flipText(__('modules.order.price'), $appLocale, $printingChoice) }}
                        @if (count($receiptLanguages) > 1)
                        <br>{{ flipText(__('modules.order.price', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                        @endif
                    </th>
                    <th class="amount">{{ flipText(__('modules.order.amount'), $appLocale, $printingChoice) }}
                        @if (count($receiptLanguages) > 1)
                        <br>{{ flipText(__('modules.order.amount', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td class="qty">{{ \App\Support\PosQuantity::formatDisplay($item->quantity, (bool) ($item->menuItem->sell_by_weight ?? false)) }}</td>
                        <td class="description">
                            @php
                                $primaryItemName = $item->menuItem?->getTranslatedValue('item_name', $appLocale) ?? '--';
                                $secondaryItemName = count($receiptLanguages) > 1
                                    ? $item->menuItem?->getTranslatedValue('item_name', $receiptLanguages[1])
                                    : null;
                            @endphp
                            {{ flipText($primaryItemName, $appLocale, $printingChoice) }}
                            @if ($secondaryItemName && $secondaryItemName !== $primaryItemName)
                            <br>{{ flipText($secondaryItemName, $receiptLanguages[1], $printingChoice) }}
                            @endif

                            @if (isset($item->menuItemVariation))
                                <br><small>({{ $item->menuItemVariation->variation }})</small>
                            @endif
                            @foreach ($item->modifierOptions as $modifier)
                                @php
                                    if ($order->order_type_id) {
                                        $modifier->setPriceContext($order->order_type_id, $order?->delivery_app_id);
                                    }
                                @endphp
                                <div class="modifiers">• {{ $modifier->name ?? $modifier->pivot->modifier_option_name }}
                                    (+{{ currency_format($modifier->pivot->modifier_option_price ?? $modifier->price, restaurant()->currency_id) }})
                                </div>
                            @endforeach
                        </td>
                        <td class="price">{{ currency_format($item->price, restaurant()->currency_id) }}</td>
                        <td class="amount">
                            {{ currency_format($item->amount, restaurant()->currency_id) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <table>
                    <tr>
                        <td>{{ flipText(__('modules.order.subTotal'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            <br>{{ flipText(__('modules.order.subTotal', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif
                        </td>
                        <td>{{ currency_format($order->sub_total, restaurant()->currency_id) }}</td>
                    </tr>
                </table>
            </div>

            @if (!is_null($order->discount_amount))
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>{{ flipText(__('modules.order.discount'), $appLocale, $printingChoice) }} @if ($order->discount_type == 'percent')
                                    ({{ rtrim(rtrim($order->discount_value, '0'), '.') }}%)
                                @endif
                                @if (count($receiptLanguages) > 1)
                                <br>{{ flipText(__('modules.order.discount', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif
                            </td>
                            <td>-{{ currency_format($order->discount_amount, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            @if(function_exists('module_enabled') && module_enabled('Loyalty') && $order->loyalty_points_redeemed > 0 && $order->loyalty_discount_amount > 0)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>{{ flipText(__('loyalty::app.loyaltyDiscount'), $appLocale, $printingChoice) }} ({{ number_format($order->loyalty_points_redeemed) }} @lang('loyalty::app.points'))
                                @if (count($receiptLanguages) > 1)
                                <br>{{ flipText(__('loyalty::app.loyaltyDiscount', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }} ({{ number_format($order->loyalty_points_redeemed) }} @lang('loyalty::app.points', [], $receiptLanguages[1]))
                                @endif
                            </td>
                            <td>-{{ currency_format($order->loyalty_discount_amount, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            @if(function_exists('module_enabled') && module_enabled('Loyalty') && ($order->stamp_discount_amount > 0 || $order->items()->where('is_free_item_from_stamp', true)->exists()))
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>{{ flipText(__('app.stampDiscount'), $appLocale, $printingChoice) }}
                                @if($order->items()->where('is_free_item_from_stamp', true)->exists())
                                    (@lang('app.freeItem'))
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('app.freeItem', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($order->stamp_discount_amount > 0)
                                    -{{ currency_format($order->stamp_discount_amount, restaurant()->currency_id) }}
                                @else
                                    {{ flipText(__('app.free'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('app.free', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endif
            @php
                // Calculate discounted subtotal (after both regular and loyalty discounts)
                // Loyalty points are always removed from subtotal before calculating tax
                $discountedSubtotal = $order->sub_total
                    - ($order->discount_amount ?? 0)
                    - ($order->loyalty_discount_amount ?? 0);

                // Calculate service charges on discounted subtotal
                $serviceTotal = 0;
                if ($order->charges && $order->charges->count() > 0) {
                    foreach ($order->charges as $chargeRelation) {
                        $charge = $chargeRelation->charge;
                        if ($charge) {
                            $chargeAmount = $charge->getAmount((float)$discountedSubtotal);
                            $serviceTotal += (float)$chargeAmount;
                        }
                    }
                }

                // For backward compatibility, keep $net for service charge display
                $net = $order->sub_total - ($order->discount_amount ?? 0);
                $taxBase = $order->tax_base ?? $net;
            @endphp

            @foreach ($order->charges as $item)
            <div class="summary-row">
                <table>
                    <tr>
                        <td>{{ $item->charge->charge_name }}
                            @if ($item->charge->charge_type == 'percent')
                            ({{ $item->charge->charge_value }}%)
                            @endif:
                        </td>
                        <td>
                            {{ currency_format($item->charge->getAmount($discountedSubtotal), restaurant()->currency_id) }}
                        </td>
                    </tr>
                </table>
            </div>
            @endforeach

            @if ($order->tip_amount > 0)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>{{ flipText(__('modules.order.tip'), $appLocale, $printingChoice) }}
                                @if (count($receiptLanguages) > 1)
                                <br>{{ flipText(__('modules.order.tip', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif
                            </td>
                            <td>{{ currency_format($order->tip_amount, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            @if ($order->order_type === 'delivery' && !is_null($order->delivery_fee))
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>{{ flipText(__('modules.delivery.deliveryFee'), $appLocale, $printingChoice) }}
                                @if (count($receiptLanguages) > 1)
                                <br>{{ flipText(__('modules.delivery.deliveryFee', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif
                            </td>
                            <td>
                                @if($order->delivery_fee > 0)
                                    {{ currency_format($order->delivery_fee, restaurant()->currency_id) }}
                                @else
                                    {{ flipText(__('modules.delivery.freeDelivery'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('modules.delivery.freeDelivery', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

            @if ($taxMode == 'order')
                @foreach ($order->taxes as $item)
                    @if($item->tax)
                        <div class="summary-row">
                            <table>
                                <tr>
                                    <td>{{ $item->tax->tax_name }} ({{ $item->tax->tax_percent }}%):</td>
                                    <td>
                                        @php
                                            // Calculate tax_base based on Tax Calculation Base setting
                                            // Check if service charges should be included in tax base
                                            $restaurant = restaurant();
                                            $includeChargesInTaxBase = false;
                                            if ($restaurant && isset($restaurant->include_charges_in_tax_base)) {
                                                $includeChargesInTaxBase = (bool)$restaurant->include_charges_in_tax_base;
                                            }

                                            // Tax base = (subtotal - discounts) + service charges (if enabled)
                                            $taxBase = $includeChargesInTaxBase
                                                ? ($discountedSubtotal + $serviceTotal)
                                                : $discountedSubtotal;
                                            $taxBase = max(0, (float)$taxBase);

                                            // Calculate tax on tax_base
                                            $taxAmount = ($item->tax->tax_percent / 100) * $taxBase;
                                        @endphp
                                        {{ currency_format($taxAmount, restaurant()->currency_id) }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif
                @endforeach
            @else
                @if($order->total_tax_amount > 0)
                    @php
                        $taxTotals = [];
                        $totalTax = 0;
                        foreach ($order->items as $item) {
                            $qty = $item->quantity ?? 1;
                            $taxBreakdown = is_array($item->tax_breakup) ? $item->tax_breakup : (json_decode($item->tax_breakup, true) ?? []);
                            foreach ($taxBreakdown as $taxName => $taxInfo) {
                                if (!isset($taxTotals[$taxName])) {
                                    $taxTotals[$taxName] = [
                                        'percent' => $taxInfo['percent'] ?? 0,
                                        'amount' => ($taxInfo['amount'] ?? 0) * $qty
                                    ];
                                } else {
                                    $taxTotals[$taxName]['amount'] += ($taxInfo['amount'] ?? 0) * $qty;
                                }
                            }
                            $totalTax += $item->tax_amount ?? 0;
                        }
                    @endphp
                    <div>
                        @foreach ($taxTotals as $taxName => $taxInfo)
                        <div class="summary-row secondary">
                            <table>
                                <tr>
                                    <td>{{ $taxName }} ({{ $taxInfo['percent'] }}%)</td>
                                    <td>{{ currency_format($taxInfo['amount'], restaurant()->currency_id) }}</td>
                                </tr>
                            </table>
                        </div>
                        @endforeach
                    </div>
                    <div class="summary-row">
                        <table>
                            <tr>
                                <td>{{ flipText(__('modules.order.totalTax'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('modules.order.totalTax', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                </td>
                                <td>{{ currency_format($totalTax, restaurant()->currency_id) }}</td>
                            </tr>
                        </table>
                    </div>
                @endif
            @endif

            @if ($payment)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>{{ flipText(__('modules.order.balanceReturn'), $appLocale, $printingChoice) }}
                                @if (count($receiptLanguages) > 1)
                                <br>{{ flipText(__('modules.order.balanceReturn', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif
                            </td>
                            <td>{{ currency_format($payment->balance, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            <div class="summary-row total">
                <table>
                    <tr>
                        <td>{{ flipText(__('modules.order.total'), $appLocale, $printingChoice) }}
                            @if (count($receiptLanguages) > 1)
                            <br>{{ flipText(__('modules.order.total', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                            @endif
                        </td>
                        <td>{{ currency_format($order->total, restaurant()->currency_id) }}</td>
                    </tr>
                </table>
            </div>

            @if ($receiptSettings->show_payment_status)
                <div class="summary-row" style="margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #000;">
                    <table>
                        <tr>
                            <td style="font-weight: bold;">{{ flipText(__('modules.order.paymentStatus'), $appLocale, $printingChoice) }}
                                @if (count($receiptLanguages) > 1)
                                <br>{{ flipText(__('modules.order.paymentStatus', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                @endif
                            </td>
                            <td style="font-weight: bold;">
                                @if($order->status === 'paid')
                                    <span style="color: #10b981;">@lang('modules.order.paid')</span>
                                @else
                                    <span style="color: #ef4444;">@lang('modules.order.unpaid')</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

        </div>

        <div class="footer">
            <p>{{ flipText($receiptSettings->displayFooterText(), $appLocale, $printingChoice) }}
                @if (\App\Models\ReceiptSetting::footerTextToPlain($receiptSettings->footer_text) === '' && count($receiptLanguages) > 1)
                <br>{{ flipText(__('messages.thankYouVisit', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                @endif
            </p>

            @if ($order->status != 'paid')
            <div>
                @if ($receiptSettings->show_payment_qr_code)
                    <p class="qr_code">{{ flipText(__('modules.settings.payFromYourPhone'), $appLocale, $printingChoice) }}
                        @if (count($receiptLanguages) > 1)
                        <br>{{ flipText(__('modules.settings.payFromYourPhone', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                        @endif
                    </p>
                    @php
                        $qrCodeUrl = $receiptSettings->payment_qr_code_url;
                        $qrCodeBase64 = null;
                        $qrCodeAbsoluteUrl = $qrCodeUrl;
                        if ($qrCodeUrl && !preg_match('/^https?:\/\//', $qrCodeUrl)) {
                            $qrCodeAbsoluteUrl = url($qrCodeUrl);
                        }
                        if ($qrCodeUrl) {
                            try {
                                $qrImageContents = @file_get_contents($qrCodeAbsoluteUrl);
                                if ($qrImageContents !== false) {
                                    $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrImageContents);
                                }
                            } catch (\Exception $e) {
                                $qrCodeBase64 = null;
                            }
                        }
                    @endphp
                    @if ($qrCodeBase64)
                        <img class="qr-code-img" src="{{ $qrCodeBase64 }}" alt="QR Code" loading="eager">
                    @elseif ($qrCodeAbsoluteUrl)
                        <img class="qr-code-img" src="{{ $qrCodeAbsoluteUrl }}" alt="QR Code" loading="eager">
                    @endif
                    <p class="">@lang('modules.settings.scanQrCode')
                        @if (count($receiptLanguages) > 1)
                        <br>{{ flipText(__('modules.settings.scanQrCode', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                        @endif
                    </p>
                @endif
            </div>
            @endif

            @if ($receiptSettings->show_payment_details && $order->payments->count())
                <div class="summary">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="qty" style="text-align: center">
                                    {{ flipText(__('modules.order.amount'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('modules.order.amount', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                </th>
                                <th class="payment-method" style="text-align: center">
                                    {{ flipText(__('modules.order.paymentMethod'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('modules.order.paymentMethod', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                </th>
                                <th class="price" style="text-align: center">
                                    {{ flipText(__('app.dateTime'), $appLocale, $printingChoice) }}
                                    @if (count($receiptLanguages) > 1)
                                    <br>{{ flipText(__('app.dateTime', [], $receiptLanguages[1]), $receiptLanguages[1], $printingChoice) }}
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->payments as $payment)
                                <tr>
                                    <td class="qty" style="text-align: center">{{ currency_format($payment->amount, restaurant()->currency_id) }}</td>
                                    <td class="payment-method" style="text-align: center">{{ $payment->payment_method }}</td>
                                    <td class="price" style="text-align: center">
                                        @if($payment->payment_method != 'due')
                                            {{ $payment->created_at->timezone(timezone())->format(dateFormat() . ' ' . timeFormat()) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>


    </div>

    @if ($isMobilePrint)
    <div class="mobile-print-bar">
        <button type="button" id="mobilePrintBtn">@lang('app.print')</button>
    </div>
    @endif

    <script>
        function isPWA() {
            return (window.matchMedia('(display-mode: standalone)').matches) ||
                   (window.navigator.standalone === true) ||
                   (document.referrer.includes('android-app://'));
        }

        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '{{ route("orders.index") }}';
            }
        }

        function finishAfterPrint() {
            if (isPWA()) {
                goBack();
            } else {
                window.close();
                setTimeout(function() {
                    try {
                        if (!window.closed) {
                            window.location.href = '{{ route("orders.index") }}';
                        }
                    } catch (e) {}
                }, 500);
            }
        }

        function stripMobilePrintChrome() {
            // Remove from DOM (not just hide). Android often fires afterprint while the
            // print dialog is still open — restoring/showing the button then puts it
            // back into the print snapshot. Do not re-insert before print finishes.
            var bar = document.querySelector('.mobile-print-bar');
            if (bar) {
                bar.remove();
            }
            document.body.style.paddingBottom = '0';
            void document.body.offsetHeight;
        }

        function whenReadyToPrint(callback) {
            var images = Array.prototype.slice.call(document.querySelectorAll('img'));
            var waitForPaint = function() {
                requestAnimationFrame(function() {
                    requestAnimationFrame(callback);
                });
            };

            if (images.length === 0) {
                waitForPaint();
                return;
            }

            var pending = images.filter(function(img) {
                return !img.complete;
            }).length;

            if (pending === 0) {
                waitForPaint();
                return;
            }

            images.forEach(function(img) {
                if (img.complete) {
                    return;
                }

                var done = function() {
                    pending--;
                    if (pending <= 0) {
                        waitForPaint();
                    }
                };

                img.addEventListener('load', done, { once: true });
                img.addEventListener('error', done, { once: true });
            });
        }

        function triggerPrint(autoClose) {
            if (window.__printTriggered) {
                return;
            }
            window.__printTriggered = true;

            stripMobilePrintChrome();

            if (autoClose) {
                if ('onafterprint' in window) {
                    window.onafterprint = finishAfterPrint;
                } else {
                    window.addEventListener('afterprint', finishAfterPrint, { once: true });
                }
            }

            // Delay so Android PrintService snapshots DOM without the button
            setTimeout(function() {
                window.print();
            }, 150);
        }

        window.onload = function() {
            if (window.self !== window.top) {
                return;
            }

            if (isPWA()) {
                var btn = document.getElementById('backButton');
                if (btn) btn.style.display = 'block';
            }

            var mobileBtn = document.getElementById('mobilePrintBtn');
            if (mobileBtn) {
                mobileBtn.addEventListener('click', function() {
                    window.__printTriggered = false;
                    whenReadyToPrint(function() {
                        triggerPrint(true);
                    });
                });
            }

            whenReadyToPrint(function() {
                if (window.__isMobilePrint) {
                    // Android print preview fails if print is triggered before user interaction
                    return;
                }
                triggerPrint(true);
            });
        };
    </script>
</body>

</html>
