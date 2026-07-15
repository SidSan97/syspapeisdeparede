<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_column_names', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('name');
        });

        DB::table('production_column_names')
            ->orderBy('id')
            ->get()
            ->each(function ($column, $index) {
                DB::table('production_column_names')
                    ->where('id', $column->id)
                    ->update(['order' => $index]);
            });
    }

    public function down(): void
    {
        Schema::table('production_column_names', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
