<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->json('components');
            $table->string('payment_method', 30);
            $table->unsignedTinyInteger('installments')->nullable();
            $table->decimal('amount_artes', 12, 2)->default(0);
            $table->decimal('amount_produtos', 12, 2)->default(0);
            $table->decimal('amount_frete', 12, 2)->default(0);
            $table->decimal('amount_total', 12, 2)->default(0);
            $table->string('external_payment_link_id')->nullable();
            $table->string('external_order_id')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('external_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_links');
    }
};

