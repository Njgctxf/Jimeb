
<div>

    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 rounded-xl">
        <div class="w-full mb-1">
       
            <div class="items-center justify-between block sm:flex ">
                <div class="flex items-center mb-4 sm:mb-0 gap-x-2 flex-wrap gap-2">
                    <form class="ltr:pr-3 rtl:pl-3 " action="#" method="GET">
                        <label for="products-search" class="sr-only">Search</label>
                        <div class="relative w-48 mt-1 sm:w-60">
                            <x-input id="menu_name" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.searchMenuItems') }}" wire:model.live.debounce.500ms="search"  />
                        </div>
                    </form>

                    <x-secondary-button wire:click="$dispatch('showMenuItemFilters')" class="shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter ltr:mr-1 rtl:ml-1" viewBox="0 0 16 16">
                            <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                        </svg> @lang('app.showFilter')
                    </x-secondary-button>
                
                    @php $menuItemStats = getRestaurantMenuItemStats(user()->restaurant_id); @endphp
                    @php $canCreateDish = user_can('Create Menu Item') && ($menuItemStats['unlimited'] || $menuItemStats['current_count'] < $menuItemStats['menu_items_limit']); @endphp

                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="shrink-0">
                                @lang('app.moreActions')
                                <svg class="w-4 h-4 ltr:ml-1 rtl:mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <a href="{{ route('menu-items.entities.sort') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">@lang('modules.menu.sortMenuItems')</a>
                            @if(user_can('Show Item Category'))
                                <a href="{{ route('item-categories.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">@lang('menu.itemCategories')</a>
                            @endif
                            @if(user_can('Show Menu Item'))
                                <a href="{{ route('modifier-groups.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">@lang('menu.modifierGroups')</a>
                                <a href="{{ route('item-modifiers.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">@lang('menu.itemModifiers')</a>
                            @endif
                            @if(user_can('Export Menu Item'))
                                <a href="{{ route('menu-items.export') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">@lang('modules.menu.exportMenuItems')</a>
                            @endif
                            @if($canCreateDish)
                                <a href="{{ route('menu-items.bulk-import') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">@lang('modules.menu.bulkUpload')</a>
                            @endif
                            <x-menu.copy-to-branches-button scope="menu_items" class="w-full !rounded-none !border-0 !shadow-none !justify-start" />
                        </x-slot>
                    </x-dropdown>

                    @if($canCreateDish)
                        <x-primary-link href="{{ route('menu-items.create') }}" wire:navigate class="shrink-0">
                            @lang('modules.menu.addMenuItem')
                        </x-primary-link>
                    @endif
                </div>
            </div>
        </div>
    </div>


    @if ($showFilters)
        @include('menu_items.filters')
    @endif

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.itemName')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.setPrice')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.itemCategory')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.menuName')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.isAvailable')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.showOnCustomerSite')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-end rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('app.action')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50 dark:bg-gray-800 dark:divide-gray-700" wire:key='menu-item-list'>

                            @forelse ($menuItems as $item)
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key='menu-item-{{ $item->id }}' wire:loading.class.delay='opacity-10'>
                                    <td class="lg:flex items-center p-4 mr-12 lg:space-x-6 rtl:space-x-reverse">
                                        <img class="w-12 h-12 rounded-md object-cover" src="{{ $item->item_photo_url }}" loading="lazy"
                                            alt="{{ $item->item_name }}">
                                        <div class="text-sm font-normal text-gray-500 dark:text-gray-400 w-40 lg:w-auto">
                                            <div class="text-sm  font-semibold text-gray-900 dark:text-white inline-flex items-center">
                                                <img src="{{ asset('img/'.$item->type.'.svg')}}" class="h-3 mr-2" title="@lang('modules.menu.' . $item->type)" alt="" />
                                                {{ $item->item_name }}

                                                @if (!$item->is_available)
                                                    <span class="text-xs font-medium ms-2 px-1.5 py-0.5 rounded-full bg-red-200 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                        @lang('app.inactive')
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400 line-clamp-2">{{
                                                $item->description }}</div>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $item->price ? currency_format($item->price, restaurant()->currency_id) : '--' }}
                                    </td>

                                    <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">{{
                                        $item->category->category_name }}</td>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">{{
                                        $item->menu->menu_name }}</td>
                                    <td class="py-2.5 px-4 text-center text-gray-900 whitespace-nowrap dark:text-white">
                                        <x-checkbox name="isRecommended" id="isRecommended" wire:click="toggleAvailability({{ $item->id }})" wire:key="itemAvailability-{{ $item->id }}" :checked="(bool) $item->is_available" />
                                    </td>
                                    <td class="py-2.5 px-4 text-center text-gray-900 whitespace-nowrap dark:text-white">
                                        <x-checkbox name="showOnCustomerSite" id="showOnCustomerSite" wire:click="toggleShowOnCustomerSite({{ $item->id }})" wire:key="itemCustomerSite-{{ $item->id }}" :checked="(bool) $item->show_on_customer_site" />
                                    </td>
                                    <td class="py-2.5 px-4 space-x-2 rtl:space-x-reverse whitespace-nowrap ltr:text-right rtl:text-left">
                                        @if ($item->variations_count > 0)
                                            <x-secondary-button-table wire:click='showItemVariations({{ $item->id }})'>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="w-4 h-4 ltr:mr-1 rtl:ml-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                                                @lang('modules.menu.showVariations')
                                            </x-secondary-button-table>
                                        @endif

                                        @if(user_can('Update Menu Item'))
                                            <x-secondary-link href="{{ route('menu-items.edit', $item->id) }}" wire:navigate >
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.414 2.586a2 2 0 0 0-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 0 0 0-2.828"/><path fill-rule="evenodd" d="M2 6a2 2 0 0 1 2-2h4a1 1 0 0 1 0 2H4v10h10v-4a1 1 0 1 1 2 0v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z" clip-rule="evenodd"/></svg>
                                                @lang('app.update')
                                            </x-secondary-link>
                                        @endif

                                        @if(user_can('Delete Menu Item'))
                                            <x-danger-button-table wire:click="showDeleteMenuItem({{ $item->id }})">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1" clip-rule="evenodd"/></svg>
                                            </x-danger-button-table>
                                        @endif

                                    </td>
                                </tr>
                            @empty
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="7">
                                        <svg class="mx-auto h-8 w-8" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7h12v2H6zm0 4h12v2H6zm12 4H6v2h12z" fill="currentColor"/></svg>
                                        <p class="mt-2 text-base font-medium">@lang('messages.noItemAdded')</p>
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <div wire:key='menu-item-paginate'
        class="sticky bottom-0 right-0 items-center w-full p-4 bg-white border-t border-gray-200 sm:flex sm:justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0 w-full">
            {{ $menuItems->links() }}
        </div>
    </div>


    <x-dialog-modal wire:model.live="showMenuCategoryModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.menu.itemCategory')
        </x-slot>

        <x-slot name="content">
            @livewire('forms.addItemCategory')
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="$toggle('showMenuCategoryModal')" wire:loading.attr="disabled" />
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="showItemVariationsModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.menu.itemVariations')
        </x-slot>

        <x-slot name="content">
            @if ($menuItem)
            @livewire('menu.itemVariations', ['menuItem' => $menuItem], key('item-variations-'.$menuItem->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="$toggle('showItemVariationsModal')" wire:loading.attr="disabled" />
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model.defer="confirmDeleteMenuItem">
        <x-slot name="title">
            @lang('modules.menu.deleteMenuItem')?
        </x-slot>

        <x-slot name="content">
            @lang('modules.menu.deleteMenuItemMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteMenuItem')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            @if ($menuItem)
            <x-danger-button class="ltr:ml-3 rtl:mr-3" wire:click='deleteMenuItem({{ $menuItem->id }})' wire:loading.attr="disabled" wire:key="delete-menu-item-{{ $menuItem->id }}">
                {{ __('Delete') }}
            </x-danger-button>
            @endif
        </x-slot>
    </x-confirmation-modal>

    <livewire:menu.copy-menu-to-branches />

</div>
