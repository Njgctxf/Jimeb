<div class="tt-qr-codes-root"
    x-data="{ copied: false, copy() { navigator.clipboard?.writeText(@js($branchVisitUrl)); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #jb-qr-print, #jb-qr-print * { visibility: visible !important; }
            #jb-qr-print { position: absolute; inset: 0; margin: auto; box-shadow: none !important; border: 0 !important; }
        }
    </style>

    <div class="p-4 bg-white dark:bg-gray-800 dark:border-gray-700 print:hidden">
        <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.qrCodes')</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.table.singleQrIntro')</p>
    </div>

    <div class="px-4 my-6 flex justify-center">
        <div id="jb-qr-print" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $restaurantName }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.table.scanToSeeMenu')</p>

            <img src="{{ $branchQrUrl }}?v={{ time() }}" alt="QR code" class="mx-auto my-5 h-64 w-64 rounded-lg bg-white object-contain">

            <p class="break-all text-xs text-gray-400 print:hidden">{{ $branchVisitUrl }}</p>

            <div class="mt-5 flex flex-wrap justify-center gap-2 print:hidden">
                <x-button type="button" wire:click="downloadBranchQrCode">@lang('app.download')</x-button>
                <x-secondary-button type="button" onclick="window.print()">@lang('app.print')</x-secondary-button>
                <x-secondary-button type="button" @click="copy()">
                    <span x-text="copied ? @js(__('modules.settings.copied')) : @js(__('modules.table.copyLink'))"></span>
                </x-secondary-button>
            </div>

            <div class="mt-4 print:hidden">
                <button type="button" wire:click="generateQrCode"
                    wire:confirm="{{ __('modules.table.regenerateQrConfirm') }}"
                    class="text-xs text-gray-500 underline hover:text-gray-700 dark:text-gray-400">
                    @lang('modules.table.regenerateQr')
                </button>
            </div>
        </div>
    </div>
</div>
