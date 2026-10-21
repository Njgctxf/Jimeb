{{-- Encaisser : un clic sur le moyen de paiement (tableau en direct + page de détail) --}}
@if ($collectOrder)
    @php $pref = $collectOrder->pay_method; @endphp
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-3 sm:items-center" wire:key="jb-collect-{{ $collectOrder->id }}" wire:click.self="closeCollect">
        <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">@lang('modules.order.quickPayTitle') · {{ $collectOrder->table?->table_code }} · {{ $collectOrder->show_formatted_order_number }}</p>
                    <p class="mt-1 text-3xl font-extrabold text-gray-900 dark:text-white">{{ currency_format($collectOrder->remainingAmount(), restaurant()->currency_id) }}</p>
                    @if ($collectOrder->pay_split > 1)
                        <p class="text-xs text-gray-500">👥 ÷{{ $collectOrder->pay_split }} = {{ currency_format(ceil($collectOrder->remainingAmount() / $collectOrder->pay_split), restaurant()->currency_id) }} / pers.</p>
                    @endif
                </div>
                <button type="button" wire:click="closeCollect" class="text-2xl leading-none text-gray-400">×</button>
            </div>
            <p class="mb-2 mt-4 text-sm text-gray-600 dark:text-gray-300">@lang('modules.order.quickPayHint')</p>
            <div class="grid grid-cols-3 gap-2">
                @foreach (\App\Models\Order::PAY_METHODS as $m => [$emoji, $label])
                    <button type="button" wire:click="quickPay('{{ $m }}')" wire:loading.attr="disabled"
                        class="flex flex-col items-center gap-1 rounded-xl border-2 px-2 py-3 text-sm font-semibold {{ $pref === $m ? 'border-skin-base bg-skin-base/10 text-skin-base' : 'border-gray-200 text-gray-700 hover:border-gray-300 dark:border-gray-600 dark:text-gray-200' }}">
                        <span class="text-2xl">{{ $emoji }}</span>@lang($label)
                    </button>
                @endforeach
            </div>
            <button type="button" wire:click="advancedPayment" class="mt-4 w-full text-center text-xs text-gray-400 underline">@lang('modules.order.quickPayAdvanced')</button>
        </div>
    </div>
@endif
