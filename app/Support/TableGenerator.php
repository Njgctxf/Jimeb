<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Table;

/**
 * « Combien de tables avez-vous ? » : crée les tables manquantes (Table 1 … Table N) avec leur QR.
 * Les tables existantes ne sont jamais supprimées ni renumérotées.
 */
class TableGenerator
{
    /** @return int nombre de tables créées */
    public static function generate(int $count): int
    {
        $area = Area::first() ?: Area::create(['area_name' => 'Salle']);
        $existing = Table::pluck('table_code')->map(fn ($c) => mb_strtolower(trim($c)))->all();
        $created = 0;

        for ($i = 1; $i <= $count; $i++) {
            $code = 'Table ' . $i;

            if (in_array(mb_strtolower($code), $existing, true)) {
                continue;
            }

            $table = Table::create([
                'table_code' => $code,
                'area_id' => $area->id,
                'seating_capacity' => 4,
                'status' => 'active',
                'hash' => md5(microtime() . rand(1, 99999999)),
            ]);
            $table->generateQrCode();
            $created++;
        }

        return $created;
    }
}
