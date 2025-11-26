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
        Schema::create('dropshipping_data', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('person_type', ['PF', 'PJ', ''])->notNull();
            $table->string('cpf_cnpj', 18);
            $table->string('IE', 18)->nullable();
            $table->string('email', 255);
            $table->string('phone', 15);
            $table->string('cep', 9);
            $table->string('uf', 2);
            $table->string('state', 30);
            $table->string('city', 255);
            $table->string('neighborhood', 255);
            $table->string('public_space', 255)->nullable();
            $table->string('complement', 255)->nullable();
            $table->foreignId('dealer_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('budget_id')
                ->constrained('budgets')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropshipping_data');
    }
};

