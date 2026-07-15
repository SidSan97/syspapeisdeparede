<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_arts_id')
                ->nullable()
                ->constrained('collection_arts')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->string('path_name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_images');
    }
};
