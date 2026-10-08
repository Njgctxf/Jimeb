<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderMoneyRecalculator;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RecalculateOrderMoneyPrecision extends Command
{
    protected $signature = 'orders:recalculate-money
                            {orderNumber : Order number, e.g. 218}
                            {--branch= : Branch ID when the same number exists on more than one branch}
                            {--dry-run : Show before/after without writing}
                            {--sync-payment : Update payment and amount_paid to the new total}';

    protected $description = 'Rebuild an order\'s line prices from the current menu and recalculate tax/totals using the restaurant currency decimals.';

    public function handle(OrderMoneyRecalculator $recalculator): int
    {
        $orderNumber = (string) $this->argument('orderNumber');
        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;

        $matches = $recalculator->findOrders($orderNumber, null, $branchId);

        if ($matches->isEmpty()) {
            $this->error("Order #{$orderNumber} was not found.");

            return Command::FAILURE;
        }

        if ($matches->count() > 1 && !$branchId) {
            $this->error("Order #{$orderNumber} exists on multiple branches. Pass --branch=ID.");
            $matches->each(function (Order $order) {
                $this->line("  id={$order->id} branch_id={$order->branch_id} total={$order->total}");
            });

            return Command::FAILURE;
        }

        try {
            $preview = $recalculator->preview($matches->first());
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $decimals = (int) $preview['decimals'];
        $this->info("Restaurant: {$preview['restaurant_name']}  Currency decimals: {$decimals}");

        $this->table(
            ['Item', 'Old price', 'New price', 'Old amount', 'New amount'],
            collect($preview['lines'])->map(fn ($row) => [
                $row['name'],
                number_format($row['before_price'], $decimals),
                number_format($row['new_price'], $decimals),
                number_format($row['before_amount'], $decimals),
                number_format($row['new_amount'], $decimals),
            ])->all()
        );

        $syncPayment = (bool) $this->option('sync-payment');
        $newAmountPaid = $syncPayment && ($preview['can_sync_payment'] ?? false)
            ? $preview['totals']['total']['new']
            : $preview['totals']['amount_paid']['old'];

        $this->table(
            ['Field', 'Old', 'New'],
            [
                ['sub_total', number_format($preview['totals']['sub_total']['old'], $decimals), number_format($preview['totals']['sub_total']['new'], $decimals)],
                ['total_tax_amount', number_format($preview['totals']['total_tax_amount']['old'], $decimals), number_format($preview['totals']['total_tax_amount']['new'], $decimals)],
                ['total', number_format($preview['totals']['total']['old'], $decimals), number_format($preview['totals']['total']['new'], $decimals)],
                ['amount_paid', number_format($preview['totals']['amount_paid']['old'], $decimals), number_format($newAmountPaid, $decimals)],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. Nothing was saved.');

            return Command::SUCCESS;
        }

        $recalculator->apply($matches->first(), $syncPayment);

        $this->info("Order #{$orderNumber} (id {$preview['order_id']}) was recalculated.");
        if ($syncPayment && !($preview['can_sync_payment'] ?? false)) {
            $this->warn('This order has more than one payment, so payment rows were left unchanged.');
        } elseif (!$syncPayment && (float) $preview['totals']['amount_paid']['old'] > 0) {
            $this->warn('Payments were left unchanged. Re-run with --sync-payment if the receipt should show the new total as paid.');
        }

        return Command::SUCCESS;
    }
}
