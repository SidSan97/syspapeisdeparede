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
        Schema::create('request_layouts_art', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dealer_id')->comment('id do revendedor que criou o orçamento');
            $table->unsignedBigInteger('designer_id')->comment('id do designer que carregou a arte');
            $table->string('path_file', 255);
            $table->timestamps();

            $table->foreign('dealer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('designer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
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
        });

        Schema::dropIfExists('request_layouts_art');
    }
};

