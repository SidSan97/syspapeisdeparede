# Reprecificacao automatica por mudanca de preco Tiny ERP

Este documento descreve a implementacao de reprecificacao automatica de `orders` e `budgets` quando houver mudanca de preco no Tiny ERP.

## Objetivo

- Executar diariamente em background.
- Detectar mudanca real nos precos do Tiny ERP.
- Recalcular totais somente quando houver mudanca.
- Atualizar apenas registros nao pagos com 30+ dias.

## Arquivos envolvidos

- `app/Console/Commands/SyncTinyErpPricesAndRepriceStaleOrders.php`
- `app/Jobs/RepriceStaleUnpaidOrdersAndBudgetsJob.php`
- `routes/console.php`
- `app/Http/Controllers/TinyErpSettingsController.php`

## Fluxo da solucao

1. O scheduler dispara o comando diario:
    - `tinyerp:sync-prices-and-reprice`
2. O comando invalida `tiny_erp_all_data` e chama sincronizacao do Tiny:
    - `TinyErpSettingsController::all()`
3. A sincronizacao atualiza settings atuais:
    - `tiny_erp_price_payment` (preco a vista)
    - `tiny_erp_price_installment` (preco a prazo)
4. O comando compara com o ultimo snapshot processado:
    - `tiny_erp_last_processed_price_payment`
    - `tiny_erp_last_processed_price_installment`
5. Se nao houve mudanca, encerra sem reprecificar.
6. Se houve mudanca, envia job em fila:
    - `RepriceStaleUnpaidOrdersAndBudgetsJob`
7. O job recalcula `orders` e `budgets` elegiveis.
8. Ao final, atualiza o snapshot processado e o timestamp:
    - `tiny_erp_last_reprice_at`

## Regra de elegibilidade

### Orders

- `paid = 0`
- `updated_at <= now() - 30 dias`
- `status != Cancelado` (ou `status` nulo)

### Budgets associados

- `budgets` ligados a `orders` elegiveis por `budget_rooms.order_id`
- atualizacao feita para os `budgets` encontrados via relacionamento `rooms.order`

## Formula de recalculo

Para cada `order` e `budget` elegivel:

- `total_amount = (total_area * preco_vista_novo) + custo_modelos + frete`
- `total_amount_installments = (total_area * preco_prazo_novo) + custo_modelos + frete`

Onde:

- `preco_vista_novo = tiny_erp_price_payment`
- `preco_prazo_novo = tiny_erp_price_installment`
- `custo_modelos = soma dos valores dos collection models das walls`
- `frete = selected_carrier_price (ou 0)`

## Agendamento

Em `routes/console.php`:

- `Schedule::command('tinyerp:sync-prices-and-reprice')->dailyAt('02:00');`

Pode ser ajustado para outro horario conforme necessidade.

## Operacao em producao

Para funcionar continuamente:

1. Scheduler do Laravel ativo (`schedule:run` via cron).
2. Worker de fila ativo (`queue:work`/Supervisor).

Sem worker, o job de reprecificacao nao sera processado.

## Chaves de settings utilizadas

### Precos atuais

- `tiny_erp_price_payment`
- `tiny_erp_price_installment`

### Snapshot de controle da rotina

- `tiny_erp_last_processed_price_payment`
- `tiny_erp_last_processed_price_installment`
- `tiny_erp_last_reprice_at`

## Observacoes

- Na primeira execucao, o comando salva o snapshot inicial e nao reprecifica.
- A comparacao de mudanca usa arredondamento monetario para evitar falso positivo por ruído decimal.
- A rotina foi desenhada para minimizar processamento desnecessario.
