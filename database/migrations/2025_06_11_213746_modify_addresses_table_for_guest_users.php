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
        Schema::table('addresses', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['user_id']);

            // Modify user_id to be nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add foreign key constraint with nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['user_id']);

            // Remove null constraint
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Re-add foreign key constraint as non-nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
