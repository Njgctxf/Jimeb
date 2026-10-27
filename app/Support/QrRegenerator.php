<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Table;

/**
 * Redessine toutes les images de QR code du restaurant courant avec son style actuel.
 * Les liens ne changent pas : les QR déjà imprimés continuent de fonctionner.
 */
final class QrRegenerator
{
    public static function all(): int
    {
        @set_time_limit(600);
        $count = 0;

        foreach (Table::all() as $table) {
            $table->createQrCode(route('table_order', [$table->hash]), $table->qrLabel());
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
}
