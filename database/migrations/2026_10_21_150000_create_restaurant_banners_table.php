<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_banners')) {
            return;
        }

        Schema::create('restaurant_banners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->string('position', 10)->default('hero');      // hero = grande bannière, middle = bannière du milieu
            $table->string('image')->nullable();
            $table->string('title', 90)->nullable();
            $table->string('subtitle', 160)->nullable();
            $table->string('button_text', 30)->nullable();
            $table->string('target_type', 20)->default('menu');   // menu, promo, call, cart, category, dish
            $table->unsignedBigInteger('target_id')->nullable();   // plat ou catégorie visé
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_banners');
    }
};
