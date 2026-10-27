<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // null = pas encore choisi ; « per_table » = un QR par table ; « single » = un QR unique, le client saisit sa table
            $table->string('qr_mode', 16)->nullable();
        });

        // Les restaurants existants continuent de fonctionner comme avant : un QR par table.
        DB::table('restaurants')->update(['qr_mode' => 'per_table']);
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('qr_mode');
        });
    }
};
