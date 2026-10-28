{{-- Menu déroulant d'état : choisir directement l'état d'une commande (ou l'annuler). --}}
@php
    $jbCurrent = in_array($order->order_status?->value, ['placed', 'confirmed'], true) ? 'confirmed' : $order->order_status?->value;
    $jbTones = [
        'confirmed' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/50 dark:text-amber-100 dark:border-amber-700',
        'preparing' => 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-900/50 dark:text-sky-100 dark:border-sky-700',
        'food_ready' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-100 dark:border-emerald-700',
    ];
    $jbStatuses = \App\Livewire\Concerns\ManagesLiveOrders::quickStatuses();
@endphp
<select wire:key="jb-status-{{ $order->id }}-{{ $jbCurrent }}"
    class="jb-status-select cursor-pointer rounded-full border py-0.5 pl-2.5 pr-7 text-xs font-semibold focus:ring-2 focus:ring-skin-base {{ $jbTones[$jbCurrent] ?? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-500' }}"
    x-data
    x-on:change="
        const v = $event.target.value;
        if (v === 'cancel') {
            if (confirm(@js(__('modules.order.confirmCancelOrder')))) {
                $wire.cancelOrder({{ $order->id }}, prompt(@js(__('modules.order.cancelReasonPrompt'))) || '');
            } else { $event.target.value = @js($jbCurrent); }
            return;
        }
        $wire.setStatus({{ $order->id }}, v);
    ">
    @unless (array_key_exists($jbCurrent, $jbStatuses))
        <option value="{{ $jbCurrent }}" selected disabled>{{ $order->order_status?->translatedLabel() }}</option>
    @endunless
    @foreach ($jbStatuses as $value => [$emoji, $label])
        <option value="{{ $value }}" @selected($jbCurrent === $value)>{{ $label }}</option>
    @endforeach
    @if ($order->status !== 'paid')
        <option value="cancel">@lang('modules.order.cancelOrderShort')</option>
    @endif
</select>
