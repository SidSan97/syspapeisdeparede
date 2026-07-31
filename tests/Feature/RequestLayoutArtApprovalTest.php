<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestLayoutArtApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{art_id: int}
     */
    private function createPendingArt(User $user): array
    {
        $order = Order::factory()->create();

        $orderBudgetId = DB::table('order_budgets')->insertGetId([
            'order_id' => $order->id,
            'production_column_names_id' => null,
            'status' => 'pending',
            'order_index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $interactionId = DB::table('request_layouts_art_interactions')->insertGetId([
            'card_id' => $orderBudgetId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $artId = DB::table('request_layouts_art')->insertGetId([
            'dealer_id' => $user->id,
            'designer_id' => $user->id,
            'order_id' => $order->id,
            'order_budget_id' => $orderBudgetId,
            'approval_status' => 'pending',
            'interactions_card_id' => $interactionId,
            'path_file' => 'request-layout-arts/art.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['art_id' => $artId];
    }

    public function test_approval_requires_terms_of_use_acceptance(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ['art_id' => $artId] = $this->createPendingArt($user);

        $this->patchJson('/api/v1/budgets/request-layout-arts/status', [
            'request_layout_art_id' => $artId,
            'approval_status' => 'approved',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('accepted_terms_of_use');

        $this->assertDatabaseHas('request_layouts_art', [
            'id' => $artId,
            'approval_status' => 'pending',
        ]);
    }

    public function test_approval_is_rejected_when_terms_checkbox_is_false(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ['art_id' => $artId] = $this->createPendingArt($user);

        $this->patchJson('/api/v1/budgets/request-layout-arts/status', [
            'request_layout_art_id' => $artId,
            'approval_status' => 'approved',
            'accepted_terms_of_use' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('accepted_terms_of_use');
    }

    public function test_art_is_approved_when_terms_are_accepted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ['art_id' => $artId] = $this->createPendingArt($user);

        $this->patchJson('/api/v1/budgets/request-layout-arts/status', [
            'request_layout_art_id' => $artId,
            'approval_status' => 'approved',
            'accepted_terms_of_use' => true,
        ])->assertOk()
            ->assertJsonPath('approval_status', 'approved');

        $this->assertDatabaseHas('request_layouts_art', [
            'id' => $artId,
            'approval_status' => 'approved',
        ]);
    }

    public function test_rejection_does_not_require_terms_of_use(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        ['art_id' => $artId] = $this->createPendingArt($user);

        $this->patchJson('/api/v1/budgets/request-layout-arts/status', [
            'request_layout_art_id' => $artId,
            'approval_status' => 'rejected',
        ])->assertOk()
            ->assertJsonPath('approval_status', 'rejected');
    }
}
