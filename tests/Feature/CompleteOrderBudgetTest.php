<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\User;
use App\Support\OrderBudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompleteOrderBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_complete_order_budget(): void
    {
        $orderBudget = $this->createOrderBudget();

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/complete", [
            'accepted_terms_of_use' => true,
        ])->assertUnauthorized();
    }

    public function test_complete_requires_accepted_terms_of_use(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderBudget = $this->createOrderBudget($user);

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/complete", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accepted_terms_of_use']);

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/complete", [
            'accepted_terms_of_use' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accepted_terms_of_use']);

        $this->assertNull($orderBudget->fresh()->completed_at);
    }

    public function test_complete_succeeds_when_terms_are_accepted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderBudget = $this->createOrderBudget($user);

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/complete", [
            'accepted_terms_of_use' => true,
        ])
            ->assertOk()
            ->assertJsonPath('is_completed', true);

        $this->assertNotNull($orderBudget->fresh()->completed_at);
    }

    public function test_guest_cannot_reopen_order_budget(): void
    {
        $orderBudget = $this->createOrderBudget();
        $orderBudget->forceFill(['completed_at' => now()])->save();

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/reopen")
            ->assertUnauthorized();
    }

    public function test_reopen_clears_completed_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderBudget = $this->createOrderBudget($user);
        $orderBudget->forceFill(['completed_at' => now()])->save();

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/reopen")
            ->assertOk()
            ->assertJsonPath('is_completed', false)
            ->assertJsonPath('completed_at', null);

        $this->assertNull($orderBudget->fresh()->completed_at);
    }

    public function test_reopen_is_idempotent_when_card_is_already_open(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $orderBudget = $this->createOrderBudget($user);

        $this->postJson("/api/v1/budgets/order-budgets/{$orderBudget->id}/reopen")
            ->assertOk()
            ->assertJsonPath('is_completed', false);

        $this->assertNull($orderBudget->fresh()->completed_at);
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
