<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Prix normal conservé quand un prix promo est actif (price = prix payé par le client)
            if (!Schema::hasColumn('menu_items', 'original_price')) {
                $table->decimal('original_price', 16, 3)->nullable()->after('price');
            }
            // Photos supplémentaires du plat (noms de fichiers)
            if (!Schema::hasColumn('menu_items', 'extra_images')) {
                $table->json('extra_images')->nullable()->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'extra_images']);
        });
    }
};
