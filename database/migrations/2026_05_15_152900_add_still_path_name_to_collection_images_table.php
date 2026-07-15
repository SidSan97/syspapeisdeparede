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
        Schema::table('collection_images', function (Blueprint $table) {
            $table->string('still_path_name', 255)->nullable()->after('path_name');
        });
    }

    public function down(): void
    {
        Schema::table('collection_images', function (Blueprint $table) {
            $table->dropColumn('still_path_name');
        });
    }
};
