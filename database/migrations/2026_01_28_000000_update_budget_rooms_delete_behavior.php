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
        // Ajustar a FK de budget_rooms.budget_id para permitir NULL
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
        });

        // Tornar o campo budget_id nullable
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_id')->nullable()->change();
        });

        // Recriar a FK com nullOnDelete (sem cascade)
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->foreign('budget_id')
                ->references('id')
                ->on('budgets')
                ->nullOnDelete();
        });

        // Regra de negócio: BudgetObserver e OrderObserver tratam a limpeza de budget_rooms
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Voltar FK de budget_rooms.budget_id para NOT NULL + cascadeOnDelete
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
        });

        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('budget_id')->nullable(false)->change();
        });

        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->foreign('budget_id')
                ->references('id')
                ->on('budgets')
                ->cascadeOnDelete();
        });
    }
};
