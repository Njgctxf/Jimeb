<?php

namespace App\Livewire\QrCode;

use App\Helper\Files;
use App\Models\Area;
use Livewire\Component;
use App\Models\Table;
use App\Models\FileStorage;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class QrCodes extends Component
{
    use LivewireAlert;

    public $tableCount = '';

    /** null = pas encore choisi ; « per_table » ou « single ». */
    public ?string $qrMode = null;

    public function setQrMode(string $mode): void
    {
        if (! in_array($mode, ['per_table', 'single'], true)) {
            return;
        }

        $restaurant = \App\Models\Restaurant::findOrFail(restaurant()->id);
        $restaurant->update(['qr_mode' => $mode]);
        session(['restaurant' => $restaurant]);
        $this->qrMode = $mode;

        // Le QR général existe dans les deux modes
        try {
            $branch = branch();
            if ($branch && ! FileStorage::where('filename', $branch->getQrCodeFileName())->exists()) {
                $branch->generateQrCode();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $this->alert('success', $mode === 'single'
            ? 'Mode « QR unique » activé : le client indiquera son numéro de table.'
            : 'Mode « un QR par table » activé.', ['toast' => true, 'position' => 'top-end']);
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
        $this->qrMode = \App\Models\Restaurant::find(restaurant()->id)?->qr_mode;

        // Aucun QR n'est généré tant que le restaurant n'a pas choisi son mode
        if ($this->qrMode === null) {
            return;
        }

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
            'qrMode' => $this->qrMode,
        ]);
    }
}
