<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            INSERT INTO collection_categories (id, parent_id, name, image_cover, created_at, updated_at)
            SELECT id, NULL, name, image_cover, created_at, updated_at
            FROM collection_arts
        ");

        $maxId = DB::table('collection_categories')->max('id') ?? 0;
        
        // Migrar subcategorias
        $subcategories = DB::table('collection_arts_subcategories')->get();
        foreach ($subcategories as $index => $subcategory) {
            DB::table('collection_categories')->insert([
                'id' => $maxId + $index + 1,
                'parent_id' => $subcategory->collection_art_id,
                'name' => $subcategory->name,
                'image_cover' => $subcategory->sub_collection_image_cover,
                'created_at' => $subcategory->created_at,
                'updated_at' => $subcategory->updated_at,
            ]);
        }

        Schema::table('collection_images', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_category_id')->nullable()->after('id');
        });

        $subcategoryMap = [];
        $oldSubcategories = DB::table('collection_arts_subcategories')->get();
        foreach ($oldSubcategories as $oldSub) {
            $newCategory = DB::table('collection_categories')
                ->where('parent_id', $oldSub->collection_art_id)
                ->where('name', $oldSub->name)
                ->first();
            if ($newCategory) {
                $subcategoryMap[$oldSub->id] = $newCategory->id;
            }
        }
        
        foreach ($subcategoryMap as $oldId => $newId) {
            DB::table('collection_images')
                ->where('collection_arts_id', $oldId)
                ->update(['collection_category_id' => $newId]);
        }
    }

    public function down(): void
    {
        Schema::table('collection_images', function (Blueprint $table) {
            $table->dropColumn('collection_category_id');
        });
    }
};

