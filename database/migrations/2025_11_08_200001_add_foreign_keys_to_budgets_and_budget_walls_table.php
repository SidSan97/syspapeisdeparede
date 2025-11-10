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
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreign('primary_budget_room_id')
                ->references('id')
                ->on('budget_rooms')
                ->nullOnDelete();
        });

        Schema::table('budget_walls', function (Blueprint $table) {
            $table->foreign('collection_model_id')
                ->references('id')
                ->on('collection_models')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_walls', function (Blueprint $table) {
            $table->dropForeign(['collection_model_id']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropForeign(['primary_budget_room_id']);
        });
    }
};

