<div class="tt-qr-codes-root"
    x-data="{
        copied: false,
        printMode: 'all',
        copy() { navigator.clipboard?.writeText(@js($branchVisitUrl)); this.copied = true; setTimeout(() => this.copied = false, 2000); },
        printNow(mode) { this.printMode = mode; this.$nextTick(() => window.print()); }
    }"
    x-bind:data-print="printMode">
    <style>
        .jb-print-only { display: none; }
        @media print {
            body * { visibility: hidden !important; }
            .tt-qr-codes-root[data-print="all"] #jb-print-all,
            .tt-qr-codes-root[data-print="all"] #jb-print-all *,
            .tt-qr-codes-root[data-print="general"] #jb-print-general,
            .tt-qr-codes-root[data-print="general"] #jb-print-general * { visibility: visible !important; }
            .tt-qr-codes-root[data-print="all"] #jb-print-all { display: grid; position: absolute; left: 0; top: 0; width: 100%; grid-template-columns: repeat(2, 1fr); gap: 12mm; padding: 10mm; }
            .tt-qr-codes-root[data-print="general"] #jb-print-general { display: block; position: absolute; inset: 0; margin: auto; width: 100%; max-width: 120mm; text-align: center; padding-top: 20mm; }
            .jb-print-card { break-inside: avoid; border: 1px solid #ccc; border-radius: 8px; padding: 8mm; text-align: center; }
        }
    </style>

    <div class="p-4 bg-white dark:bg-gray-800 dark:border-gray-700 print:hidden">
        <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.qrCodes')</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Un QR code par table : la table est reconnue automatiquement et le client peut commander. Le QR général (entrée, flyers, réseaux sociaux) ouvre le menu en consultation seule.</p>
    </div>

    {{-- Combien de tables ? --}}
    <div class="px-4 mt-4 print:hidden">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <p class="font-semibold text-gray-900 dark:text-white">Combien de tables avez-vous ?</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tapez un nombre : les tables « Table 1 », « Table 2 »… sont créées avec leur QR code. Vous pourrez les renommer ensuite.</p>
            <form wire:submit="createTables" class="mt-3 flex flex-wrap items-start gap-2">
                <div>
                    <x-input type="number" min="1" max="200" class="w-32" placeholder="ex. 12" wire:model="tableCount" />
                    <x-input-error for="tableCount" class="mt-1" />
                </div>
                <x-button type="submit" wire:loading.attr="disabled" wire:target="createTables">Créer les tables</x-button>
            </form>
        </div>
    </div>

    {{-- QR par table --}}
    <div class="px-4 mt-6 print:hidden">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">QR codes des tables ({{ count($tableQrs) }})</h2>
            @if (count($tableQrs))
                <x-button type="button" @click="printNow('all')">Imprimer tous les QR</x-button>
            @endif
        </div>

        @forelse ($tableQrs as $item)
            @if ($loop->first)
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @endif
            <div class="rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm dark:border-gray-600 dark:bg-gray-800" wire:key="tqr-{{ $item['table_id'] }}">
                <p class="font-semibold text-gray-900 dark:text-white">{{ $item['label'] }}</p>
                <img src="{{ $item['image_url'] }}?v={{ $item['version'] }}" alt="QR {{ $item['label'] }}" class="mx-auto my-2 h-32 w-32 rounded bg-white object-contain" loading="lazy">
                <button type="button" class="text-sm text-skin-base hover:underline" wire:click="downloadQrCode(@js($item['table_code']), {{ $item['branch_id'] }})">@lang('app.download')</button>
            </div>
            @if ($loop->last)
                </div>
            @endif
        @empty
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Aucune table pour le moment. Indiquez le nombre de tables ci-dessus.</p>
        @endforelse
    </div>

    {{-- QR général --}}
    <div class="px-4 my-6 print:hidden">
        <div class="mx-auto w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <p class="text-lg font-semibold text-gray-900 dark:text-white">QR général</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Menu en consultation seule, sans commande : pour l'entrée, les flyers, Instagram ou votre site.</p>

            <img src="{{ $branchQrUrl }}?v={{ time() }}" alt="QR général" class="mx-auto my-5 h-56 w-56 rounded-lg bg-white object-contain">

            <p class="break-all text-xs text-gray-400">{{ $branchVisitUrl }}</p>

            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <x-button type="button" wire:click="downloadBranchQrCode">@lang('app.download')</x-button>
                <x-secondary-button type="button" @click="printNow('general')">@lang('app.print')</x-secondary-button>
                <x-secondary-button type="button" @click="copy()">
                    <span x-text="copied ? @js(__('modules.settings.copied')) : @js(__('modules.table.copyLink'))"></span>
                </x-secondary-button>
            </div>

            <div class="mt-4">
                <button type="button" wire:click="generateQrCode"
                    wire:confirm="{{ __('modules.table.regenerateQrConfirm') }}"
                    class="text-xs text-gray-500 underline hover:text-gray-700 dark:text-gray-400">
                    @lang('modules.table.regenerateQr')
                </button>
            </div>
        </div>
    </div>

    {{-- Zones d'impression (invisibles à l'écran) --}}
    <div id="jb-print-general" class="jb-print-only">
        <p style="font-size:22pt;font-weight:700">{{ $restaurantName }}</p>
        <p style="margin-top:4mm">Scannez pour voir notre menu</p>
        <img src="{{ $branchQrUrl }}?v={{ time() }}" alt="QR général" style="width:90mm;height:90mm;margin:8mm auto">
    </div>

    <div id="jb-print-all" class="jb-print-only">
        @foreach ($tableQrs as $item)
            <div class="jb-print-card">
                <p style="font-size:16pt;font-weight:700">{{ $restaurantName }}</p>
                <img src="{{ $item['image_url'] }}?v={{ $item['version'] }}" alt="QR {{ $item['label'] }}" style="width:60mm;height:60mm;margin:4mm auto">
                <p style="font-size:20pt;font-weight:700">{{ $item['label'] }}</p>
                <p style="font-size:10pt">Scannez pour voir le menu et commander</p>
            </div>
        @endforeach
    </div>
</div>
