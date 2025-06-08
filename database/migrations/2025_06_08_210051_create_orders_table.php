<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('guest_email')->nullable();
            $table->string('order_number', 32)->unique();
            $table->foreignId('shipping_address_id')->constrained('addresses')->onDelete('restrict');
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses')->onDelete('restrict');
            $table->string('status', 50)->default('pendiente_pago');
            $table->decimal('subtotal_amount', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_gateway', 50)->nullable();
            $table->string('payment_id')->nullable()->index();
            $table->string('payment_status', 50)->default('pendiente');
            $table->string('shipping_method_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Añadir indices para optimizar consultas comunes
            $table->index(['user_id', 'created_at']);
            $table->index(['guest_email', 'created_at']);
            $table->index(['status']);
            $table->index(['payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
