<?php

namespace App\Support;

use App\Models\Table;

/**
 * Retrouve une table à partir de ce que le client a tapé (mode « QR unique »).
 * « 5 », « t5 », « T-5 » ou « Table 5 » désignent la même table.
 */
final class TableLookup
{
    public static function find(int $branchId, string $input): ?Table
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        $tables = Table::withoutGlobalScopes()->where('branch_id', $branchId)->orderBy('id')->get();

        $squash = static fn (string $v): string => preg_replace('/[^a-z0-9]+/u', '', mb_strtolower($v));

        // 1) Nom identique (sans tenir compte des espaces, tirets et majuscules)
        $wanted = $squash($input);
        foreach ($tables as $table) {
            if ($squash((string) $table->table_code) === $wanted) {
                return $table;
            }
        }

        // 2) Même numéro : « 5 » trouve « Table 5 » ou « T-5 »
        if (preg_match('/^\D*(\d+)\D*$/u', $input, $m)) {
            $number = (int) $m[1];
            $matches = $tables->filter(function (Table $t) use ($number) {
                return preg_match('/(\d+)\D*$/u', (string) $t->table_code, $c) && (int) $c[1] === $number;
            });

            if ($matches->isNotEmpty()) {
                // « Table N » est préféré à un autre format s'il y en a plusieurs
                return $matches->first(fn (Table $t) => str_starts_with(mb_strtolower((string) $t->table_code), 'table')) ?? $matches->first();
            }
        }

        return null;
    }
}
