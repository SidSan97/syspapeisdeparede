<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\User;
use App\Support\OrderBudgetStatus;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateOrderBudgetProductionPercentageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_production_percentage(): void
    {
        $orderBudget = $this->createOrderBudget();

        $this->putJson("/api/v1/orders/order-budgets/{$orderBudget->id}/production-percentage", [
            'production_percentage' => 50,
        ])->assertUnauthorized();
    }

    public function test_updates_percentage_using_order_budget_id_even_when_it_differs_from_order_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        $orderBudget = $this->createOrderBudget($user);

        $this->assertNotEquals($orderBudget->id, $orderBudget->order_id);

        $this->putJson("/api/v1/orders/order-budgets/{$orderBudget->id}/production-percentage", [
            'production_percentage' => 42.5,
        ])
            ->assertOk()
            ->assertJsonPath('id', $orderBudget->id)
            ->assertJsonPath('production_percentage', '42.5');

        $this->assertSame('42.5', $orderBudget->fresh()->production_percentage);
        $this->assertTrue(OrderStatus::is($orderBudget->order->fresh()->status, OrderStatus::IN_PRODUCTION));
        $this->assertSame('Produção em andamento', $orderBudget->order->fresh()->flags);
    }

    public function test_does_not_resolve_parent_order_id_as_order_budget(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        $orderBudget = $this->createOrderBudget($user);

        $this->assertNotEquals($orderBudget->id, $orderBudget->order_id);

        $this->putJson("/api/v1/orders/order-budgets/{$orderBudget->order_id}/production-percentage", [
            'production_percentage' => 50,
        ])->assertNotFound();
    }

    public function test_reaching_100_percent_marks_order_as_sent_and_sets_production_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderBudget = $this->createOrderBudget($user);

        $this->putJson("/api/v1/orders/order-budgets/{$orderBudget->id}/production-percentage", [
            'production_percentage' => 100,
        ])
            ->assertOk()
            ->assertJsonPath('production_percentage', '100.0');

        $orderBudget->refresh();

        $this->assertNotNull($orderBudget->production_date);
        $this->assertTrue(OrderStatus::is($orderBudget->order->fresh()->status, OrderStatus::SENT));
        $this->assertSame('Produção concluída', $orderBudget->order->fresh()->flags);
        $this->assertDatabaseHas('production_reports', [
            'order_budget_id' => $orderBudget->id,
            'user_id' => $user->id,
            'action_type' => 'production_percentage_100',
        ]);
    }

    public function test_production_percentage_is_required_and_must_be_between_0_and_100(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderBudget = $this->createOrderBudget($user);

        $this->putJson("/api/v1/orders/order-budgets/{$orderBudget->id}/production-percentage", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['production_percentage']);

        $this->putJson("/api/v1/orders/order-budgets/{$orderBudget->id}/production-percentage", [
            'production_percentage' => 101,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['production_percentage']);
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
            'status' => OrderBudgetStatus::APPROVE_LAYOUT,
            'layout_column_names_id' => $layoutColumnId,
            'production_column_names_id' => $productionColumnId,
            'order_index' => 0,
        ]);
    }
}
