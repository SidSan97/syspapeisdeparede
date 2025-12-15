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
        Schema::create('request_layouts_art_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id')->comment('usuario que criou a requisição');
            $table->timestamps();

            $table->foreign('card_id', 'card_id_fk')
                ->references('id')
                ->on('order_budgets')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });

        Schema::create('request_layouts_art', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dealer_id')->comment('id do revendedor que criou o orçamento');
            $table->unsignedBigInteger('designer_id')->comment('id do designer que carregou a arte');
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('order_budget_id');
            $table->unsignedBigInteger('interactions_card_id');
            $table->string('path_file', 255);
            $table->text('comment', 500)->nullable();
            $table->timestamps();

            $table->foreign('dealer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('designer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('budget_id')
                ->references('id')
                ->on('budgets')
                ->onDelete('cascade');

            $table->foreign('order_budget_id')
                ->references('id')
                ->on('order_budgets')
                ->onDelete('cascade');

            $table->foreign('interactions_card_id', 'interactions_card_id')
                ->references('id')
                ->on('request_layouts_art_interactions')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_layouts_art', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
            $table->dropForeign(['designer_id']);
            $table->dropForeign(['budget_id']);
            $table->dropForeign(['order_budget_id']);
            $table->dropForeign(['interactions_card_id']);
            $table->dropColumn('comment');
            $table->dropColumn('interactions_card_id');
        });

        Schema::dropIfExists('request_layouts_art');
        Schema::dropIfExists('request_layouts_art_interactions');
    }
};

