<?php

namespace App\Livewire\Settings;

use App\Helper\Files;
use App\Models\ItemCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantBanner;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gestion des bannières de la page d'accueil client (images, textes, destination du bouton).
 */
class BannerSettings extends Component
{
    use LivewireAlert, WithFileUploads;

    public Restaurant $settings;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, mixed> */
    public array $uploads = [];

    public function mount(Restaurant $settings): void
    {
        $this->settings = $settings;
        $this->load();
    }

    private function load(): void
    {
        $this->rows = RestaurantBanner::forRestaurant($this->settings->id)
            ->orderBy('position')->orderBy('sort_order')->orderBy('id')->get()
            ->mapWithKeys(fn ($b) => [$b->id => [
                'position' => $b->position,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
                'button_text' => $b->button_text,
                'target_type' => $b->target_type,
                'target_id' => $b->target_id,
                'is_active' => (bool) $b->is_active,
                'image' => $b->image,
            ]])->all();
    }

    private function count(string $position): int
    {
        return RestaurantBanner::forRestaurant($this->settings->id)->where('position', $position)->count();
    }

    public function addBanner(string $position): void
    {
        $position = $position === RestaurantBanner::POSITION_MIDDLE ? RestaurantBanner::POSITION_MIDDLE : RestaurantBanner::POSITION_HERO;
        $max = $position === RestaurantBanner::POSITION_HERO ? RestaurantBanner::MAX_HERO : RestaurantBanner::MAX_MIDDLE;

        if ($this->count($position) >= $max) {
            $this->alert('warning', __('modules.settings.bannerMax', ['n' => $max]), ['toast' => true, 'position' => 'top-end']);

            return;
        }

        RestaurantBanner::create([
            'restaurant_id' => $this->settings->id,
            'position' => $position,
            'target_type' => 'menu',
            'sort_order' => (int) RestaurantBanner::forRestaurant($this->settings->id)->where('position', $position)->max('sort_order') + 1,
        ]);

        $this->load();
    }

    public function deleteBanner(int $id): void
    {
        $b = RestaurantBanner::forRestaurant($this->settings->id)->find($id);
        if (!$b) {
            return;
        }
        if ($b->image) {
            Files::deleteFile(basename($b->image), 'banners');
        }
        $b->delete();
        unset($this->uploads[$id]);
        $this->load();
    }

    public function removeImage(int $id): void
    {
        $b = RestaurantBanner::forRestaurant($this->settings->id)->find($id);
        if ($b && $b->image) {
            Files::deleteFile(basename($b->image), 'banners');
            $b->update(['image' => null]);
        }
        $this->load();
    }

    public function move(int $id, string $dir): void
    {
        $b = RestaurantBanner::forRestaurant($this->settings->id)->find($id);
        if (!$b) {
            return;
        }

        $list = RestaurantBanner::forRestaurant($this->settings->id)->where('position', $b->position)
            ->orderBy('sort_order')->orderBy('id')->get()->values();
        $i = $list->search(fn ($x) => $x->id === $b->id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;

        if ($i === false || $j < 0 || $j >= $list->count()) {
            return;
        }

        $list = $list->all();
        [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        foreach ($list as $n => $item) {
            $item->update(['sort_order' => $n]);
        }
        $this->load();
    }

    public function save(): void
    {
        $this->validate([
            'rows.*.title' => 'nullable|string|max:90',
            'rows.*.subtitle' => 'nullable|string|max:160',
            'rows.*.button_text' => 'nullable|string|max:30',
            'rows.*.target_type' => 'required|in:' . implode(',', RestaurantBanner::TARGETS),
            'rows.*.target_id' => 'nullable|integer',
            'uploads.*' => 'nullable|image|max:4096',
        ]);

        foreach ($this->rows as $id => $row) {
            $b = RestaurantBanner::forRestaurant($this->settings->id)->find($id);
            if (!$b) {
                continue;
            }

            $type = $row['target_type'];
            $data = [
                'title' => $this->clean($row['title'] ?? null),
                'subtitle' => $this->clean($row['subtitle'] ?? null),
                'button_text' => $this->clean($row['button_text'] ?? null),
                'target_type' => $type,
                'target_id' => in_array($type, ['dish', 'category'], true) ? ($row['target_id'] ?: null) : null,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];

            if (!empty($this->uploads[$id])) {
                $old = $b->image ? basename($b->image) : null;
                $name = Files::uploadLocalOrS3($this->uploads[$id], 'banners', width: 1400, oldFilename: $old);
                $data['image'] = 'banners/' . $name;
            }

            $b->update($data);
        }

        $this->uploads = [];
        $this->load();

        session()->forget(['restaurant']);
        $this->alert('success', __('messages.settingsUpdated'), ['toast' => true, 'position' => 'top-end']);
    }

    private function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    public function render()
    {
        return view('livewire.settings.banner-settings', [
            'dishes' => MenuItem::query()->orderBy('id')->get()->map(fn ($i) => ['id' => $i->id, 'name' => $i->item_name])->all(),
            'categories' => ItemCategory::query()->orderBy('id')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->category_name])->all(),
        ]);
    }
}
