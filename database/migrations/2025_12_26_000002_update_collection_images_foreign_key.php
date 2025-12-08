<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('collection_images', function (Blueprint $table) {
            $table->dropForeign(['collection_arts_id']);
            
            $table->dropColumn('collection_arts_id');
            
            $table->foreign('collection_category_id')
                ->references('id')
                ->on('collection_categories')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->unsignedBigInteger('collection_category_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('collection_images', function (Blueprint $table) {
            $table->dropForeign(['collection_category_id']);
            
            $table->unsignedBigInteger('collection_arts_id')->nullable()->after('id');
            
            $table->foreign('collection_arts_id')
                ->references('id')
                ->on('collection_arts_subcategories')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
};

