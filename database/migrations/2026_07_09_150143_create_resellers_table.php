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
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tiny_id')->unique();
            $table->string('tiny_code')->nullable();
            $table->string('name');
            $table->string('fantasy_name')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('site')->nullable();
            $table->string('ie')->nullable();
            $table->string('person_type')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('public_space')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('status');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('cnpj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
