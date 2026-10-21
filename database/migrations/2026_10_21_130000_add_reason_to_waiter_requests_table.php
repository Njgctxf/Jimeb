<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waiter_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('waiter_requests', 'reason')) {
                $table->string('reason', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('waiter_requests', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
