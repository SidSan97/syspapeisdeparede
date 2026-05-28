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
        Schema::create('order_budget_activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_budget_id')
                ->constrained('order_budgets')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->unsignedInteger('duration_seconds');
            $table->timestamps();

            $table->index(['order_budget_id', 'ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_budget_activity_sessions');
    }
};
