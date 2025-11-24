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
        Schema::create('layout_card_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->string('description', 500)->nullable();
            $table->string('type_page', 155)->nullable();
            $table->timestamps();

            $table->foreign('card_id', 'layout_card_history_card_id_fk')
                ->references('id')
                ->on('order_budgets')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_card_history');
    }
};

