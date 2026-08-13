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

class InvoiceOrderBudgetCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_expedition_cards(): void
    {
        $this->getJson('/api/v1/orders/expedition')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_invoice_order_budget_cards(): void
    {
        $this->postJson('/api/v1/orders/order-budgets/ready-to-expedition', [
            'order_budget_ids' => [1],
        ])->assertUnauthorized();
    }

    public function test_separation_list_excludes_cards_with_picking_label_generated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $withoutLabel = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 0,
            'order_index' => 1,
        ]);
        $withLabel = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 2,
        ]);

        $ids = collect($this->getJson('/api/v1/orders/expedition?stage=separation')
            ->assertOk()
            ->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($withoutLabel->id, $ids);
        $this->assertNotContains($withLabel->id, $ids);
    }

    public function test_in_separation_list_only_includes_cards_with_picking_label_generated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $withoutLabel = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 0,
            'order_index' => 1,
        ]);
        $withLabel = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 2,
        ]);

        $response = $this->getJson('/api/v1/orders/expedition?stage=in_separation')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($withoutLabel->id, $ids);
        $this->assertContains($withLabel->id, $ids);
        $this->assertFalse($response->json('data.0.can_invoice_order'));
    }

    public function test_can_invoice_order_is_true_when_all_sibling_cards_have_labels(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $first = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 1,
        ]);
        $second = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 2,
        ]);

        $response = $this->getJson('/api/v1/orders/expedition?stage=in_separation')
            ->assertOk();

        $this->assertTrue($response->json('data.0.can_invoice_order'));
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $response->json('data.0.order_budget_ids')
        );
    }

    public function test_invoice_fails_when_a_sibling_card_has_no_label(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $labeled = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 1,
        ]);
        $this->createPickingCard($user, $order, [
            'picking_label_generated' => 0,
            'order_index' => 2,
        ]);

        $this->postJson('/api/v1/orders/order-budgets/ready-to-expedition', [
            'order_budget_ids' => [$labeled->id],
            ...$this->packingPayload(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_budget_ids');

        $this->assertSame(0, (int) $labeled->fresh()->ready_to_expedition);
    }

    public function test_invoice_marks_all_order_cards_as_ready_to_expedition(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $first = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 1,
        ]);
        $second = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 2,
        ]);

        $this->postJson('/api/v1/orders/order-budgets/ready-to-expedition', [
            'order_budget_ids' => [$first->id, $second->id],
            ...$this->packingPayload(),
        ])
            ->assertOk()
            ->assertJsonPath('updated', 2);

        $this->assertSame(1, (int) $first->fresh()->ready_to_expedition);
        $this->assertSame(1, (int) $second->fresh()->ready_to_expedition);
        $this->assertSame('João Embalador', $order->fresh()->packer_name);
        $this->assertSame(3, (int) $order->fresh()->quantity_volumes);

        $ids = collect($this->getJson('/api/v1/orders/expedition?stage=in_separation')
            ->assertOk()
            ->json('data'))
            ->pluck('id')
            ->all();

        $this->assertNotContains($first->id, $ids);
        $this->assertNotContains($second->id, $ids);
    }

    public function test_invoice_requires_packer_name_and_volume_quantity(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $card = $this->createPickingCard($user, $order, [
            'picking_label_generated' => 1,
            'order_index' => 1,
        ]);

        $this->postJson('/api/v1/orders/order-budgets/ready-to-expedition', [
            'order_budget_ids' => [$card->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['packer_name', 'quantidade_volumes']);

        $this->assertNull($order->fresh()->packer_name);
        $this->assertSame(0, (int) $card->fresh()->ready_to_expedition);
    }

    /**
     * @return array{packer_name: string, quantidade_volumes: int}
     */
    protected function packingPayload(): array
    {
        return [
            'packer_name' => 'João Embalador',
            'quantidade_volumes' => 3,
        ];
    }

    protected function createOrder(User $user): Order
    {
        return Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createPickingCard(User $user, Order $order, array $overrides = []): OrderBudget
    {
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

        return OrderBudget::withoutGlobalScopes()->create(array_merge([
            'order_id' => $order->id,
            'tenant_id' => $user->id,
            'status' => OrderBudgetStatus::APPROVE_LAYOUT,
            'layout_column_names_id' => $layoutColumnId,
            'production_column_names_id' => $productionColumnId,
            'order_index' => 1,
            'production_percentage' => 100,
            'picking_label_generated' => 0,
            'ready_to_expedition' => 0,
        ], $overrides));
    }
}
