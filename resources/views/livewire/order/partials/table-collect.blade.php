{{-- Encaisser la table entière : toutes les commandes servies, un clic sur le moyen de paiement --}}
@if ($collectTable)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-3 sm:items-center" wire:key="jb-collect-table-{{ $collectTable['table']?->id }}" wire:click.self="closeTableCollect">
        <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl dark:bg-gray-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">@lang('modules.order.quickPayTableTitle') · {{ $collectTable['table']?->table_code }} · @lang('modules.order.boardTableOrders', ['count' => $collectTable['orders']->count()])</p>
                    <p class="mt-1 text-3xl font-extrabold text-gray-900 dark:text-white">{{ currency_format($collectTable['total'], restaurant()->currency_id) }}</p>
                </div>
                <button type="button" wire:click="closeTableCollect" class="text-2xl leading-none text-gray-400">×</button>
            </div>
            @if ($collectTable['pending'])
                <p class="mt-3 rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ trans_choice('modules.order.boardTablePending', $collectTable['pending'], ['count' => $collectTable['pending']]) }}</p>
            @endif
            <p class="mb-2 mt-4 text-sm text-gray-600 dark:text-gray-300">@lang('modules.order.quickPayHint')</p>
            <div class="grid grid-cols-3 gap-2">
                @foreach (\App\Models\Order::PAY_METHODS as $m => [$emoji, $label])
                    <button type="button" wire:click="quickPayTable('{{ $m }}')" wire:loading.attr="disabled"
                        class="rounded-xl border-2 border-gray-200 px-2 py-4 text-sm font-semibold text-gray-700 hover:border-gray-300 dark:border-gray-600 dark:text-gray-200">
                        @lang($label)
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@endif
