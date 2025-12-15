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
        // Add tenant_id to budgets table
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to budget_rooms table
        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('budget_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to budget_walls table
        Schema::table('budget_walls', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('budget_room_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        Schema::table('order_budgets', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('order_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        Schema::table('request_layouts_art_interactions', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('card_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        Schema::table('request_layouts_art', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('order_budget_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        Schema::table('my_favorites_collection_images', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });

        Schema::table('layout_card_history', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('card_id')->constrained('users')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layout_card_history', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('my_favorites_collection_images', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('request_layouts_art', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('request_layouts_art_interactions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('order_budgets', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('budget_walls', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('budget_rooms', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};

