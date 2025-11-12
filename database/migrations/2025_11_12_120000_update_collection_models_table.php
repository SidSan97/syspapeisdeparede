<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('collection_models', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_models', 'name')) {
                $table->string('name')->default('')->after('id');
            }
        });

        if (Schema::hasTable('models_types')) {
            DB::table('collection_models')
                ->join('models_types', 'collection_models.type_model_id', '=', 'models_types.id')
                ->update([
                    'collection_models.name' => DB::raw('models_types.name'),
                ]);
        }

        Schema::table('collection_models', function (Blueprint $table) {
            if (Schema::hasColumn('collection_models', 'type_model_id')) {
                $table->dropForeign(['type_model_id']);
                $table->dropColumn('type_model_id');
            }
        });

        Schema::dropIfExists('models_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('models_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('collection_models', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_models', 'type_model_id')) {
                $table->foreignId('type_model_id')
                    ->nullable()
                    ->constrained('models_types')
                    ->nullOnDelete();
            }

            if (Schema::hasColumn('collection_models', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};

