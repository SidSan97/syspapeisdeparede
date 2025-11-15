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
        Schema::table('order_budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('layout_column_names_id')->after('status');
            
            $table->foreign('layout_column_names_id', 'layout_column_names_fk')
                ->references('id')
                ->on('layout_column_names')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_budgets', function (Blueprint $table) {
            $table->dropForeign('layout_column_names_fk');
            $table->dropColumn('layout_column_names_id');
        });
    }
};

