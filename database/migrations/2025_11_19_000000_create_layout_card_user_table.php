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
        Schema::create('layout_card_user', function (Blueprint $table) {
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->primary(['card_id', 'user_id'], 'layout_card_user_primary');

            $table->foreign('card_id', 'layout_card_user_card_id_fk')
                ->references('id')
                ->on('order_budgets')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('user_id', 'layout_card_user_user_id_fk')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_card_user');
    }
};
