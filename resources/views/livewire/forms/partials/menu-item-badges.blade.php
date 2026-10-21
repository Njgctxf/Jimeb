@php
    $badgeKeys = \App\Support\DishBadges::keys();
    $badgePrefix = $badgeDomPrefix ?? 'badge-mi';
@endphp
<div>
    <x-label :value="__('modules.menu.badgesSectionTitle')" class="mb-3" />
    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">@lang('modules.menu.badgesSectionHelp')</p>
    <ul class="grid w-full grid-cols-2 gap-2 md:grid-cols-3">
        @foreach ($badgeKeys as $key)
            <li wire:key="{{ $badgePrefix }}-{{ $key }}">
                <input type="checkbox" id="{{ $badgePrefix }}-{{ $key }}" class="peer hidden"
                    wire:model.live="selectedBadges" value="{{ $key }}">
                <label for="{{ $badgePrefix }}-{{ $key }}"
                    class="inline-flex w-full cursor-pointer select-none items-center gap-2 rounded-lg border-2 border-gray-200 bg-white p-2 text-sm font-medium text-gray-600 hover:bg-gray-100 peer-checked:border-skin-base peer-checked:bg-skin-base/5 peer-checked:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:peer-checked:text-skin-base">
                    <span class="text-lg leading-none">{{ \App\Support\DishBadges::emoji($key) }}</span>
                    <span class="min-w-0 flex-1 text-left leading-snug">@lang(\App\Support\DishBadges::langKey($key))</span>
                </label>
            </li>
        @endforeach
    </ul>
</div>
