<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\User;
use App\Support\OrderBudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadOrderBudgetArtTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_art(): void
    {
        $orderBudget = $this->createOrderBudget();

        $this->postJson('/api/v1/budgets/order-budgets/upload-art', [
            'comment' => 'Arte do designer',
            'order_budget_id' => $orderBudget->id,
            'dealer_id' => $orderBudget->order->user_id,
            'designer_id' => $orderBudget->order->user_id,
            'order_id' => $orderBudget->order_id,
        ])->assertUnauthorized();
    }

    public function test_uploading_art_sets_order_budget_status_to_art_received(): void
    {
        Storage::fake('public');

        $designer = User::factory()->create();
        $orderBudget = $this->createOrderBudget($designer);

        Sanctum::actingAs($designer);

        $this->post('/api/v1/budgets/order-budgets/upload-art', [
            'comment' => 'Arte do designer',
            'order_budget_id' => $orderBudget->id,
            'dealer_id' => $designer->id,
            'designer_id' => $designer->id,
            'order_id' => $orderBudget->order_id,
            'art_file' => $this->fakeArtFile(),
        ])->assertCreated();

        $this->assertTrue(
            OrderBudgetStatus::is($orderBudget->fresh()->status, OrderBudgetStatus::ART_RECEIVED)
        );
    }

    public function test_comment_without_file_keeps_pending_review_status(): void
    {
        $designer = User::factory()->create();
        $orderBudget = $this->createOrderBudget($designer);

        Sanctum::actingAs($designer);

        $this->post('/api/v1/budgets/order-budgets/upload-art', [
            'comment' => 'Somente comentário',
            'order_budget_id' => $orderBudget->id,
            'dealer_id' => $designer->id,
            'designer_id' => $designer->id,
            'order_id' => $orderBudget->order_id,
        ])->assertCreated();

        $this->assertTrue(
            OrderBudgetStatus::is($orderBudget->fresh()->status, OrderBudgetStatus::PENDING_REVIEW)
        );
    }

    protected function createOrderBudget(?User $user = null): OrderBudget
    {
        $user ??= User::factory()->create();

        $layoutColumnId = DB::table('layout_column_names')->insertGetId([
            'name' => 'Test Layout Column',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productionColumnId = DB::table('production_column_names')->insertGetId([
            'name' => 'Test Production Column',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        return OrderBudget::withoutGlobalScopes()->create([
            'order_id' => $order->id,
            'tenant_id' => $user->id,
            'status' => OrderBudgetStatus::WAITING_ART,
            'layout_column_names_id' => $layoutColumnId,
            'production_column_names_id' => $productionColumnId,
            'order_index' => 0,
        ]);
    }

    private function fakeArtFile(): UploadedFile
    {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIAAEAAQMBEQACEQEDEQH/xAAXAAEBAQEAAAAAAAAAAAAAAAAAAQID/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEAMQAAAAqf8A/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPwB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwB//9k=');

        return UploadedFile::fake()->createWithContent('art.jpg', $jpeg);
    }
}
