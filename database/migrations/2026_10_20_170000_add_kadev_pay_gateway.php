<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            $table->boolean('kadev_status')->default(false);
            $table->text('kadev_public_key')->nullable();
            $table->text('kadev_secret_key')->nullable();        // chiffrée (cast « encrypted »)
            $table->text('kadev_webhook_secret')->nullable();    // chiffré (cast « encrypted »)
        });

        Schema::create('kadev_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->decimal('amount', 16, 2);
            $table->string('reference')->nullable()->unique();   // « KDV-… », unique : une référence ne valide qu'une commande
            $table->string('payment_status')->default('pending');
            $table->text('payment_error_response')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kadev_payments');

        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            $table->dropColumn(['kadev_status', 'kadev_public_key', 'kadev_secret_key', 'kadev_webhook_secret']);
        });
    }
};
