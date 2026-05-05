<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BudgetAndOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info(' Iniciando criação de orçamentos e pedidos...');

        // Garantir que temos usuários
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('⚠️  Nenhum usuário encontrado. Criando 10 usuários...');
            User::factory()->count(10)->create();
            $users = User::all();
        }

        // Perguntar quantos registros criar
        $count = 200;

        // Desabilitar eventos e queries log para melhor performance
        $this->command->info("📊 Criando {$count} orçamentos...");

        DB::beginTransaction();

        try {
            $progressBar = $this->command->getOutput()->createProgressBar($count);
            $progressBar->start();

            // Criar orçamentos em lotes para melhor performance
            $batchSize = 50;
            $budgetCount = (int) ($count * 0.4); // 40% orçamentos
            $orderCount = (int) ($count * 0.6); // 60% pedidos

            // Criar orçamentos
            for ($i = 0; $i < $budgetCount; $i += $batchSize) {
                $currentBatch = min($batchSize, $budgetCount - $i);

                for ($j = 0; $j < $currentBatch; $j++) {
                    $user = $users->random();
                    $budget = Budget::factory()->create([
                        'user_id' => $user->id,
                        'tenant_id' => $user->id,
                    ]);

                    // Criar rooms e walls
                    $this->createRoomsForBudget($budget, $user->id);

                    // Criar dropshipping se necessário
                    if ($budget->dropshipping_budget) {
                        \App\Models\DropshippingData::factory()->create([
                            'budget_id' => $budget->id,
                            'dealer_id' => $user->id,
                        ]);
                    }

                    $progressBar->advance();
                }
            }

            // Criar pedidos (a partir de budgets)
            $this->command->newLine();
            $this->command->info("📦 Criando {$orderCount} pedidos a partir de orçamentos...");

            $orderProgressBar = $this->command->getOutput()->createProgressBar($orderCount);
            $orderProgressBar->start();

            for ($i = 0; $i < $orderCount; $i += $batchSize) {
                $currentBatch = min($batchSize, $orderCount - $i);

                for ($j = 0; $j < $currentBatch; $j++) {
                    $user = $users->random();

                    // Primeiro criar um Budget
                    $budget = Budget::factory()->create([
                        'user_id' => $user->id,
                        'tenant_id' => $user->id,
                        'status' => fake()->randomElement(['em aberto', 'aprovado', 'cancelado']),
                    ]);

                    // Criar rooms e walls para o Budget
                    $this->createRoomsForBudget($budget, $user->id);

                    // Criar dropshipping para o Budget se necessário
                    if ($budget->dropshipping_budget) {
                        \App\Models\DropshippingData::factory()->create([
                            'budget_id' => $budget->id,
                            'dealer_id' => $user->id,
                        ]);
                    }

                    // Pedido espelhando o orçamento (mesmas colunas compartilhadas) + vínculos budget/order/rooms
                    $orderStatusFlags = fake()->randomElement([
                        ['status' => 'em aberto', 'flags' => null],
                        ['status' => 'aprovado', 'flags' => 'Pagamento recebido'],
                        ['status' => 'Em produção', 'flags' => 'Produção em andamento'],
                        ['status' => 'Enviado', 'flags' => 'Produção concluída'],
                        ['status' => 'cancelado', 'flags' => null],
                    ]);

                    $this->createOrderLinkedToBudget($budget, $orderStatusFlags);

                    $orderProgressBar->advance();
                }
            }

            $orderProgressBar->finish();
            $progressBar->finish();

            DB::commit();

            $this->command->newLine(2);
            $this->command->info('✅ Criados com sucesso:');
            $this->command->info("   - {$budgetCount} orçamentos");
            $this->command->info("   - {$orderCount} pedidos");
            $this->command->info('   - Total: '.($budgetCount + $orderCount).' registros');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erro ao criar registros: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Mesmas colunas compartilhadas entre budgets e orders (alinhado a OrderRepository::sharedAttributesFromBudget).
     * Status do pedido é definido separadamente no seeder.
     */
    private function sharedOrderAttributesFromBudget(Budget $budget): array
    {
        return [
            'user_id' => $budget->user_id,
            'tenant_id' => $budget->tenant_id,
            'name' => $budget->name,
            'total_area' => $budget->total_area,
            'total_amount' => $budget->total_amount,
            'total_amount_installments' => $budget->total_amount_installments,
            'total_amount_markup' => $budget->total_amount_markup,
            'total_amount_installments_markup' => $budget->total_amount_installments_markup,
            'delivery_time' => $budget->delivery_time,
            'payment_method' => $budget->payment_method,
            'installment_limit' => $budget->installment_limit,
            'installments' => $budget->installments,
            'cep' => $budget->cep,
            'selected_carrier_name' => $budget->selected_carrier_name,
            'selected_carrier_price' => $budget->selected_carrier_price,
            'selected_carrier_delivery_time' => $budget->selected_carrier_delivery_time,
            'carriers_snapshot' => $budget->carriers_snapshot,
            'primary_budget_room_id' => $budget->primary_budget_room_id,
            'payment_file' => $budget->payment_file,
            'dropshipping_budget' => $budget->dropshipping_budget,
        ];
    }

    /**
     * Fluxo alinhado ao placeOrder: cria order com dados do budget, budgets.order_id e budget_rooms.order_id.
     *
     * @param  array{status: string, flags: ?string}  $orderStatusFlags
     */
    private function createOrderLinkedToBudget(Budget $budget, array $orderStatusFlags): Order
    {
        $budget->refresh();

        $order = Order::factory()->create(array_merge(
            $this->sharedOrderAttributesFromBudget($budget),
            [
                'status' => $orderStatusFlags['status'],
                'flags' => $orderStatusFlags['flags'],
            ]
        ));

        $budget->update(['order_id' => $order->getKey()]);

        $budget->rooms()->update(['order_id' => $order->getKey()]);

        if ($budget->dropshipping_budget) {
            \App\Models\DropshippingData::where('budget_id', $budget->id)
                ->update(['order_id' => $order->getKey()]);
        }

        return $order;
    }

    /**
     * Create rooms and walls for a budget
     */
    private function createRoomsForBudget(Budget $budget, int $tenantId): void
    {
        $roomCount = fake()->numberBetween(1, 5);
        $collectionModelIds = \App\Models\CollectionModel::pluck('id')->toArray();

        $rooms = [];
        for ($i = 0; $i < $roomCount; $i++) {
            $room = \App\Models\BudgetRoom::factory()->create([
                'budget_id' => $budget->id,
                'tenant_id' => $tenantId,
                'position' => $i + 1,
            ]);

            $wallCount = fake()->numberBetween(1, 4);
            for ($j = 0; $j < $wallCount; $j++) {
                $width = fake()->randomFloat(2, 2, 10);
                $height = fake()->randomFloat(2, 2.5, 3.5);
                $totalArea = $width * $height;

                \App\Models\BudgetWall::factory()->create([
                    'budget_room_id' => $room->id,
                    'tenant_id' => $tenantId,
                    'position' => $j + 1,
                    'width' => $width,
                    'height' => $height,
                    'total_area' => $totalArea,
                    'collection_model_id' => ! empty($collectionModelIds)
                        ? fake()->randomElement($collectionModelIds)
                        : \App\Models\CollectionModel::factory(),
                ]);
            }
            $rooms[] = $room;
        }

        // Atualizar primary_budget_room_id com o primeiro room
        if (! empty($rooms)) {
            $budget->update(['primary_budget_room_id' => $rooms[0]->id]);
        }

        // Recalcular total_area
        $totalArea = $budget->rooms()
            ->with('walls')
            ->get()
            ->flatMap->walls
            ->sum('total_area');

        $budget->update(['total_area' => round($totalArea, 2)]);
    }
}
