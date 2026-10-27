<?php

namespace App\Livewire\QrCode;

use App\Helper\Files;
use App\Models\Area;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Helper\QrCodeGenerator;
use App\Models\Branch;
use App\Support\QrStyle;
use App\Models\Table;
use App\Models\FileStorage;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class QrCodes extends Component
{
    use LivewireAlert;
    use WithFileUploads;

    public $tableCount = '';

    // ----- Personnalisation des QR codes -----
    public string $qrShape = 'square';

    public string $qrEye = 'square';

    public string $qrColor = '#000000';

    public string $qrBackground = '#ffffff';

    /** Logo enregistré (nom de fichier) et logo en cours de choix. */
    public ?string $qrLogo = null;

    public $qrLogoTemp = null;

    public string $qrPreview = '';

    private function loadQrStyle(): void
    {
        $style = QrStyle::forRestaurant(restaurant());
        $this->qrShape = $style['shape'];
        $this->qrEye = $style['eye'];
        $this->qrColor = $style['color'];
        $this->qrBackground = $style['background'];
        $this->qrLogo = $style['logo'];
    }

    public function updated($name): void
    {
        if (! str_starts_with($name, 'qr') || $name === 'qrPreview') {
            return;
        }

        if ($name === 'qrLogoTemp') {
            $this->validate(['qrLogoTemp' => 'nullable|image|mimes:png,jpg,jpeg,webp,gif|max:4096'], [
                'qrLogoTemp.image' => 'Le logo doit être une image (PNG, JPG, WebP ou GIF).',
                'qrLogoTemp.mimes' => 'Formats acceptés : PNG, JPG, WebP, GIF.',
                'qrLogoTemp.max' => 'Le logo ne doit pas dépasser 4 Mo.',
            ]);
        }

        $this->refreshQrPreview();
    }

    /** Style en cours d'édition (avec le logo choisi, enregistré ou en attente). */
    private function currentStyle(?string $logoFile): array
    {
        return QrStyle::normalize([
            'shape' => $this->qrShape,
            'eye' => $this->qrEye,
            'color' => $this->qrColor,
            'background' => $this->qrBackground,
            'logo' => $logoFile,
        ]);
    }

    public function refreshQrPreview(): void
    {
        $this->resetErrorBag('qrColor');

        if ($problem = QrStyle::colorProblem(QrStyle::hex($this->qrColor) ?? '#000000', QrStyle::hex($this->qrBackground) ?? '#ffffff')) {
            $this->addError('qrColor', $problem);

            return;
        }

        $logo = $this->qrLogo;

        if ($this->qrLogoTemp) {
            $logo = $this->storeLogoFile($this->qrLogoTemp, 'preview-' . restaurant()->id . '.png');
        }

        $png = QrCodeGenerator::renderPng(
            'https://exemple.com/menu/table-4',
            'Table 4',
            $this->currentStyle($logo)
        );

        $this->qrPreview = 'data:image/png;base64,' . base64_encode($png);
    }

    /** Enregistre un logo (réduit à 400 px max, transparence conservée) dans un fichier PNG. */
    private function storeLogoFile($upload, string $filename): ?string
    {
        $bytes = @file_get_contents($upload->getRealPath());
        $src = $bytes ? @imagecreatefromstring($bytes) : false;

        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, 400 / max($w, $h));
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

    public function removeQrLogo(): void
    {
        $this->qrLogo = null;
        $this->qrLogoTemp = null;
        $this->refreshQrPreview();
    }

    public function saveQrStyle(): void
    {
        $restaurant = restaurant();

        if ($problem = QrStyle::colorProblem(QrStyle::hex($this->qrColor) ?? '#000000', QrStyle::hex($this->qrBackground) ?? '#ffffff')) {
            $this->addError('qrColor', $problem);

            return;
        }

        $logo = $this->qrLogo;
        $oldLogo = QrStyle::forRestaurant($restaurant)['logo'];

        if ($this->qrLogoTemp) {
            $logo = $this->storeLogoFile($this->qrLogoTemp, 'logo-' . $restaurant->id . '-' . time() . '.png');
            @unlink(QrStyle::logoPath('preview-' . $restaurant->id . '.png'));
        }

        $style = $this->currentStyle($logo);
        $style['version'] = time();

        $restaurant->update(['qr_settings' => $style]);

        if ($oldLogo && $oldLogo !== $style['logo']) {
            @unlink(QrStyle::logoPath($oldLogo));
        }

        $this->qrLogo = $style['logo'];
        $this->qrLogoTemp = null;

        $count = $this->regenerateAllQrImages();

        $this->alert('success', 'Style enregistré : ' . $count . ' QR code(s) mis à jour. Les QR déjà imprimés restent valables.', ['toast' => true, 'position' => 'top-end']);
    }

    public function resetQrStyle(): void
    {
        $this->qrShape = QrStyle::DEFAULTS['shape'];
        $this->qrEye = QrStyle::DEFAULTS['eye'];
        $this->qrColor = QrStyle::DEFAULTS['color'];
        $this->qrBackground = QrStyle::DEFAULTS['background'];
        $this->qrLogo = null;
        $this->qrLogoTemp = null;

        $this->saveQrStyle();
        $this->refreshQrPreview();
    }

    /**
     * Redessine toutes les images de QR code avec le nouveau style.
     * Les liens ne changent pas : les QR déjà imprimés continuent de fonctionner.
     */
    private function regenerateAllQrImages(): int
    {
        @set_time_limit(300);
        $count = 0;

        foreach (Table::all() as $table) {
            $table->createQrCode(
                route('table_order', [$table->hash]),
                trim(__('modules.table.table') . ' ' . $table->table_code)
            );
            $table->touch();
            $count++;
        }

        foreach (Branch::all() as $branch) {
            $branch->createQrCode(
                route('table_order', [$branch->restaurant_id]) . '?branch=' . $branch->unique_hash . '&hash=' . $branch->restaurant->hash . '&from_qr=1'
            );
            $count++;
        }

        return $count;
    }

    /**
     * « Combien de tables avez-vous ? » : crée les tables manquantes (Table 1 … Table N) avec leur QR.
     * Les tables existantes ne sont jamais supprimées ni renumérotées.
     */
    public function createTables(): void
    {
        $this->validate(['tableCount' => 'required|integer|min:1|max:200'], [
            'tableCount.required' => 'Indiquez le nombre de tables.',
            'tableCount.integer' => 'Indiquez un nombre entier.',
            'tableCount.min' => 'Indiquez au moins 1 table.',
            'tableCount.max' => 'Maximum 200 tables à la fois.',
        ]);

        $created = \App\Support\TableGenerator::generate((int) $this->tableCount);

        $this->tableCount = '';
        $this->alert('success', $created . ' table(s) créée(s) avec leur QR code.', ['toast' => true, 'position' => 'top-end']);
    }

    public function downloadQrCode($tableCode, $branchId)
    {
        $filename = 'qrcode-' . $branchId . '-' . str()->slug($tableCode, '-', (auth()->user() ? auth()->user()->locale : 'en')) . '.png';

        $file = FileStorage::where('filename', $filename)->first();

        return download_local_s3($file, 'qrcodes/' . $filename);
    }

    public function downloadBranchQrCode()
    {
        $branch = branch();

        $filename = 'qrcode-branch-' . $branch->id . '-' . $branch->restaurant->id . '.png';

        $file = FileStorage::where('filename', $filename)->first();

        return download_local_s3($file, 'qrcodes/' . $filename);
    }

    public function generateQrCode($tableId = null)
    {
        if ($tableId) {
            $table = Table::find($tableId);
        } else {
            $table = branch();
        }

        $table->generateQrCode();

        $this->redirect(route('qrcodes.index'));
    }

    /**
     * Flat list of QR entries for Alpine (selection, modal, bulk print).
     *
     * @param  \Illuminate\Support\Collection<int, Area>  $areasWithTables
     * @return list<array<string, mixed>>
     */
    private function buildPrintableQrItems($areasWithTables): array
    {
        $items = [];
        $restaurant = restaurant();
        $branch = branch();

        if ($branch->qRCodeUrl) {
            $items[] = [
                'id' => 'branch-' . $branch->id,
                'kind' => 'branch',
                'label' => $branch->name ?: __('menu.qrCodes'),
                'subtitle' => __('modules.table.branchMenuQr'),
                'image_url' => $branch->qRCodeUrl,
                'visit_url' => route('table_order', [$restaurant->id])
                    . '?branch=' . $branch->unique_hash
                    . '&hash=' . $restaurant->hash
                    . '&from_qr=1',
                'table_id' => null,
                'table_code' => null,
                'branch_id' => $branch->id,
            ];
        }

        foreach ($areasWithTables as $area) {
            foreach ($area->tables as $table) {
                if (empty($table->qRCodeUrl)) {
                    continue;
                }

                $items[] = [
                    'id' => 'table-' . $table->id,
                    'kind' => 'table',
                    'label' => $table->table_code,
                    'subtitle' => $area->area_name,
                    'area_id' => $area->id,
                    'image_url' => $table->qRCodeUrl,
                    'visit_url' => route('table_order', [$table->hash]) . '?hash=' . $restaurant->hash,
                    'table_id' => $table->id,
                    'table_code' => $table->table_code,
                    'branch_id' => $table->branch_id,
                    'available_status' => $table->available_status,
                    'status' => $table->status,
                    'seating_capacity' => $table->seating_capacity,
                ];
            }
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function tableQrs($areasWithTables): array
    {
        return collect($areasWithTables)->flatMap(fn ($area) => $area->tables)
            ->filter(fn ($t) => ! empty($t->qRCodeUrl))
            ->sortBy(fn ($t) => (int) preg_replace('/\D+/', '', $t->table_code) ?: PHP_INT_MAX)
            ->map(fn ($t) => [
                'table_id' => $t->id,
                'table_code' => $t->table_code,
                'label' => $t->table_code,
                'image_url' => $t->qRCodeUrl,
                'branch_id' => $t->branch_id,
                'version' => optional($t->updated_at)->timestamp ?? 0,
            ])->values()->all();
    }

    public function mount(): void
    {
        $this->loadQrStyle();
        $this->refreshQrPreview();

        // Génère automatiquement le QR code du restaurant s'il n'existe pas encore
        try {
            $branch = branch();
            if ($branch && !FileStorage::where('filename', $branch->getQrCodeFileName())->exists()) {
                $branch->generateQrCode();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        $areasWithTables = Area::with('tables')->get();
        $branch = branch();
        $restaurant = restaurant();

        return view('livewire.qr-code.qr-codes', [
            'branchQrUrl' => $branch->qRCodeUrl,
            'branchVisitUrl' => route('table_order', [$restaurant->id])
                . '?branch=' . $branch->unique_hash
                . '&hash=' . $restaurant->hash
                . '&from_qr=1',
            'tables' => $areasWithTables,
            'areas' => $areasWithTables,
            'printableQrItems' => [],
            'tableQrs' => $this->tableQrs($areasWithTables),
            'restaurantName' => restaurant()->name,
        ]);
    }
}
