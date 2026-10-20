<div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ce plat est servi avec…</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Accompagnements, cuisson, sauce, taille… Laissez vide pour un plat sans choix (boisson, dessert).</p>

    <div class="space-y-4">
        @foreach ($sideGroups as $g => $group)
            <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-4" wire:key="side-group-{{ $g }}-{{ $group['id'] ?? 'new' }}">
                <div class="flex items-start gap-2">
                    <div class="flex-1">
                        @if (! empty($group['id']))
                            <p class="font-medium text-gray-900 dark:text-white">{{ $group['name'] }}</p>
                        @else
                            <x-input type="text" class="block w-full" placeholder="Nom du choix (ex. Accompagnement)" wire:model="sideGroups.{{ $g }}.name" />
                        @endif
                    </div>
                    <button type="button" class="text-sm text-red-600 hover:underline" wire:click="removeSideGroup({{ $g }})">Retirer</button>
                </div>

                <div class="mt-3 space-y-2">
                    @foreach ($group['options'] as $o => $option)
                        <div class="flex items-center gap-2" wire:key="side-opt-{{ $g }}-{{ $o }}">
                            @if (! empty($group['id']))
                                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $option['name'] }}</span>
                                <span class="text-sm text-gray-500">{{ ((float) $option['price']) > 0 ? '+ '.currency_format($option['price'], restaurant()->currency_id) : '' }}</span>
                            @else
                                <x-input type="text" class="flex-1" placeholder="Choix (ex. Riz)" wire:model="sideGroups.{{ $g }}.options.{{ $o }}.name" />
                                <x-input type="number" min="0" step="any" class="w-32" placeholder="+ prix" wire:model="sideGroups.{{ $g }}.options.{{ $o }}.price" />
                                <button type="button" class="text-gray-400 hover:text-red-600" wire:click="removeSideOption({{ $g }}, {{ $o }})" aria-label="Supprimer le choix">✕</button>
                            @endif
                        </div>
                    @endforeach

                    @if (empty($group['id']))
                        <button type="button" class="text-sm text-skin-base hover:underline" wire:click="addSideOption({{ $g }})">+ Ajouter un choix</button>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="rounded" wire:model="sideGroups.{{ $g }}.required"> Le client doit choisir
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="rounded" wire:model="sideGroups.{{ $g }}.multiple"> Plusieurs choix possibles
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="button" class="px-3 py-2 text-sm font-medium border rounded-lg text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600" wire:click="addSideGroup">+ Ajouter un accompagnement</button>

        @if (count($sideGroupChoices))
            <select class="text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" x-on:change="if ($event.target.value) { $wire.reuseSideGroup($event.target.value); $event.target.value = ''; }">
                <option value="">Réutiliser un accompagnement…</option>
                @foreach ($sideGroupChoices as $choice)
                    <option value="{{ $choice['id'] }}">{{ $choice['name'] }}</option>
                @endforeach
            </select>
        @endif
    </div>
</div>
