<?php

namespace App\Livewire\Onboarding;

use App\Helper\Files;
use App\Livewire\Concerns\HasSideGroups;
use App\Models\ItemCategory;
use App\Models\MenuItem;
use App\Models\OnboardingStep;
use App\Support\DefaultMenu;
use App\Support\ItemSideGroups;
use App\Support\TableGenerator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Premier paramétrage en 3 écrans : nom + logo → tables → première catégorie + premier plat.
 * La 4e étape est le tableau de bord, avec sa liste « à faire ensuite ».
 */
class SetupWizard extends Component
{
    use WithFileUploads, HasSideGroups;

    public int $step = 1;

    // Étape 1
    public $restaurantName = '';
    public $logo;

    // Étape 2
    public $tableCount = '';
    public $qrMode = '';

    // Étape 3
    public $categoryName = '';
    public $dishName = '';
    public $dishPrice = '';
    public $dishDescription = '';
    public $dishImage;

    public function mount(): void
    {
        $this->restaurantName = restaurant()->name;

        // Reprise après rechargement : si les tables existent déjà, on passe directement au premier plat.
        if (\App\Models\Table::count() > 0) {
            $this->step = 3;

            if (restaurant()->qr_mode === null) {
                restaurant()->update(['qr_mode' => 'per_table']);
            }
        }

        $this->qrMode = (string) (restaurant()->qr_mode ?? '');
    }

    public function saveIdentity(): void
    {
        $this->validate([
            'restaurantName' => 'required|string|max:120',
            'logo' => 'nullable|image|max:2048',
        ], [
            'restaurantName.required' => 'Indiquez le nom de votre restaurant.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        $restaurant = restaurant();
        $restaurant->name = trim($this->restaurantName);

        if ($this->logo) {
            $restaurant->logo = Files::uploadLocalOrS3($this->logo, dir: 'logo', width: 150, height: 150);
        }

        $restaurant->save();
        $this->step = 2;
    }

    public function saveTables(): void
    {
        $this->validate([
            'qrMode' => 'required|in:per_table,single',
            'tableCount' => 'required|integer|min:1|max:200',
        ], [
            'qrMode.required' => 'Choisissez comment vos clients commandent.',
            'qrMode.in' => 'Choisissez comment vos clients commandent.',
            'tableCount.required' => 'Indiquez le nombre de tables.',
            'tableCount.integer' => 'Indiquez un nombre entier.',
            'tableCount.min' => 'Indiquez au moins 1 table.',
            'tableCount.max' => 'Maximum 200 tables.',
        ]);

        restaurant()->update(['qr_mode' => $this->qrMode]);

        TableGenerator::generate((int) $this->tableCount);
        $this->step = 3;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function finish()
    {
        $this->validate([
            'categoryName' => 'required|string|max:80',
            'dishName' => 'required|string|max:120',
            'dishPrice' => 'required|numeric|min:0',
            'dishImage' => 'nullable|image|max:4096',
        ], [
            'categoryName.required' => 'Indiquez une catégorie (ex. Plats).',
            'dishName.required' => 'Indiquez le nom du plat.',
            'dishPrice.required' => 'Indiquez le prix du plat.',
            'dishPrice.numeric' => 'Le prix doit être un nombre.',
            'dishImage.image' => 'La photo doit être une image.',
        ]);

        DB::transaction(function () {
            $name = trim($this->categoryName);
            $category = ItemCategory::all()->first(function (ItemCategory $c) use ($name) {
                return collect($c->getTranslations('category_name'))
                    ->contains(fn ($v) => mb_strtolower(trim((string) $v)) === mb_strtolower($name));
            });

            if (! $category) {
                $category = new ItemCategory();
                $category->setTranslations('category_name', array_fill_keys(['en', 'fr'], $name));
                $category->save();
            }

            $locale = global_setting()->locale ?: 'fr';
            $item = MenuItem::create([
                'item_name' => trim($this->dishName),
                'price' => (float) $this->dishPrice,
                'item_category_id' => $category->id,
                'description' => trim((string) $this->dishDescription) ?: null,
                'is_available' => true,
                'type' => 'other',
                'menu_id' => DefaultMenu::id(),
            ]);

            $item->translations()->create([
                'locale' => $locale,
                'item_name' => trim($this->dishName),
                'description' => trim((string) $this->dishDescription),
            ]);

            if ($this->dishImage) {
                $item->update(['image' => Files::uploadLocalOrS3($this->dishImage, 'item', width: 350)]);
            }

            ItemSideGroups::sync($item, $this->sideGroups);
        });

        cache()->forget('restaurant_' . restaurant()->id . '_menu_item_stats');

        OnboardingStep::updateOrCreate(['branch_id' => branch()->id], [
            'add_area_completed' => 1,
            'add_table_completed' => 1,
            'add_menu_completed' => 1,
            'add_menu_items_completed' => 1,
        ]);

        return $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.onboarding.setup-wizard', [
            'categories' => ItemCategory::all()->map(fn ($c) => $c->category_name)->filter()->values()->all(),
        ]);
    }
}
