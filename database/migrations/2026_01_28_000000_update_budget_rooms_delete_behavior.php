<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // Tornar o campo budget_id nullable (sem depender de doctrine/dbal)
        DB::statement('ALTER TABLE budget_rooms MODIFY budget_id BIGINT UNSIGNED NULL');

        // Recriar a FK com nullOnDelete (sem cascade)
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->foreign('budget_id')
                ->references('id')
                ->on('budgets')
                ->nullOnDelete();
        });

        // Trigger: ao excluir um orçamento
        // - se budget_rooms.order_id IS NULL => deletar o registro em budget_rooms
        // - senão => setar budget_rooms.budget_id = NULL
        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_budgets_after_delete
            AFTER DELETE ON budgets
            FOR EACH ROW
            BEGIN
                -- Remove os quartos que pertencem somente ao orçamento
                DELETE FROM budget_rooms
                WHERE budget_id = OLD.id
                AND (order_id IS NULL OR order_id = 0);
            END
            SQL
        );

        // Trigger: ao excluir um pedido
        // - se budget_rooms.budget_id IS NULL => deletar o registro em budget_rooms
        // - senão => setar budget_rooms.order_id = NULL
        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_orders_after_delete
            AFTER DELETE ON orders
            FOR EACH ROW
            BEGIN
                -- Remove os quartos que pertencem somente ao pedido
                DELETE FROM budget_rooms
                WHERE order_id = OLD.id
                AND (budget_id IS NULL OR budget_id = 0);
            END
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover triggers
        DB::unprepared('DROP TRIGGER IF EXISTS trg_budgets_after_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_orders_after_delete');

        // Voltar FK de budget_rooms.budget_id para NOT NULL + cascadeOnDelete
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
        });

        DB::statement('ALTER TABLE budget_rooms MODIFY budget_id BIGINT UNSIGNED NOT NULL');

        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->foreign('budget_id')
                ->references('id')
                ->on('budgets')
                ->cascadeOnDelete();
        });
    }
};

