<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('my_favorites_collection_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('collection_image_id')
                ->constrained('collection_images')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            // Índice único para evitar duplicatas
            $table->unique(['user_id', 'collection_image_id'], 'unique_user_collection_image');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('my_favorites_collection_images');
    }
};

