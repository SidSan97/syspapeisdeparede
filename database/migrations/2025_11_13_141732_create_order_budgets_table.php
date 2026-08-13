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
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('budget_wall_id')->nullable();
            $table->unsignedBigInteger('layout_column_names_id')->nullable();
            $table->unsignedBigInteger('production_column_names_id')->nullable()->default(1);
            $table->string('description', 500)->nullable();
            $table->date('production_date')->nullable();
            $table->decimal('production_percentage', 5, 1)->default(0);
            $table->string('status', 50);
            $table->integer('order_index');
            $table->tinyInteger('ready_to_expedition')->default(0);
            $table->string('tinyErp_order_id')->nullable();
            $table->integer('picking_label_generated')->default(0);
            $table->integer('tinyErp_order_expedition_id')->nullable();
            $table->datetime('activity_running_since')->nullable();
            $table->unsignedInteger('activity_elapsed_seconds')->default(0);
            $table->datetime('completed_at')->nullable();
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
            $table->foreign('production_column_names_id', 'order_budgets_prod_column_names_id_fk')
                ->references('id')
                ->on('production_column_names')
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
