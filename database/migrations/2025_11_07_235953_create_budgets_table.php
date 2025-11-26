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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('name');
            $table->decimal('total_area', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_amount_installments', 12, 2)->default(0);
            $table->unsignedInteger('delivery_time')->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedTinyInteger('installment_limit')->nullable();
            $table->unsignedTinyInteger('installments')->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('selected_carrier_name')->nullable();
            $table->decimal('selected_carrier_price', 10, 2)->nullable();
            $table->unsignedInteger('selected_carrier_delivery_time')->nullable();
            $table->json('carriers_snapshot')->nullable();
            $table->unsignedBigInteger('primary_budget_room_id')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('payment_file')->nullable();
            $table->string('comment_referring_model', 500)->nullable();
            $table->string('link_referring_model', 150)->nullable();
            $table->json('files_referring_model')->nullable();
            $table->text('collection_referring_model')->nullable();
            $table->tinyInteger('dropshipping_budget')->default(0);
            $table->timestamps();

            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
