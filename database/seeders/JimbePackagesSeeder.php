<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Les 3 formules de Jimbe (prix mensuels en F CFA) et l'essai de 7 jours.
 *
 *   php artisan db:seed --class=JimbePackagesSeeder
 *
 * Idempotent : peut être relancé sans créer de doublons. Le compte du propriétaire compte dans « comptes équipe ».
 * Les forfaits d'origine de Jimeb (Subscription Package, Life Time) sont masqués, pas supprimés.
 */
class JimbePackagesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // FCFA (XOF) : devise globale
        $currencyId = DB::table('global_currencies')->where('currency_code', 'XOF')->value('id') ?? 1;

        // Modules : on reprend ceux du forfait d'essai d'origine
        $trial = DB::table('packages')->where('package_type', 'trial')->first();
        $moduleIds = $trial
            ? DB::table('package_modules')->where('package_id', $trial->id)->pluck('module_id')->all()
            : [];

        $plans = [
            [
                'package_name' => 'Essentiel',
                'description' => 'Menu QR et commandes à table pour démarrer.',
                'monthly_price' => 25000,
                'staff_limit' => 2,
                'branch_limit' => 1,
                'sort_order' => 10,
                'is_recommended' => 0,
                'additional_features' => [],
            ],
            [
                'package_name' => 'Prestige',
                'description' => 'Équipe, promotions et statistiques de ventes.',
                'monthly_price' => 40000,
                'staff_limit' => 5,
                'branch_limit' => 1,
                'sort_order' => 20,
                'is_recommended' => 1,
                'additional_features' => ['Export Report'],
            ],
            [
                'package_name' => 'Signature',
                'description' => 'Plusieurs adresses, site web du restaurant et marketplace.',
                'monthly_price' => 90000,
                'staff_limit' => -1,
                'branch_limit' => -1,
                'sort_order' => 30,
                'is_recommended' => 0,
                'additional_features' => ['Change Branch', 'Export Report'],
            ],
        ];

        foreach ($plans as $plan) {
            $features = $plan['additional_features'];
            unset($plan['additional_features']);

            $values = array_merge($plan, [
                'price' => 0,
                'currency_id' => $currencyId,
                'annual_price' => null,
                'monthly_status' => '1',
                'annual_status' => '0',
                'billing_cycle' => 12,
                'is_private' => 0,
                'is_free' => 0,
                'package_type' => 'standard',
                'trial_status' => null,
                'trial_days' => null,
                'menu_items_limit' => -1,
                'order_limit' => -1,
                'multipos_limit' => -1,
                'ai_monthly_token_limit' => -1,
                'additional_features' => json_encode($features),
                'updated_at' => $now,
            ]);

            $existing = DB::table('packages')->where('package_name', $plan['package_name'])->first();

            if ($existing) {
                DB::table('packages')->where('id', $existing->id)->update($values);
                $packageId = $existing->id;
            } else {
                $packageId = DB::table('packages')->insertGetId($values + ['created_at' => $now]);
            }

            DB::table('package_modules')->where('package_id', $packageId)->delete();
            foreach ($moduleIds as $moduleId) {
                DB::table('package_modules')->insert(['package_id' => $packageId, 'module_id' => $moduleId]);
            }
        }

        // Essai de 7 jours : limites de Prestige (le restaurateur goûte à l'équipe, aux promotions et aux statistiques)
        if ($trial) {
            DB::table('packages')->where('id', $trial->id)->update([
                'trial_days' => 7,
                'trial_notification_before_days' => 2,
                'trial_message' => "7 jours d'essai gratuit",
                'staff_limit' => 5,
                'branch_limit' => 1,
                'additional_features' => json_encode(['Export Report']),
                'updated_at' => $now,
            ]);
        }

        // Forfaits d'origine de Jimeb : masqués de la liste publique
        DB::table('packages')->whereIn('package_name', ['Subscription Package', 'Life Time'])->update(['is_private' => 1]);
    }
}
