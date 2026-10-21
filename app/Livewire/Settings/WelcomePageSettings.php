<?php

namespace App\Livewire\Settings;

use App\Helper\Files;
use App\Models\Restaurant;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Personnalisation de la page d'accueil que voit le client après avoir scanné le QR code.
 */
class WelcomePageSettings extends Component
{
    use LivewireAlert, WithFileUploads;

    public Restaurant $settings;

    public $cover;
    public ?string $welcomeTitle = null;
    public ?string $welcomeSubtitle = null;
    public ?string $welcomeButtonText = null;
    public ?string $welcomeTableLabel = null;

    public function mount(Restaurant $settings): void
    {
        $this->settings = $settings;
        $this->welcomeTitle = $settings->welcome_title;
        $this->welcomeSubtitle = $settings->welcome_subtitle;
        $this->welcomeButtonText = $settings->welcome_button_text;
        $this->welcomeTableLabel = $settings->welcome_table_label;
    }

    public function updatedCover(): void
    {
        $this->validateOnly('cover', ['cover' => 'nullable|image|max:4096']);
    }

    public function removeCover(): void
    {
        if (!empty($this->settings->welcome_cover)) {
            Files::deleteFile(basename($this->settings->welcome_cover), 'welcome');
        }

        $this->settings->welcome_cover = null;
        $this->settings->save();
        $this->afterSave();
    }

    public function submitForm(): void
    {
        $this->validate([
            'cover' => 'nullable|image|max:4096',
            'welcomeTitle' => 'nullable|string|max:120',
            'welcomeSubtitle' => 'nullable|string|max:200',
            'welcomeButtonText' => 'nullable|string|max:40',
            'welcomeTableLabel' => 'nullable|string|max:30',
        ]);

        $this->settings->welcome_title = $this->clean($this->welcomeTitle);
        $this->settings->welcome_subtitle = $this->clean($this->welcomeSubtitle);
        $this->settings->welcome_button_text = $this->clean($this->welcomeButtonText);
        $this->settings->welcome_table_label = $this->clean($this->welcomeTableLabel);

        if ($this->cover) {
            $old = !empty($this->settings->welcome_cover) ? basename($this->settings->welcome_cover) : null;
            $name = Files::uploadLocalOrS3($this->cover, 'welcome', width: 1200, oldFilename: $old);
            $this->settings->welcome_cover = 'welcome/' . $name;
        }

        $this->settings->save();
        $this->cover = null;
        $this->afterSave();
    }

    private function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    private function afterSave(): void
    {
        session()->forget(['restaurant']);
        $this->dispatch('settingsUpdated');

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function getCoverUrlProperty(): ?string
    {
        return !empty($this->settings->welcome_cover) ? asset_url_local_s3($this->settings->welcome_cover) : null;
    }

    public function render()
    {
        return view('livewire.settings.welcome-page-settings');
    }
}
