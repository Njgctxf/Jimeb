@php
    $positions = [
        'hero' => ['title' => __('modules.settings.bannerHero'), 'help' => __('modules.settings.bannerHeroHelp'), 'max' => \App\Models\RestaurantBanner::MAX_HERO],
        'middle' => ['title' => __('modules.settings.bannerMiddle'), 'help' => __('modules.settings.bannerMiddleHelp'), 'max' => \App\Models\RestaurantBanner::MAX_MIDDLE],
    ];
    $targetLabels = [
        'menu' => __('modules.settings.targetMenu'),
        'promo' => __('modules.settings.targetPromo'),
        'category' => __('modules.settings.targetCategory'),
        'dish' => __('modules.settings.targetDish'),
        'call' => __('modules.settings.targetCall'),
        'cart' => __('modules.settings.targetCart'),
    ];
@endphp

<div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
    <div class="mb-5">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">@lang('modules.settings.banners')</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.bannersHelp')</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-10">
        @foreach ($positions as $pos => $meta)
            @php $list = collect($rows)->filter(fn ($r) => $r['position'] === $pos); @endphp

            <section>
                <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $meta['title'] }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $meta['help'] }}</p>
                    </div>
                    @if ($list->count() < $meta['max'])
                        <x-secondary-button type="button" wire:click="addBanner('{{ $pos }}')">+ @lang('modules.settings.addBanner')</x-secondary-button>
                    @endif
                </div>

                @forelse ($list as $id => $row)
                    <div class="p-4 mb-4 border border-gray-200 rounded-xl dark:border-gray-600" wire:key="banner-{{ $id }}">
                        <div class="grid gap-4 md:grid-cols-[220px_1fr]">
                            {{-- Image --}}
                            <div>
                                <div class="flex items-center justify-center w-full overflow-hidden bg-gray-100 rounded-xl aspect-[2/1] dark:bg-gray-700">
                                    @if (!empty($uploads[$id]))
                                        <img src="{{ $uploads[$id]->temporaryUrl() }}" alt="" class="object-cover w-full h-full">
                                    @elseif (!empty($row['image']))
                                        <img src="{{ asset_url_local_s3($row['image']) }}" alt="" class="object-cover w-full h-full">
                                    @else
                                        <span class="px-2 text-xs text-center text-gray-400">@lang('modules.settings.noBannerImage')</span>
                                    @endif
                                </div>
                                <input type="file" accept="image/*" wire:model="uploads.{{ $id }}"
                                    class="block w-full mt-2 text-xs border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-slate-500">
                                <x-input-error for="uploads.{{ $id }}" class="mt-1" />
                                @if (!empty($row['image']))
                                    <button type="button" wire:click="removeImage({{ $id }})" class="mt-1 text-xs text-red-600 hover:underline">@lang('modules.settings.removeBannerImage')</button>
                                @endif
                            </div>

                            {{-- Textes + destination --}}
                            <div class="space-y-3">
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <x-label :value="__('modules.settings.bannerTitleLabel')" />
                                        <x-input type="text" class="block w-full mt-1" wire:model.defer="rows.{{ $id }}.title" maxlength="90" placeholder="{{ __('modules.settings.bannerTitlePh') }}" />
                                    </div>
                                    <div>
                                        <x-label :value="__('modules.settings.bannerButtonLabel')" />
                                        <x-input type="text" class="block w-full mt-1" wire:model.defer="rows.{{ $id }}.button_text" maxlength="30" placeholder="{{ __('modules.settings.bannerButtonPh') }}" />
                                    </div>
                                </div>
                                <div>
                                    <x-label :value="__('modules.settings.bannerSubtitleLabel')" />
                                    <x-input type="text" class="block w-full mt-1" wire:model.defer="rows.{{ $id }}.subtitle" maxlength="160" placeholder="{{ __('modules.settings.bannerSubtitlePh') }}" />
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <x-label :value="__('modules.settings.bannerGoesTo')" />
                                        <x-select class="block w-full mt-1" wire:model.live="rows.{{ $id }}.target_type">
                                            @foreach ($targetLabels as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                    @if ($row['target_type'] === 'dish')
                                        <div>
                                            <x-label :value="__('modules.settings.chooseDish')" />
                                            <x-select class="block w-full mt-1" wire:model.defer="rows.{{ $id }}.target_id">
                                                <option value="">--</option>
                                                @foreach ($dishes as $d)
                                                    <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                                                @endforeach
                                            </x-select>
                                        </div>
                                    @elseif ($row['target_type'] === 'category')
                                        <div>
                                            <x-label :value="__('modules.settings.chooseCategory')" />
                                            <x-select class="block w-full mt-1" wire:model.defer="rows.{{ $id }}.target_id">
                                                <option value="">--</option>
                                                @foreach ($categories as $c)
                                                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                                @endforeach
                                            </x-select>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300">
                                        <input type="checkbox" class="rounded border-gray-300 text-skin-base focus:ring-skin-base" wire:model.defer="rows.{{ $id }}.is_active">
                                        @lang('modules.settings.bannerActive')
                                    </label>
                                    <div class="flex items-center gap-2">
                                        @if ($list->count() > 1)
                                            <button type="button" wire:click="move({{ $id }}, 'up')" class="px-2 py-1 text-sm border border-gray-300 rounded-lg dark:border-gray-600" title="@lang('modules.settings.moveUp')">↑</button>
                                            <button type="button" wire:click="move({{ $id }}, 'down')" class="px-2 py-1 text-sm border border-gray-300 rounded-lg dark:border-gray-600" title="@lang('modules.settings.moveDown')">↓</button>
                                        @endif
                                        <button type="button" wire:click="deleteBanner({{ $id }})" wire:confirm="{{ __('modules.settings.deleteBannerConfirm') }}"
                                            class="px-3 py-1 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50">@lang('app.delete')</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-sm text-center text-gray-500 border border-dashed border-gray-300 rounded-xl dark:border-gray-600 dark:text-gray-400">@lang('modules.settings.noBannerYet')</p>
                @endforelse
            </section>
        @endforeach

        <div>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save,uploads">@lang('app.save')</x-button>
        </div>
    </form>
</div>
