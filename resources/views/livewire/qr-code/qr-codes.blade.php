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
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choisissez comment vos clients commandent, puis générez et imprimez vos QR codes. Vous pouvez changer de mode à tout moment.</p>
    </div>

    {{-- Choix du mode : un QR par table, ou un QR unique --}}
    <div class="px-4 mt-4 print:hidden">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <p class="font-semibold text-gray-900 dark:text-white">@if ($qrMode) Mode des QR codes @else Comment vos clients commandent-ils ? @endif</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@if ($qrMode) Vous pouvez changer de mode à tout moment : vos QR déjà imprimés continuent de fonctionner. @else Choisissez avant de générer vos QR codes. Vous pourrez changer à tout moment. @endif</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'per_table' => ['Un QR code par table', 'Chaque table a son propre QR. La commande arrive déjà avec le bon numéro de table : aucune erreur possible. Il faut imprimer un QR par table.'],
                    'single' => ['Un seul QR code pour tout le restaurant', 'Un seul QR à imprimer. Avant de commander, le client écrit le numéro de sa table (champ obligatoire). Posez simplement un numéro sur chaque table.'],
                ] as $key => [$title, $text])
                    <button type="button" wire:click="setQrMode('{{ $key }}')" wire:key="qrmode-{{ $key }}"
                        class="rounded-xl border-2 p-4 text-left transition {{ $qrMode === $key ? 'border-skin-base bg-skin-base/5' : 'border-gray-200 hover:border-gray-300 dark:border-gray-600' }}">
                        <span class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border-2 {{ $qrMode === $key ? 'border-skin-base bg-skin-base' : 'border-gray-300' }}">
                                @if ($qrMode === $key)<span class="h-2 w-2 rounded-full bg-white"></span>@endif
                            </span>
                            {{ $title }}
                        </span>
                        <span class="mt-2 block text-sm text-gray-500 dark:text-gray-400">{{ $text }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @if ($qrMode)

    {{-- Combien de tables ? --}}
    <div class="px-4 mt-4 print:hidden">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <p class="font-semibold text-gray-900 dark:text-white">Combien de tables avez-vous ?</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@if ($qrMode === 'single') Tapez un nombre : les tables « Table 1 », « Table 2 »… sont créées. Les clients pourront indiquer leur numéro, et chaque commande arrivera avec la bonne table. @else Tapez un nombre : les tables « Table 1 », « Table 2 »… sont créées avec leur QR code. Vous pourrez les renommer ensuite. @endif</p>
            <form wire:submit="createTables" class="mt-3 flex flex-wrap items-start gap-2">
                <div>
                    <x-input type="number" min="1" max="200" class="w-32" placeholder="ex. 12" wire:model="tableCount" />
                    <x-input-error for="tableCount" class="mt-1" />
                </div>
                <x-button type="submit" wire:loading.attr="disabled" wire:target="createTables">Créer les tables</x-button>
            </form>
        </div>
    </div>


    {{-- Personnaliser les QR codes --}}
    <div class="px-4 mt-4 print:hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white">Personnaliser vos QR codes</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Forme du code, couleurs, coins, logo, image… Vous dessinez un QR code et son style s'applique à tous les autres.</p>
            </div>
            <a href="{{ route('qrcodes.designer') }}" class="inline-flex items-center rounded-lg bg-skin-base px-4 py-2.5 text-sm font-medium text-white hover:bg-skin-base/90">Personnaliser le design</a>
        </div>
    </div>

    {{-- QR par table --}}
    @if ($qrMode === 'per_table')
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

    @endif

    {{-- QR général --}}
    <div class="px-4 my-6 print:hidden">
        <div class="mx-auto w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-600 dark:bg-gray-800">
            @if ($qrMode === 'single')
                <p class="text-lg font-semibold text-gray-900 dark:text-white">Votre QR code unique</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">À imprimer une fois : posez-le sur toutes les tables. Le client écrit le numéro de sa table avant de commander.</p>
            @else
                <p class="text-lg font-semibold text-gray-900 dark:text-white">QR général</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Menu en consultation seule, sans commande : pour l'entrée, les flyers, Instagram ou votre site.</p>
            @endif

            <img src="{{ $branchQrUrl }}?v={{ time() }}" alt="QR général" class="mx-auto my-5 h-56 w-56 rounded-lg bg-white object-contain">

            @if ($qrMode === 'single')
                <div class="mt-1 text-left">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Numéros de table acceptés ({{ count($tableQrs) }}) :</p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        @forelse ($tableQrs as $item)
                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                        @empty
                            <span class="text-xs text-amber-700">Créez d'abord vos tables ci-dessus.</span>
                        @endforelse
                    </div>
                </div>
            @endif

            <p class="mt-3 break-all text-xs text-gray-400">{{ $branchVisitUrl }}</p>

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
        <p style="margin-top:4mm">@if ($qrMode === 'single') Scannez pour commander depuis votre table @else Scannez pour voir notre menu @endif</p>
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
    @endif
</div>
