<?php

namespace Tests\Feature;

use App\Actions\Order\UpdateOrderAction;
use App\Models\BudgetRoom;
use App\Models\BudgetWall;
use App\Models\CollectionModel;
use App\Models\Order;
use App\Models\OrderPaymentLink;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdatePaidOrderPaymentStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_cosmetic_edit_keeps_paid_state_and_payment_links(): void
    {
        [$user, $order, $wall, $paymentLink] = $this->createPaidOrderWithWall();

        Sanctum::actingAs($user);

        $updated = app(UpdateOrderAction::class)->execute($order, [
            'name' => 'Pedido renomeado',
            'observation' => 'Nova observação',
            'status' => OrderStatus::APPROVED,
            'dropshipping_budget' => 0,
            'rooms' => [
                [
                    'name' => 'Sala',
                    'walls' => [
                        $this->wallPayloadFromModel($wall),
                    ],
                ],
            ],
        ]);

        $this->assertSame(1, (int) $updated->paid);
        $this->assertSame('paid', $updated->payment_status);
        $this->assertSame('Pedido renomeado', $updated->name);
        $this->assertSame('Nova observação', $updated->observation);

        $this->assertDatabaseHas('order_payment_links', [
            'id' => $paymentLink->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('budget_walls', [
            'id' => $wall->id,
            'width' => $wall->width,
            'height' => $wall->height,
        ]);
    }

    public function test_structural_model_change_marks_order_and_links_as_pending(): void
    {
        [$user, $order, $wall, $paymentLink] = $this->createPaidOrderWithWall();
        $newModel = CollectionModel::factory()->create([
            'request_layout' => false,
            'request_art_on_payment' => false,
        ]);

        Sanctum::actingAs($user);

        $updated = app(UpdateOrderAction::class)->execute($order, [
            'name' => $order->name,
            'rooms' => [
                [
                    'name' => 'Sala',
                    'walls' => [
                        array_merge($this->wallPayloadFromModel($wall), [
                            'model' => $newModel->id,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->assertSame(0, (int) $updated->paid);
        $this->assertSame('unpaid', $updated->payment_status);
        $this->assertSame('Aguardando pagamento', $updated->flags);

        $this->assertDatabaseHas('order_payment_links', [
            'id' => $paymentLink->id,
            'status' => 'pending',
        ]);
    }

    public function test_structural_measure_change_marks_order_as_unpaid(): void
    {
        [$user, $order, $wall] = $this->createPaidOrderWithWall();

        Sanctum::actingAs($user);

        $updated = app(UpdateOrderAction::class)->execute($order, [
            'rooms' => [
                [
                    'name' => 'Sala',
                    'walls' => [
                        array_merge($this->wallPayloadFromModel($wall), [
                            'width' => (float) $wall->width + 1,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->assertSame(0, (int) $updated->paid);
        $this->assertSame('unpaid', $updated->payment_status);
    }

    /**
     * @return array{0: User, 1: Order, 2: BudgetWall, 3: OrderPaymentLink}
     */
    protected function createPaidOrderWithWall(): array
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
        $model = CollectionModel::factory()->create([
            'request_layout' => false,
            'request_art_on_payment' => false,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
            'status' => OrderStatus::APPROVED,
            'paid' => 1,
            'payment_status' => 'paid',
            'flags' => 'Pagamento recebido',
            'payment_method' => 'pix',
        ]);

        $room = BudgetRoom::factory()->create([
            'order_id' => $order->id,
            'budget_id' => null,
            'tenant_id' => $user->id,
            'name' => 'Sala',
            'position' => 0,
        ]);

        $wall = BudgetWall::factory()->create([
            'budget_room_id' => $room->id,
            'tenant_id' => $user->id,
            'position' => 0,
            'width' => 3.0,
            'height' => 2.5,
            'collection_model_id' => $model->id,
            'continuations' => [],
        ]);

        $paymentLink = OrderPaymentLink::query()->create([
            'order_id' => $order->id,
            'components' => ['ARTES', 'PRODUTOS', 'FRETE'],
            'payment_method' => 'pix',
            'installments' => 1,
            'amount_artes' => 10,
            'amount_produtos' => 100,
            'amount_frete' => 20,
            'amount_total' => 130,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return [$user, $order->fresh(['rooms.walls']), $wall, $paymentLink];
    }

    /**
     * @return array<string, mixed>
     */
    protected function wallPayloadFromModel(BudgetWall $wall): array
    {
        return [
            'name' => $wall->name,
            'direction' => $wall->direction,
            'width' => (float) $wall->width,
            'height' => (float) $wall->height,
            'model' => $wall->collection_model_id,
            'continueSameArt' => (bool) $wall->continue_same_art,
            'continuations' => $wall->continuations ?? [],
            'comment_referring_model' => $wall->comment_referring_model,
            'link_referring_model' => $wall->link_referring_model,
            'files_referring_model' => $wall->files_referring_model ?? [],
            'collection_referring_model' => $wall->collection_referring_model,
        ];
    }
}
