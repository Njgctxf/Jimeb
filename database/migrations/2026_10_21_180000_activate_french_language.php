<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Activer le français
        DB::table('language_settings')->updateOrInsert(
            ['language_code' => 'fr'],
            [
                'language_name' => 'French',
                'flag_code' => 'fr',
                'active' => 1,
                'is_rtl' => 0,
            ]
        );

        // Activer l'anglais
        DB::table('language_settings')->updateOrInsert(
            ['language_code' => 'en'],
            [
                'language_name' => 'English',
                'flag_code' => 'gb',
                'active' => 1,
                'is_rtl' => 0,
            ]
        );

        Cache::forget('languages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('language_settings')->where('language_code', 'fr')->update(['active' => 0]);
        Cache::forget('languages');
    }
};
