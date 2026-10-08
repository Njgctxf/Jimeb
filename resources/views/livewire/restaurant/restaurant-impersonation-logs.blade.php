<div>
    @unless ($compact)
        <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">
                    @lang('modules.restaurant.impersonationAuditLog')
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @lang('modules.restaurant.impersonationAuditLogDescription')
                </p>
            </div>
        </div>
    @endunless

    <div @class(['px-4' => ! $compact, 'pt-4'])>
        @unless ($compact)
            <div class="mb-4">
                <x-input type="text" class="block w-full md:w-80" wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('app.search') }}" />
            </div>
        @endunless

        <div class="overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.restaurant.sessionStarted')
                            </th>
                            @unless ($restaurantId)
                                <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.billing.restaurant')
                                </th>
                            @endunless
                            <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.restaurant.superadmin')
                            </th>
                            <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.restaurant.impersonatedAs')
                            </th>
                            <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.restaurant.sessionDuration')
                            </th>
                            <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                @lang('app.status')
                            </th>
                            <th scope="col" class="py-2.5 px-4 text-xs font-medium ltr:text-right rtl:text-left text-gray-500 uppercase dark:text-gray-400">
                                @lang('app.action')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                        @forelse ($sessions as $session)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" wire:key="impersonation-session-{{ $session->id }}">
                                <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                    @include('common.date-time-display', ['date' => $session->started_at])
                                </td>
                                @unless ($restaurantId)
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                        {{ $session->restaurant?->name ?? __('modules.report.notAvailable') }}
                                    </td>
                                @endunless
                                <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                    <div>{{ $session->superadmin_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $session->superadmin_email }}</div>
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                    <div>{{ $session->impersonated_user_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $session->impersonated_user_email }}</div>
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                    @if ($session->durationInSeconds() !== null)
                                        {{ gmdate('H:i:s', $session->durationInSeconds()) }}
                                    @else
                                        {{ __('modules.report.notAvailable') }}
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-sm">
                                    @if ($session->isActive())
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                            @lang('modules.restaurant.sessionActive')
                                        </span>
                                    @else
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                                            @lang('modules.restaurant.sessionEnded')
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-sm text-right">
                                    <x-secondary-button wire:click="viewSession({{ $session->id }})" wire:loading.attr="disabled">
                                        @lang('modules.restaurant.viewSession')
                                    </x-secondary-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $restaurantId ? 6 : 7 }}" class="py-6 px-4 text-sm text-center text-gray-500 dark:text-gray-400">
                                    @lang('modules.restaurant.noImpersonationActivity')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($sessions->hasPages())
            <div class="mt-4">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    <x-dialog-modal wire:model.live="showSessionModal" maxWidth="4xl">
        <x-slot name="title">
            @lang('modules.restaurant.impersonationSessionDetails')
        </x-slot>

        <x-slot name="content">
            @if ($selectedSession)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('modules.restaurant.superadmin')</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedSession->superadmin_name }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ $selectedSession->superadmin_email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('modules.restaurant.impersonatedAs')</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedSession->impersonated_user_name }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ $selectedSession->impersonated_user_email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('modules.restaurant.sessionStarted')</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            @include('common.date-time-display', ['date' => $selectedSession->started_at])
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('modules.restaurant.sessionEnded')</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            @if ($selectedSession->ended_at)
                                @include('common.date-time-display', ['date' => $selectedSession->ended_at])
                            @else
                                @lang('modules.restaurant.sessionActive')
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('modules.report.ipAddress')</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedSession->ip_address ?? __('modules.report.notAvailable') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('modules.restaurant.sessionActions')</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedSession->actions->count() }}</p>
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="py-2 px-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">@lang('app.dateTime')</th>
                                    <th class="py-2 px-3 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">@lang('modules.restaurant.activity')</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                                @forelse ($selectedSession->actions as $action)
                                    <tr wire:key="impersonation-action-{{ $action->id }}">
                                        <td class="py-2 px-3 text-xs text-gray-900 dark:text-white whitespace-nowrap">
                                            @include('common.date-time-display', ['date' => $action->created_at])
                                        </td>
                                        <td class="py-2 px-3 text-xs text-gray-900 dark:text-white">
                                            @if ($action->description)
                                                <div class="font-medium">{{ $action->description }}</div>
                                                @if (! str_contains($action->description, $action->path))
                                                    <div class="text-gray-500 dark:text-gray-400">{{ $action->path }}</div>
                                                @endif
                                            @else
                                                <div>{{ $action->path }}</div>
                                                @if ($action->route_name)
                                                    <div class="text-gray-500 dark:text-gray-400">{{ $action->route_name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-4 px-3 text-sm text-center text-gray-500 dark:text-gray-400">
                                            @lang('modules.restaurant.noSessionActions')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeSessionModal" wire:loading.attr="disabled">
                @lang('app.close')
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
