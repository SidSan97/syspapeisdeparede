<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_arts_subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->foreignId('collection_art_id')
                ->constrained('collection_arts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_arts_subcategories');
    }
};

