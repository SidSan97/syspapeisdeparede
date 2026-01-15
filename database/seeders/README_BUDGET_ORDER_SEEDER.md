# Seeder de Orçamentos e Pedidos

Este seeder permite popular o banco de dados com grandes volumes de orçamentos e pedidos para testes de performance.

## Factories Criadas

- **BudgetFactory**: Cria orçamentos completos com dados realistas
- **OrderFactory**: Cria pedidos completos com dados realistas
- **BudgetRoomFactory**: Cria ambientes para orçamentos/pedidos
- **BudgetWallFactory**: Cria paredes com medidas realistas
- **DropshippingDataFactory**: Cria dados de dropshipping (PF/PJ)
- **CollectionModelFactory**: Cria modelos de coleção

## Como Usar

### Executar o Seeder

```bash
php artisan db:seed --class=BudgetAndOrderSeeder
```

O seeder vai perguntar quantos registros você deseja criar:
- **500** registros (200 orçamentos + 300 pedidos)
- **1000** registros (400 orçamentos + 600 pedidos)
- **2000** registros (800 orçamentos + 1200 pedidos)

### Criar Manualmente

```php
use App\Models\Budget;
use App\Models\Order;

// Criar um orçamento simples
$budget = Budget::factory()->create();

// Criar um orçamento com rooms e walls
$budget = Budget::factory()
    ->withRooms()
    ->withDropshipping()
    ->create();

// Criar um pedido com todos os relacionamentos
$order = Order::factory()
    ->withRooms()
    ->withDropshipping()
    ->create();

// Criar múltiplos orçamentos
Budget::factory()->count(100)->create();

// Criar múltiplos pedidos
Order::factory()->count(100)->create();
```

## Estrutura dos Dados Gerados

### Orçamentos (Budget)
- ✅ Dados básicos (nome, valores, datas)
- ✅ Informações de frete
- ✅ Status variados (em aberto, aprovado, cancelado, etc.)
- ✅ Métodos de pagamento
- ✅ Dados de dropshipping (opcional)
- ✅ Rooms e Walls com medidas realistas
- ✅ Collection Models associados

### Pedidos (Order)
- ✅ Todos os campos do orçamento +
- ✅ Status de pagamento
- ✅ Links de pagamento
- ✅ Datas de expiração
- ✅ NF-e (opcional)
- ✅ Rooms e Walls
- ✅ Dados de dropshipping (opcional)

## Performance

O seeder foi otimizado para:
- ✅ Criar registros em lotes (batch processing)
- ✅ Usar transações para segurança
- ✅ Barra de progresso para acompanhar
- ✅ Criar relacionamentos eficientemente

## Limpar Dados

Para limpar os dados criados:

```bash
# Limpar orçamentos e pedidos
php artisan tinker
>>> Budget::truncate();
>>> Order::truncate();
>>> BudgetRoom::truncate();
>>> BudgetWall::truncate();
>>> DropshippingData::truncate();
```

**Atenção**: Isso vai deletar TODOS os orçamentos e pedidos do banco!

## Observações

- O seeder usa usuários existentes. Se não houver usuários, ele criará 10 automaticamente.
- Os relacionamentos (rooms, walls, dropshipping) são criados automaticamente.
- Os valores são calculados baseados nas áreas das paredes.
- tenant_id é definido automaticamente como o mesmo valor de user_id.
