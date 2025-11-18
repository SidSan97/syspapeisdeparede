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
        Schema::create('order_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')
                ->constrained('budgets')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('budget_wall_id')->nullable();
            $table->unsignedBigInteger('layout_column_names_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('status', 50);
            $table->timestamps();

            $table->foreign('budget_wall_id', 'order_budgets_budget_wall_id_fk')
                ->references('id')
                ->on('budget_walls')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('layout_column_names_id', 'order_budgets_layout_column_names_id_fk')
                ->references('id')
                ->on('layout_column_names')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_budgets');
    }
};
