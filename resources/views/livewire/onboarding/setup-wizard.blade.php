<div class="mx-auto w-full max-w-xl px-4 py-8">
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bienvenue {{ user()->name }} 👋</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Configurons votre restaurant</h1>

    {{-- Progression --}}
    <ol class="mt-5 flex items-center gap-2 text-sm">
        @foreach ([1 => 'Votre restaurant', 2 => 'Vos tables', 3 => 'Votre premier plat'] as $n => $label)
            <li class="flex items-center gap-2">
                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-skin-base text-white' => $step >= $n,
                    'bg-gray-200 text-gray-500 dark:bg-gray-700' => $step < $n,
                ])>{{ $step > $n ? '✓' : $n }}</span>
                <span @class(['hidden sm:inline', 'font-semibold text-gray-900 dark:text-white' => $step === $n, 'text-gray-500' => $step !== $n])>{{ $label }}</span>
                @if ($n < 3) <span class="mx-1 h-px w-6 bg-gray-300 dark:bg-gray-600"></span> @endif
            </li>
        @endforeach
    </ol>

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-600 dark:bg-gray-800">

        {{-- Étape 1 : nom + logo --}}
        @if ($step === 1)
            <form wire:submit="saveIdentity" class="space-y-5">
                <div>
                    <x-label for="restaurantName" value="Nom du restaurant" />
                    <x-input id="restaurantName" type="text" class="mt-1 block w-full" wire:model="restaurantName" placeholder="ex. Chez Awa" />
                    <x-input-error for="restaurantName" class="mt-1" />
                </div>
                <div>
                    <x-label for="logo" value="Logo (facultatif)" />
                    <input id="logo" type="file" accept="image/*" wire:model="logo" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 dark:text-gray-300" />
                    <x-input-error for="logo" class="mt-1" />
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Aperçu du logo" class="mt-3 h-20 w-20 rounded-lg border object-contain">
                    @elseif (restaurant()->logo)
                        <img src="{{ restaurant()->logo_url }}" alt="Logo actuel" class="mt-3 h-20 w-20 rounded-lg border object-contain">
                    @endif
                </div>
                <div class="flex justify-end">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveIdentity,logo">Continuer</x-button>
                </div>
            </form>
        @endif

        {{-- Étape 2 : tables --}}
        @if ($step === 2)
            <form wire:submit="saveTables" class="space-y-5">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Comment vos clients commandent-ils ?</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vous pourrez changer à tout moment.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'per_table' => ['Un QR code par table', 'La commande arrive déjà avec le numéro de table. Un QR à imprimer par table.'],
                            'single' => ['Un seul QR code', 'Un seul QR à imprimer. Le client écrit le numéro de sa table avant de commander.'],
                        ] as $key => [$title, $text])
                            <label wire:key="wiz-mode-{{ $key }}" class="cursor-pointer">
                                <input type="radio" class="peer sr-only" name="qrMode" value="{{ $key }}" wire:model.live="qrMode">
                                <span class="block rounded-xl border-2 border-gray-200 p-3 text-left transition peer-checked:border-skin-base peer-checked:bg-skin-base/5 dark:border-gray-600">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $text }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="qrMode" class="mt-1" />
                </div>
                <div>
                    <x-label for="tableCount" value="Combien de tables avez-vous ?" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les tables « Table 1 », « Table 2 »… sont créées avec leur QR code. Vous pourrez les renommer plus tard.</p>
                    <x-input id="tableCount" type="number" min="1" max="200" class="mt-2 block w-40" wire:model="tableCount" placeholder="ex. 12" />
                    <x-input-error for="tableCount" class="mt-1" />
                </div>
                <div class="flex items-center justify-between">
                    <button type="button" class="text-sm text-gray-500 hover:underline" wire:click="back">← Retour</button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveTables">Créer mes tables</x-button>
                </div>
            </form>
        @endif

        {{-- Étape 3 : première catégorie + premier plat --}}
        @if ($step === 3)
            <form wire:submit="finish" class="space-y-5">
                <div>
                    <x-label for="categoryName" value="Catégorie" />
                    <x-input id="categoryName" type="text" list="jb-categories" class="mt-1 block w-full" wire:model="categoryName" placeholder="ex. Plats de résistance" />
                    <datalist id="jb-categories">
                        @foreach ($categories as $c) <option value="{{ $c }}"> @endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-gray-500">Boissons, Entrées, Plats, Desserts… Vous en ajouterez d'autres ensuite.</p>
                    <x-input-error for="categoryName" class="mt-1" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <x-label for="dishName" value="Nom du plat" />
                        <x-input id="dishName" type="text" class="mt-1 block w-full" wire:model="dishName" placeholder="ex. Poulet braisé" />
                        <x-input-error for="dishName" class="mt-1" />
                    </div>
                    <div>
                        <x-label for="dishPrice" value="Prix" />
                        <x-input id="dishPrice" type="number" min="0" step="any" class="mt-1 block w-full" wire:model="dishPrice" placeholder="3500" />
                        <x-input-error for="dishPrice" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-label for="dishDescription" value="Description (facultatif)" />
                    <x-input id="dishDescription" type="text" class="mt-1 block w-full" wire:model="dishDescription" />
                </div>

                <div>
                    <x-label for="dishImage" value="Photo (facultatif)" />
                    <input id="dishImage" type="file" accept="image/*" wire:model="dishImage" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 dark:text-gray-300" />
                    <x-input-error for="dishImage" class="mt-1" />
                </div>

                @include('livewire.forms.partials.side-groups')

                <div class="flex items-center justify-between pt-2">
                    <button type="button" class="text-sm text-gray-500 hover:underline" wire:click="back">← Retour</button>
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="finish,dishImage">Terminer</x-button>
                </div>
            </form>
        @endif
    </div>
</div>
