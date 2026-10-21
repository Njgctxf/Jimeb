<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Page d'accueil que voit le client après le scan du QR code
            if (!Schema::hasColumn('restaurants', 'welcome_cover')) {
                $table->string('welcome_cover')->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'welcome_title')) {
                $table->string('welcome_title', 120)->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'welcome_subtitle')) {
                $table->string('welcome_subtitle', 200)->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'welcome_button_text')) {
                $table->string('welcome_button_text', 40)->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'welcome_table_label')) {
                $table->string('welcome_table_label', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['welcome_cover', 'welcome_title', 'welcome_subtitle', 'welcome_button_text', 'welcome_table_label']);
        });
    }
};
