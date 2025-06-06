<?php

declare(strict_types=1);

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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Índice único para evitar múltiples carritos activos por usuario
            $table->unique(['user_id'], 'unique_user_cart');

            // Índice para sesiones de invitados
            $table->index(['session_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
