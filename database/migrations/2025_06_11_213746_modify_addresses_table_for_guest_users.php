<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // For testing purposes, we'll make this migration irreversible
        // since changing from nullable to non-nullable with existing null data
        // is problematic in SQLite
        throw new \Exception('This migration cannot be reversed safely. Please use a fresh migration instead.');
    }
};
