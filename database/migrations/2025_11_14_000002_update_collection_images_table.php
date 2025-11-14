<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('collection_images', function (Blueprint $table) {
            // Remove a foreign key antiga
            $table->dropForeign(['collection_arts_id']);
        });

        Schema::table('collection_images', function (Blueprint $table) {
            // Adiciona o campo name após collection_arts_id
            $table->string('name', 100)->nullable()->after('collection_arts_id');
        });

        Schema::table('collection_images', function (Blueprint $table) {
            // Adiciona a nova foreign key para collection_arts_subcategories
            $table->foreign('collection_arts_id')
                ->references('id')
                ->on('collection_arts_subcategories')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('collection_images', function (Blueprint $table) {
            // Remove a foreign key atual
            $table->dropForeign(['collection_arts_id']);
        });

        Schema::table('collection_images', function (Blueprint $table) {
            // Remove o campo name
            $table->dropColumn('name');
        });

        Schema::table('collection_images', function (Blueprint $table) {
            // Restaura a foreign key original para collection_arts
            $table->foreign('collection_arts_id')
                ->references('id')
                ->on('collection_arts')
                ->onDelete('set null')
                ->onUpdate('restrict');
        });
    }
};

