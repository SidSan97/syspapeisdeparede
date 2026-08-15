<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\BudgetWall;
use App\Models\CollectionModel;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\User;
use App\Repositories\OrderBudgetRepository;
use App\Support\OrderBudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderBudgetInitialStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_from_budget_sets_waiting_art_when_model_requests_layout(): void
    {
        [$order, $budget] = $this->createOrderAndBudgetWithWall([
            'request_layout' => true,
            'request_art_on_payment' => false,
        ]);

        app(OrderBudgetRepository::class)->syncFromBudget($order, $budget);

        $this->assertDatabaseHas('order_budgets', [
            'order_id' => $order->id,
            'status' => OrderBudgetStatus::WAITING_ART,
        ]);
    }

    public function test_sync_from_budget_sets_waiting_payment_when_model_requests_art_on_payment(): void
    {
        [$order, $budget] = $this->createOrderAndBudgetWithWall([
            'request_layout' => false,
            'request_art_on_payment' => true,
        ]);

        app(OrderBudgetRepository::class)->syncFromBudget($order, $budget);

        $this->assertDatabaseHas('order_budgets', [
            'order_id' => $order->id,
            'status' => OrderBudgetStatus::WAITING_PAYMENT,
        ]);
    }

    public function test_sync_from_budget_sets_in_production_for_other_models(): void
    {
        [$order, $budget] = $this->createOrderAndBudgetWithWall([
            'request_layout' => false,
            'request_art_on_payment' => false,
        ]);

        app(OrderBudgetRepository::class)->syncFromBudget($order, $budget);

        $this->assertDatabaseHas('order_budgets', [
            'order_id' => $order->id,
            'status' => OrderBudgetStatus::IN_PRODUCTION,
        ]);
    }

    public function test_request_layout_takes_precedence_over_request_art_on_payment(): void
    {
        [$order, $budget] = $this->createOrderAndBudgetWithWall([
            'request_layout' => true,
            'request_art_on_payment' => true,
        ]);

        app(OrderBudgetRepository::class)->syncFromBudget($order, $budget);

        $card = OrderBudget::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($card);
        $this->assertSame(OrderBudgetStatus::WAITING_ART, $card->status);
    }

    /**
     * @param  array{request_layout: bool, request_art_on_payment: bool}  $modelFlags
     * @return array{0: Order, 1: Budget}
     */
    protected function createOrderAndBudgetWithWall(array $modelFlags): array
    {
        DB::table('layout_column_names')->insert([
            'id' => 1,
            'name' => 'Desenhista',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('production_column_names')->insert([
            'id' => 1,
            'name' => 'Produção',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create();
        $model = CollectionModel::factory()->create($modelFlags);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        $room = BudgetRoom::factory()->create([
            'budget_id' => $budget->id,
            'tenant_id' => $user->id,
            'position' => 1,
        ]);

        BudgetWall::factory()->create([
            'budget_room_id' => $room->id,
            'tenant_id' => $user->id,
            'position' => 1,
            'collection_model_id' => $model->id,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        return [$order, $budget->fresh(['rooms.walls.collectionModel'])];
    }
}
