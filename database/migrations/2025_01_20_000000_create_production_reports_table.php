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
        Schema::create('production_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_budget_id')
                ->constrained('order_budgets')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('action_type', 50); // 'mark_as_produced', 'production_percentage_100', etc
            $table->string('column_name', 255)->nullable(); // Nome da coluna no momento da ação
            $table->timestamp('action_date'); // Data e horário da ação
            $table->json('layout_summary')->nullable(); // Resumo do layout (detalhes da parede)
            $table->unsignedBigInteger('model_id')->nullable(); // ID do modelo selecionado
            $table->string('model_name')->nullable(); // Nome do modelo selecionado
            $table->text('card_description')->nullable(); // Descrição do card
            $table->json('additional_data')->nullable(); // Dados adicionais flexíveis para futuras extensões
            $table->timestamps();

            $table->index(['order_budget_id', 'action_date']);
            $table->index(['user_id', 'action_date']);
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_reports');
    }
};

