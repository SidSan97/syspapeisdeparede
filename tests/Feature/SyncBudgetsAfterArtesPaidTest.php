<?php

namespace Tests\Feature;

use App\Models\BudgetRoom;
use App\Models\BudgetWall;
use App\Models\CollectionModel;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\User;
use App\Services\OrderPaymentStateService;
use App\Support\OrderBudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncBudgetsAfterArtesPaidTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_art_on_payment_changes_status_to_waiting_art(): void
    {
        $orderBudget = $this->createOrderBudget([
            'request_art_on_payment' => true,
            'request_link' => false,
        ], OrderBudgetStatus::WAITING_PAYMENT);

        app(OrderPaymentStateService::class)->syncBudgetsAfterArtesPaid($orderBudget->order);

        $this->assertSame(
            OrderBudgetStatus::WAITING_ART,
            $orderBudget->fresh()->status
        );
    }

    public function test_request_art_on_payment_takes_precedence_over_request_link(): void
    {
        $orderBudget = $this->createOrderBudget([
            'request_art_on_payment' => true,
            'request_link' => true,
        ], OrderBudgetStatus::WAITING_PAYMENT);

        app(OrderPaymentStateService::class)->syncBudgetsAfterArtesPaid($orderBudget->order);

        $this->assertSame(
            OrderBudgetStatus::WAITING_ART,
            $orderBudget->fresh()->status
        );
    }

    public function test_model_without_art_on_payment_still_goes_to_art_received(): void
    {
        $orderBudget = $this->createOrderBudget([
            'request_art_on_payment' => false,
            'request_link' => false,
        ], OrderBudgetStatus::WAITING_PAYMENT);

        app(OrderPaymentStateService::class)->syncBudgetsAfterArtesPaid($orderBudget->order);

        $this->assertSame(
            OrderBudgetStatus::ART_RECEIVED,
            $orderBudget->fresh()->status
        );
    }

    /**
     * @param  array{request_art_on_payment: bool, request_link: bool}  $modelFlags
     */
    protected function createOrderBudget(array $modelFlags, string $status): OrderBudget
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

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        $room = BudgetRoom::factory()->create([
            'order_id' => $order->id,
            'budget_id' => null,
            'tenant_id' => $user->id,
            'position' => 1,
        ]);

        $wall = BudgetWall::factory()->create([
            'budget_room_id' => $room->id,
            'tenant_id' => $user->id,
            'position' => 1,
            'collection_model_id' => $model->id,
            'link_referring_model' => $modelFlags['request_link'] ? 'https://example.com/art' : null,
        ]);

        return OrderBudget::withoutGlobalScopes()->create([
            'order_id' => $order->id,
            'budget_wall_id' => $wall->id,
            'tenant_id' => $user->id,
            'status' => $status,
            'layout_column_names_id' => 1,
            'production_column_names_id' => 1,
            'order_index' => 1,
        ]);
    }
}
