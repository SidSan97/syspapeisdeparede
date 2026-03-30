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
        Schema::create('budget_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_room_id')
                ->constrained('budget_rooms')
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->boolean('continue_same_art')->default(false);
            $table->json('continuations')->nullable();
            $table->unsignedBigInteger('collection_model_id')->nullable();
            $table->string('comment_referring_model', 500)->nullable();
            $table->string('link_referring_model', 150)->nullable();
            $table->json('files_referring_model')->nullable();
            $table->text('collection_referring_model')->nullable();
            $table->json('request_layout_referring_model')->nullable();
            $table->decimal('total_area', 10, 2)->default(0);
            $table->decimal('strip_height', 8, 2)->nullable();
            $table->unsignedInteger('strip_count')->default(0);
            $table->timestamps();

            $table->index(['budget_room_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_walls');
    }
};
