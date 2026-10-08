<div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm 2xl:col-span-2 dark:border-gray-700 sm:p-6 dark:bg-gray-800">
    @if (!in_array('Customer Display', restaurant_modules()))
        <x-upgrade-box :title="__('modules.settings.customerDisplayUpgradeHeading')" :text="__('modules.settings.customerDisplayUpgradeInfo')"></x-upgrade-box>
    @else
        <div class="flex flex-col gap-4 mb-6 border-b border-gray-200 dark:border-gray-700 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-xl font-semibold dark:text-white">@lang('modules.settings.customerDisplaySettings')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.customerDisplaySettingsDescription')</p>
            </div>
            <a href="{{ route('customer.display') }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                @lang('menu.customerDisplay')
            </a>
        </div>

        <form wire:submit="submitForm" class="space-y-6">
            <div class="flex items-center gap-x-3 rounded-lg bg-gray-100 p-4 shadow-sm dark:bg-gray-700">
                <x-checkbox
                    name="showPaymentQr"
                    id="showPaymentQr"
                    wire:model.live="showPaymentQr"
                    class="mr-4 accent-indigo-600" />
                <div class="flex-1">
                    <x-label
                        for="showPaymentQr"
                        :value="__('modules.settings.showPaymetQrCode')"
                        class="!mb-1 font-medium" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @lang('modules.settings.showPaymentQrOnCustomerDisplayDescription')
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Note: You can add the QR code in the Payments tab.
                    </p>
                </div>
            </div>

            <div class="pt-2">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="submitForm" class="inline-flex items-center gap-x-2">
                    <div>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" wire:loading.remove wire:target="submitForm">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg aria-hidden="true" wire:loading wire:target="submitForm" class="h-4 w-4 animate-spin text-gray-200 dark:text-gray-600 fill-skin-base" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    </div>
                    <span>@lang('modules.settings.save')</span>
                </x-button>
            </div>
        </form>
    @endif
</div>
