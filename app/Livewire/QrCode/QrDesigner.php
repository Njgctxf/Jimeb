<?php

namespace App\Livewire\QrCode;

use App\Helper\QrCodeGenerator;
use App\Support\QrRegenerator;
use App\Support\QrStyle;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Page de personnalisation du QR code : on règle le « QR général », l'aperçu se met à jour en direct,
 * puis le style est appliqué automatiquement à tous les QR codes du restaurant.
 */
class QrDesigner extends Component
{
    use LivewireAlert;
    use WithFileUploads;

    // Forme du code
    public string $silhouette = 'none';

    public int $ghost = 30;

    public string $badge = 'none';

    public string $badgeColor = '#000000';

    // Points
    public string $shape = 'square';

    public int $dotScale = 100;

    // Coins
    public string $eyeFrame = 'square';

    public string $eyePupil = 'square';

    public bool $customEyes = false;

    public string $eyeColor = '#000000';

    public string $pupilColor = '#000000';

    // Couleurs
    public string $color = '#000000';

    public bool $useGradient = false;

    public string $gradient = 'h';

    public string $color2 = '#1e3a8a';

    public string $background = '#ffffff';

    // Images (noms de fichiers dans public/user-uploads/qr-logos)
    public ?string $logo = null;

    public int $logoSize = 22;

    public ?string $art = null;

    public int $artStrength = 65;

    public ?string $silhouetteImage = null;

    public $logoTemp = null;

    public $artTemp = null;

    public $silhouetteTemp = null;

    // Texte
    public string $text = 'auto';

    public string $textCustom = '';

    public function mount(): void
    {
        $s = QrStyle::forRestaurant(restaurant()->id);

        $this->silhouette = $s['silhouette'];
        $this->ghost = $s['ghost'];
        $this->badge = $s['badge'];
        $this->badgeColor = $s['badgeColor'] ?? $s['color'];
        $this->shape = $s['shape'];
        $this->dotScale = $s['dotScale'];
        $this->eyeFrame = $s['eyeFrame'];
        $this->eyePupil = $s['eyePupil'];
        $this->customEyes = $s['eyeColor'] !== null || $s['pupilColor'] !== null;
        $this->eyeColor = $s['eyeColor'] ?? $s['color'];
        $this->pupilColor = $s['pupilColor'] ?? ($s['eyeColor'] ?? $s['color']);
        $this->color = $s['color'];
        $this->useGradient = $s['gradient'] !== 'none' && $s['color2'] !== null;
        $this->gradient = $s['gradient'] !== 'none' ? $s['gradient'] : 'h';
        $this->color2 = $s['color2'] ?? '#1e3a8a';
        $this->background = $s['background'];
        $this->logo = $s['logo'];
        $this->logoSize = $s['logoSize'];
        $this->art = $s['art'];
        $this->artStrength = $s['artStrength'];
        $this->silhouetteImage = $s['silhouetteImage'];
        $this->text = $s['text'];
        $this->textCustom = $s['textCustom'];
    }

    /** Style en cours d'édition. */
    private function currentStyle(): array
    {
        return QrStyle::normalize([
            'silhouette' => $this->silhouette,
            'silhouetteImage' => $this->silhouetteImage,
            'ghost' => $this->ghost,
            'badge' => $this->badge,
            'badgeColor' => $this->badge !== 'none' ? $this->badgeColor : null,
            'shape' => $this->shape,
            'dotScale' => $this->dotScale,
            'eyeFrame' => $this->eyeFrame,
            'eyePupil' => $this->eyePupil,
            'eyeColor' => $this->customEyes ? $this->eyeColor : null,
            'pupilColor' => $this->customEyes ? $this->pupilColor : null,
            'color' => $this->color,
            'gradient' => $this->useGradient ? $this->gradient : 'none',
            'color2' => $this->useGradient ? $this->color2 : null,
            'background' => $this->background,
            'logo' => $this->logo,
            'logoSize' => $this->logoSize,
            'art' => $this->art,
            'artStrength' => $this->artStrength,
            'text' => $this->text,
            'textCustom' => $this->textCustom,
        ]);
    }

    // ---------- Envoi des images ----------

    public function updatedLogoTemp(): void
    {
        $this->receiveImage('logoTemp', 'logo', 'logo', 600);
    }

    public function updatedArtTemp(): void
    {
        $this->receiveImage('artTemp', 'art', 'art', 900);
    }

    public function updatedSilhouetteTemp(): void
    {
        $this->receiveImage('silhouetteTemp', 'silhouetteImage', 'shape', 600);

        if ($this->silhouetteImage) {
            $this->silhouette = 'image';
        }
    }

    private function receiveImage(string $tempProp, string $target, string $kind, int $maxPx): void
    {
        $this->validate([$tempProp => 'nullable|image|mimes:png,jpg,jpeg,webp,gif|max:6144'], [
            $tempProp . '.image' => 'Le fichier doit être une image (PNG, JPG, WebP ou GIF).',
            $tempProp . '.mimes' => 'Formats acceptés : PNG, JPG, WebP, GIF.',
            $tempProp . '.max' => 'L\'image ne doit pas dépasser 6 Mo.',
        ]);

        $file = $this->storeImage($this->{$tempProp}, 'prev-' . restaurant()->id . '-' . $kind . '.png', $maxPx);
        $this->{$tempProp} = null;

        if ($file) {
            $this->{$target} = $file;
        }
    }

    /** Enregistre une image (réduite, transparence conservée) au format PNG. */
    private function storeImage($upload, string $filename, int $maxPx): ?string
    {
        if (! $upload) {
            return null;
        }

        $bytes = @file_get_contents($upload->getRealPath());
        $src = $bytes ? @imagecreatefromstring($bytes) : false;

        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $maxPx / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        $dir = public_path('user-uploads/qr-logos');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($dst, $dir . '/' . $filename);
        imagedestroy($src);
        imagedestroy($dst);

        return $filename;
    }

    public function removeImage(string $which): void
    {
        match ($which) {
            'logo' => $this->logo = null,
            'art' => $this->art = null,
            'silhouette' => $this->silhouetteImage = null,
            default => null,
        };

        if ($which === 'silhouette' && $this->silhouette === 'image') {
            $this->silhouette = 'none';
        }
    }

    // ---------- Enregistrement ----------

    public function save(): void
    {
        $style = $this->currentStyle();

        if ($problem = QrStyle::problem($style)) {
            $this->addError('color', $problem);

            return;
        }

        $restaurant = restaurant();
        $before = QrStyle::forRestaurant($restaurant->id);
        $stamp = time();

        // Les images d'aperçu (« prev-… ») deviennent des fichiers définitifs
        foreach (['logo' => 'logo', 'art' => 'art', 'silhouetteImage' => 'shape'] as $key => $kind) {
            if ($style[$key] && str_starts_with($style[$key], 'prev-')) {
                $final = $kind . '-' . $restaurant->id . '-' . $stamp . '.png';
                @rename(QrStyle::filePath($style[$key]), QrStyle::filePath($final));
                $style[$key] = $final;
            }
        }

        $style['version'] = $stamp;
        $restaurant->update(['qr_settings' => $style]);

        // Nettoyage des anciens fichiers qui ne servent plus
        foreach (['logo', 'art', 'silhouetteImage'] as $key) {
            if ($before[$key] && $before[$key] !== $style[$key]) {
                @unlink(QrStyle::filePath($before[$key]));
            }
        }
        foreach (['logo', 'art', 'shape'] as $kind) {
            @unlink(QrStyle::filePath('prev-' . $restaurant->id . '-' . $kind . '.png'));
        }

        $this->logo = $style['logo'];
        $this->art = $style['art'];
        $this->silhouetteImage = $style['silhouetteImage'];

        $count = QrRegenerator::all();

        $this->alert('success', 'Style enregistré et appliqué à ' . $count . ' QR code(s). Les QR déjà imprimés restent valables.', ['toast' => true, 'position' => 'top-end']);
    }

    public function resetToClassic(): void
    {
        $d = QrStyle::DEFAULTS;

        $this->silhouette = $d['silhouette'];
        $this->silhouetteImage = null;
        $this->ghost = $d['ghost'];
        $this->badge = $d['badge'];
        $this->badgeColor = $d['color'];
        $this->shape = $d['shape'];
        $this->dotScale = $d['dotScale'];
        $this->eyeFrame = $d['eyeFrame'];
        $this->eyePupil = $d['eyePupil'];
        $this->customEyes = false;
        $this->color = $d['color'];
        $this->useGradient = false;
        $this->background = $d['background'];
        $this->logo = null;
        $this->art = null;
        $this->logoSize = $d['logoSize'];
        $this->artStrength = $d['artStrength'];
        $this->text = $d['text'];
        $this->textCustom = '';
    }

    public function render()
    {
        $style = $this->currentStyle();
        $problem = QrStyle::problem($style);
        $this->resetErrorBag('color');

        $preview = null;

        if ($problem) {
            $this->addError('color', $problem);
        } else {
            try {
                $png = QrCodeGenerator::renderPng('https://exemple.com/menu/table-4', 'Table 4', $style);
                $preview = 'data:image/png;base64,' . base64_encode($png);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('livewire.qr-code.qr-designer', [
            'preview' => $preview,
            'warnings' => QrStyle::warnings($style),
            'qrCount' => \App\Models\Table::count() + 1,
        ]);
    }
}
