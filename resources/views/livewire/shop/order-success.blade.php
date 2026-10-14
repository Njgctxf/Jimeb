{{-- Jimbe : confirmation + suivi de commande --}}
@php
    $jbStatus = $order->order_status->value;
    $jbCancelled = $jbStatus === 'cancelled';
    $jbStep = match (true) {
        in_array($jbStatus, ['served', 'completed', 'delivered'], true) => 4,
        $jbStatus === 'food_ready' => 3,
        $jbStatus === 'preparing' => 2,
        default => 1,
    };
    $jbSteps = [
        1 => ['📩', __('modules.shop.stepReceived')],
        2 => ['👨‍🍳', __('modules.shop.stepPreparing')],
        3 => ['✅', __('modules.shop.stepReady')],
        4 => ['🍽️', __('modules.shop.stepServed')],
    ];
    $jbMaxPrep = $order->items->max(fn ($i) => $i->menuItem?->preparation_time);
    $jbMenuUrl = route('shop_restaurant', ['hash' => $restaurant->hash, 'new_order' => 1]) . '?branch=' . ($shopBranch->id ?? '');
@endphp

<link rel="stylesheet" href="{{ asset('css/jimbe-shop.css') }}?v={{ @filemtime(public_path('css/jimbe-shop.css')) }}">

<div class="jb-shop jb-done" wire:poll.10s="refreshOrderSuccess">
    <div class="jb-done__hero">
        <div class="jb-done__check">{{ $jbCancelled ? '✕' : '✓' }}</div>
        <h1>{{ $jbCancelled ? __('modules.shop.orderCancelled') : __('modules.shop.orderSent') }}</h1>
        <p>{{ $jbCancelled ? __('modules.shop.orderCancelledSub') : __('modules.shop.orderSentSub') }}</p>
        <div class="jb-done__meta">
            <span>{{ $order->show_formatted_order_number }}</span>
            @if ($order->table)
                <span>🪑 {{ $order->table->table_code }}</span>
            @endif
            @if ($jbMaxPrep)
                <span>⏱ ~{{ prep_time_label($jbMaxPrep) }}</span>
            @endif
        </div>
    </div>

    @unless ($jbCancelled)
        <div class="jb-panel jb-steps">
            @foreach ($jbSteps as $n => [$emoji, $label])
                <div class="jb-steps__i {{ $n < $jbStep ? 'is-done' : '' }} {{ $n === $jbStep ? 'is-now' : '' }}">
                    <div class="jb-steps__dot">{{ $n <= $jbStep ? $emoji : '·' }}</div>
                    <span>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    @endunless

    <div class="jb-panel">
        <h3 class="jb-panel__title">@lang('modules.shop.yourOrder')</h3>
        <div class="jb-recap">
            @foreach ($order->items as $item)
                <div class="jb-recap__row" wire:key="jb-recap-{{ $item->id }}">
                    <div>
                        <b>{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }} × {{ $item->menuItem?->item_name }}</b>
                        @if ($item->menuItemVariation)
                            <em>{{ $item->menuItemVariation->variation }}</em>
                        @endif
                        @foreach ($item->modifierOptions as $mod)
                            <small>+ {{ $mod->name }}</small>
                        @endforeach
                    </div>
                    <span>{{ currency_format($item->amount, $restaurant->currency_id) }}</span>
                </div>
            @endforeach
        </div>
        @if ($order->order_note)
            <p class="jb-recap__note">📝 {{ $order->order_note }}</p>
        @endif
        <div class="jb-sum" style="margin-top: 10px;">
            <div class="jb-sum__total"><span>@lang('modules.order.total')</span><b>{{ currency_format($order->total, $restaurant->currency_id) }}</b></div>
            <p class="jb-sum__hint">@lang('modules.shop.payAtEnd')</p>
        </div>
    </div>

    <a href="{{ $jbMenuUrl }}" class="jb-cta__btn jb-done__btn" style="display:block;text-align:center;text-decoration:none;">
        @lang('modules.shop.orderMore')
    </a>
</div>
