{{-- Jimbe : vue d'ensemble des réglages, une carte par groupe --}}
<div class="px-4 lg:px-6">
    <div class="mb-5">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">@lang('menu.settings')</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.overviewHint')</p>
    </div>

    <div class="grid items-start gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($sections as $group => $items)
            <section @class([
                'rounded-2xl border bg-white p-5 shadow-sm dark:bg-gray-800',
                'border-gray-200 dark:border-gray-700' => $group !== 'advanced',
                'border-dashed border-gray-300 dark:border-gray-600' => $group === 'advanced',
            ])>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $groups[$group][0] }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $groups[$group][1] }}</p>

                <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($items as [$tab, $label])
                        <li>
                            <a href="{{ route('settings.index') }}?tab={{ $tab }}" wire:navigate
                                class="group flex items-center justify-between py-2 text-sm text-gray-700 hover:text-skin-base dark:text-gray-200">
                                {{ $label }}
                                <svg class="h-4 w-4 text-gray-300 group-hover:text-skin-base dark:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                            </a>
                        </li>
                    @endforeach
                    @if ($group === 'team' && $canStaff)
                        <li>
                            <a href="{{ route('staff.index') }}" wire:navigate
                                class="group flex items-center justify-between py-2 text-sm text-gray-700 hover:text-skin-base dark:text-gray-200">
                                @lang('modules.settings.staffLink')
                                <svg class="h-4 w-4 text-gray-300 group-hover:text-skin-base dark:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                            </a>
                        </li>
                    @endif
                </ul>
            </section>
        @endforeach
    </div>
</div>
