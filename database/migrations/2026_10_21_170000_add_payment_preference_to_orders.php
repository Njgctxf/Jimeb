<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jimbe : le client choisit comment et quand il paie avant de commander ;
 * le serveur peut lui envoyer l'addition (et la relancer) depuis le tableau de bord.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pay_method', 12)->nullable()->after('order_note');   // cash | card | momo
            $table->string('pay_when', 8)->nullable()->after('pay_method');      // now | after
            $table->unsignedTinyInteger('pay_split')->default(1)->after('pay_when');
            $table->timestamp('bill_sent_at')->nullable()->after('pay_split');
            $table->unsignedTinyInteger('bill_sent_count')->default(0)->after('bill_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pay_method', 'pay_when', 'pay_split', 'bill_sent_at', 'bill_sent_count']);
        });
    }
};
