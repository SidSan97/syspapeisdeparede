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
        Schema::create('collection_models', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 10, 2);
            $table->unsignedInteger('deadline');
            $table->foreignId('type_model_id')->constrained('models_types');
            $table->boolean('request_link')->default(false);
            $table->boolean('request_comment')->default(false);
            $table->boolean('request_file')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_models');
    }
};

