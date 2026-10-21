<div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
    <div class="mb-5">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">@lang('modules.settings.welcomePage')</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.welcomePageHelp')</p>
    </div>

    <form wire:submit.prevent="submitForm" class="space-y-6">
        {{-- Photo du restaurant --}}
        <div>
            <x-label for="cover" :value="__('modules.settings.welcomeCover')" />
            <input id="cover" type="file" accept="image/*" wire:model="cover"
                class="block w-full mt-1 text-sm border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-slate-500">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('modules.settings.welcomeCoverHelp')</p>
            <x-input-error for="cover" class="mt-2" />

            <div class="flex items-center gap-4 mt-3">
                @if ($cover)
                    <img src="{{ $cover->temporaryUrl() }}" alt="" class="object-cover w-40 h-24 rounded-xl border border-gray-200">
                @elseif ($this->coverUrl)
                    <img src="{{ $this->coverUrl }}" alt="" class="object-cover w-40 h-24 rounded-xl border border-gray-200">
                    <button type="button" wire:click="removeCover" class="text-sm text-red-600 hover:underline">@lang('app.remove')</button>
                @endif
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-label for="welcomeTitle" :value="__('modules.settings.welcomeTitleLabel')" />
                <x-input id="welcomeTitle" type="text" class="block w-full mt-1" wire:model.defer="welcomeTitle" maxlength="120"
                    placeholder="{{ __('modules.shop.welcomeAt', ['name' => $settings->name]) }}" />
                <x-input-error for="welcomeTitle" class="mt-2" />
            </div>
            <div>
                <x-label for="welcomeButtonText" :value="__('modules.settings.welcomeButtonLabel')" />
                <x-input id="welcomeButtonText" type="text" class="block w-full mt-1" wire:model.defer="welcomeButtonText" maxlength="40"
                    placeholder="{{ __('modules.shop.seeMenu') }}" />
                <x-input-error for="welcomeButtonText" class="mt-2" />
            </div>
        </div>

        <div>
            <x-label for="welcomeSubtitle" :value="__('modules.settings.welcomeSubtitleLabel')" />
            <x-input id="welcomeSubtitle" type="text" class="block w-full mt-1" wire:model.defer="welcomeSubtitle" maxlength="200"
                placeholder="{{ __('modules.shop.w1Sub') }}" />
            <x-input-error for="welcomeSubtitle" class="mt-2" />
        </div>

        <div class="md:w-1/2">
            <x-label for="welcomeTableLabel" :value="__('modules.settings.welcomeTableLabelLabel')" />
            <x-input id="welcomeTableLabel" type="text" class="block w-full mt-1" wire:model.defer="welcomeTableLabel" maxlength="30"
                placeholder="{{ __('modules.shop.tableWord') }}" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('modules.settings.welcomeTableLabelHelp')</p>
            <x-input-error for="welcomeTableLabel" class="mt-2" />
        </div>

        <div>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="submitForm,cover">@lang('app.save')</x-button>
        </div>
    </form>
</div>
