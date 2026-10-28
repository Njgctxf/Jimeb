<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\ManagesLiveOrders;
use App\Models\Order;
use App\Models\WaiterRequest;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tableau « Commandes en direct » : Nouvelles → En cuisine → Prêtes → À encaisser.
 * Gros bouton pour l'étape suivante, menu déroulant pour choisir un autre état, carte → page de détail.
 */
class TodayOrderList extends Component
{
    use ManagesLiveOrders;

    protected $listeners = ['refreshOrders' => '$refresh'];

    public const COLUMNS = ['new', 'kitchen', 'ready', 'bill'];

    /** Une page par étape : adresse (?etape=…) → colonne affichée. */
    public const STAGES = ['nouvelles' => 'new', 'cuisine' => 'kitchen', 'pretes' => 'ready', 'encaisser' => 'bill'];

    #[Url(as: 'etape', except: 'nouvelles')]
    public string $etape = 'nouvelles';

    /** « Mes commandes » : seulement celles du serveur connecté (et celles que personne n'a prises). */
    public bool $mine = false;

    /** Table dont on encaisse l'addition entière (fenêtre simple). */
    public ?int $collectTableId = null;

    public function mount(): void
    {
        $this->mine = (bool) session('jb_board_mine', false);
    }

    /** Onglet d'étape : on change de page sans recharger (l'adresse suit grâce à #[Url]). */
    public function goToStage(string $etape): void
    {
        if (array_key_exists($etape, self::STAGES)) {
            $this->etape = $etape;
        }
    }

    public function updatedMine($value): void
    {
        session(['jb_board_mine' => (bool) $value]);
    }

    /** Appel de table traité : on ferme toutes les demandes en attente de cette table. */
    public function closeCall(int $requestId): void
    {
        $request = WaiterRequest::where('id', $requestId)->where('branch_id', branch()->id)->first();

        if ($request) {
            WaiterRequest::where('table_id', $request->table_id)->where('branch_id', branch()->id)
                ->whereIn('status', ['pending', 'Pending'])
                ->update(['status' => 'completed']);
        }
    }

    /** Rush : toutes les nouvelles commandes visibles partent en cuisine d'un coup. */
    public function sendAllToKitchen(): void
    {
        foreach ($this->todayOrders() as $order) {
            if (self::columnOf($order) === 'new') {
                $this->setStatus($order->id, 'preparing');
            }
        }
    }

    /**
     * Addition d'une table : ses commandes servies et pas encore payées,
     * plus le nombre de commandes encore en cours (pas servies) pour le garde-fou.
     */
    private function tableBill(int $tableId): array
    {
        $open = $this->todayOrders(all: true)->where('table_id', $tableId)->where('status', '!=', 'paid');
        $served = $open->filter(fn ($o) => self::columnOf($o) === 'bill')->values();

        return [$served, $open->count() - $served->count()];
    }

    public function collectTable(int $tableId): void
    {
        $this->collectOrderId = null;
        $this->collectTableId = $tableId;
    }

    public function closeTableCollect(): void
    {
        $this->collectTableId = null;
    }

    /** Encaisse d'un clic tout ce qui a été servi à la table ; ce qui n'est pas encore servi reste à part. */
    public function quickPayTable(string $method): void
    {
        $paymentMethod = Order::PAY_METHODS[$method][2] ?? null;

        if ($this->collectTableId && $paymentMethod) {
            [$served] = $this->tableBill($this->collectTableId);

            foreach ($served as $order) {
                $this->payInFull($order, $paymentMethod);
            }

            if ($served->isNotEmpty()) {
                $this->dispatch('jb-paid');
            }
        }

        $this->collectTableId = null;
    }

    /** Envoie (ou relance) l'addition de toutes les commandes servies de la table. */
    public function sendTableBill(int $tableId): void
    {
        [$served] = $this->tableBill($tableId);

        foreach ($served as $order) {
            $this->sendBill($order->id);
        }
    }

    /** Commandes du jour (les plus anciennes d'abord : ce sont celles qui attendent le plus). */
    private function todayOrders(bool $all = false)
    {
        $boundaries = getBusinessDayBoundaries(branch(), now());
        $startUTC = $boundaries['start']->setTimezone('UTC')->toDateTimeString();
        $endUTC = $boundaries['end']->setTimezone('UTC')->toDateTimeString();

        return Order::withCount('items')
            ->with('table.area', 'waiter', 'customer', 'items.menuItem', 'items.menuItemVariation')
            ->whereNotIn('status', ['canceled', 'draft'])
            ->where('orders.date_time', '>=', $startUTC)
            ->where('orders.date_time', '<=', $endUTC)
            ->when($this->mine && !$all, fn ($q) => $q->where(fn ($q) => $q->whereNull('waiter_id')->orWhere('waiter_id', user()->id)))
            ->orderBy('date_time')
            ->get();
    }

    public function render()
    {
        $orders = $this->todayOrders();

        $columns = collect(self::COLUMNS)->mapWithKeys(fn ($c) => [$c => collect()])->all();
        $finished = 0;

        foreach ($orders as $order) {
            $column = self::columnOf($order);
            $column ? $columns[$column]->push($order) : $finished++;
        }

        // Session de table : toutes les commandes non payées d'une table forment son addition en cours.
        // La cuisine voit chaque commande à part (« commande 2 sur 3 »), la caisse voit la table.
        $allOrders = $this->mine ? $this->todayOrders(all: true) : $orders;
        $sessionPos = [];
        $pendingByTable = [];
        $billByTable = [];

        foreach ($allOrders->where('status', '!=', 'paid')->whereNotNull('table_id')->groupBy('table_id') as $tableId => $tableOrders) {
            foreach ($tableOrders->values() as $i => $o) {
                $sessionPos[$o->id] = [$i + 1, $tableOrders->count()];
            }

            $served = $tableOrders->filter(fn ($o) => self::columnOf($o) === 'bill')->values();
            $pendingByTable[$tableId] = $tableOrders->count() - $served->count();

            // Une seule carte d'addition dès que la table a au moins deux commandes servies
            if ($served->count() > 1 && $columns['bill']->contains('table_id', $tableId)) {
                $billByTable[$tableId] = $served;
            }
        }

        $columns['bill'] = $columns['bill']->reject(fn ($o) => isset($billByTable[$o->table_id]))->values();

        $collectTable = null;

        if ($this->collectTableId) {
            [$served, $pending] = $this->tableBill($this->collectTableId);
            $collectTable = $served->isEmpty() ? null : [
                'table' => $served->first()->table,
                'orders' => $served,
                'pending' => $pending,
                'total' => $served->sum(fn ($o) => $o->remainingAmount()),
            ];
        }

        $calls = WaiterRequest::with('table')
            ->where('branch_id', branch()->id)
            ->whereIn('status', ['pending', 'Pending'])
            ->orderBy('id')
            ->get();

        return view('livewire.dashboard.today-order-list', [
            'collectOrder' => $this->collectOrderId ? Order::with('table')->find($this->collectOrderId) : null,
            'stage' => self::STAGES[$this->etape] ?? 'new',
            'collectTable' => $collectTable,
            'columns' => $columns,
            'billByTable' => $billByTable,
            'sessionPos' => $sessionPos,
            'pendingByTable' => $pendingByTable,
            'finished' => $finished,
            'calls' => $calls,
        ]);
    }
}
