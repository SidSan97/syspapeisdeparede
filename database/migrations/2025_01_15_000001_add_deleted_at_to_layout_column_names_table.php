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
        // Remove a coluna deleted_at se existir (para migrar de soft delete para hard delete)
        if (Schema::hasColumn('layout_column_names', 'deleted_at')) {
            Schema::table('layout_column_names', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter: adicionar soft deletes novamente (se necessário)
        if (!Schema::hasColumn('layout_column_names', 'deleted_at')) {
            Schema::table('layout_column_names', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};

