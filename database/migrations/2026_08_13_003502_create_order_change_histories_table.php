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
        Schema::create('order_change_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('description', 500);
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('tenant_id');
            $table->index('reseller_id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_change_histories');
    }
};
