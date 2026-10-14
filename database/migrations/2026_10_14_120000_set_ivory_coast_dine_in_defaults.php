<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jimbe : service sur place uniquement
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('allow_customer_delivery_orders')->default(false)->change();
            $table->boolean('allow_customer_pickup_orders')->default(false)->change();
        });

        DB::table('restaurants')->update([
            'allow_customer_delivery_orders' => false,
            'allow_customer_pickup_orders' => false,
            'allow_dine_in_orders' => true,
            'table_required' => true,
        ]);

        DB::table('order_types')->whereIn('slug', ['delivery', 'pickup'])->where('is_default', true)
            ->update(['is_active' => false, 'enable_from_customer_site' => false]);

        // Devise : Franc CFA (XOF), sans décimales
        $ci = DB::table('countries')->where('countries_code', 'CI')->first();
        $xof = [
            'currency_name' => 'Franc CFA',
            'currency_symbol' => 'FCFA',
            'currency_code' => 'XOF',
            'currency_position' => 'right_with_space',
            'no_of_decimal' => 0,
            'thousand_separator' => ' ',
            'decimal_separator' => ',',
        ];

        foreach (DB::table('restaurants')->get(['id']) as $restaurant) {
            $existing = DB::table('currencies')->where('restaurant_id', $restaurant->id)->where('currency_code', 'XOF')->value('id');
            $currencyId = $existing ?: DB::table('currencies')->insertGetId($xof + [
                'restaurant_id' => $restaurant->id,
            ]);

            $update = ['currency_id' => $currencyId, 'timezone' => 'Africa/Abidjan'];
            if ($ci) {
                $update['country_id'] = $ci->id;
            }
            DB::table('restaurants')->where('id', $restaurant->id)->update($update);
        }

        $globalId = DB::table('global_currencies')->where('currency_code', 'XOF')->value('id')
            ?: DB::table('global_currencies')->insertGetId($xof);

        DB::table('global_settings')->update(['default_currency_id' => $globalId, 'timezone' => 'Africa/Abidjan']);
    }

    public function down(): void
    {
        // Pas de retour en arrière automatique
    }
};
