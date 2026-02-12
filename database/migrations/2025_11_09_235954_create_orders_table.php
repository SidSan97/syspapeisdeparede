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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('primary_budget_room_id')
                ->nullable()
                ->constrained('budget_rooms')
                ->nullOnDelete();

            // Basic fields
            $table->string('name');
            $table->decimal('total_area', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_amount_installments', 12, 2)->default(0);
            $table->unsignedInteger('delivery_time')->default(0);

            // Payment fields
            $table->string('payment_method')->nullable();
            $table->unsignedTinyInteger('installment_limit')->nullable();
            $table->unsignedTinyInteger('installments')->nullable();
            $table->string('payment_file')->nullable();

            // Shipping fields
            $table->string('cep', 9)->nullable();
            $table->string('selected_carrier_name')->nullable();
            $table->decimal('selected_carrier_price', 10, 2)->nullable();
            $table->unsignedInteger('selected_carrier_delivery_time')->nullable();
            $table->json('carriers_snapshot')->nullable();

            // Status and reference fields
            $table->string('status', 50)->nullable();
            $table->string('flags', 100)->nullable();

            // Other fields
            $table->tinyInteger('dropshipping_budget')->default(0);
            $table->string('link_payment')->nullable();
            $table->string('payment_expiration_date')->nullable();
            $table->tinyInteger('paid')->default(0);
            $table->tinyInteger('nf_sent')->default(0);
            $table->string('nf_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('payment_method');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

