<?php

namespace App\Livewire\Settings;

use App\Models\Restaurant;
use Livewire\Attributes\On;
use Livewire\Component;

class Master extends Component
{

    public $settings;
    public $activeSetting;

    public function mount()
    {
        $this->settings = Restaurant::find(restaurant()->id);
        // Sans onglet : la vue d'ensemble en cartes
        $this->activeSetting = request('tab') != '' ? request('tab') : 'home';
    }

    /**
     * Jimbe : les réglages rangés par groupes, dans l'ordre où le restaurateur en a besoin.
     * Chaque entrée : [onglet, libellé] ; on ne garde que ce que l'utilisateur a le droit de voir,
     * et un groupe vide disparaît. « advanced » est replié par défaut.
     */
    public function sections(): array
    {
        $isAdmin = user()->hasRole('Admin_' . user()->restaurant_id);
        $openClose = $isAdmin || user_can('Show Restaurant Open/Close');

        $groups = [
            'restaurant' => [
                ['restaurant', __('modules.settings.restaurantSettings'), $isAdmin],
                ['branch', __('modules.settings.branchSettings'), $isAdmin],
                ['operationalShifts', __('modules.settings.operationalShifts'), $isAdmin],
                ['restaurantOpenClose', __('modules.settings.restaurantOpenCloseSettings'), $openClose],
                ['currency', __('modules.settings.currencySettings'), $isAdmin],
                ['tax', __('modules.settings.taxSettings'), $isAdmin],
            ],
            'orders' => [
                ['orderSettings', __('modules.settings.orderSettings'), true],
                ['kotSettings', __('modules.settings.kotSettings'), true],
                ['reservation', __('modules.settings.reservationSettings'), true],
                ['deliverySettings', __('modules.settings.deliverySettings'), true],
            ],
            'payment' => [
                ['payment', __('modules.settings.paymentgatewaySettings'), $isAdmin],
                ['receipt', __('modules.settings.receiptSetting'), true],
                ['printer', __('modules.settings.printerSetting'), true],
                ['customerDisplay', __('modules.settings.customerDisplaySettings'), true],
            ],
            'site' => [
                ['customerSite', __('modules.settings.customerSiteSettings'), true],
                ['welcome', __('modules.settings.welcomePage'), true],
                ['banners', __('modules.settings.banners'), true],
                ['menuItemImageSettings', __('modules.menu.menuItemImageSettings'), true],
                ['aboutus', __('modules.settings.aboutUsSettings'), true],
            ],
            'team' => [
                ['role', __('modules.settings.roleSettings'), $isAdmin],
            ],
            'account' => [
                ['billing', __('modules.settings.billing'), $isAdmin],
                ['email', __('modules.settings.emailSettings'), $isAdmin],
                ['theme', __('modules.settings.themeSettings'), $isAdmin],
                ['app', __('modules.settings.appSettings'), $isAdmin],
            ],
            'advanced' => [
                ['euAllergens', __('modules.settings.euAllergensFicTitle'), true],
                ['downloads', __('modules.settings.downloads'), true],
                ['cancelSettings', __('modules.settings.cancelSettings'), true],
                ['refundReasons', __('modules.settings.refundReasons'), true],
            ],
        ];

        return collect($groups)
            ->map(fn ($items) => collect($items)->filter(fn ($i) => $i[2])->map(fn ($i) => [$i[0], $i[1]])->values()->all())
            ->filter()
            ->all();
    }

    #[On('settingsUpdated')]
    public function refreshSettings()
    {
        session()->forget(['restaurant', 'timezone', 'currency']);

        $this->settings->fresh();
    }

    public function render()
    {
        return view('livewire.settings.master', [
            'sections' => $this->sections(),
        ]);
    }

}
