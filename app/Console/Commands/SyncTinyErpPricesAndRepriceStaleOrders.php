<?php

namespace App\Console\Commands;

use App\Http\Controllers\TinyErpSettingsController;
use App\Jobs\RepriceStaleUnpaidOrdersAndBudgetsJob;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncTinyErpPricesAndRepriceStaleOrders extends Command
{
    protected $signature = 'tinyerp:sync-prices-and-reprice';

    protected $description = 'Atualiza preços do Tiny ERP e reprecifica pedidos/orçamentos não pagos 30+ dias quando houver mudança';

    public function handle(TinyErpSettingsController $tinyErpController): int
    {
        // Compara com o último snapshot processado (não com o preço "atual"),
        // para disparar reprecificação somente quando houver mudança real.
        $lastProcessedVista = $this->toFloat(Setting::get('tiny_erp_last_processed_price_payment'));
        $lastProcessedPrazo = $this->toFloat(Setting::get('tiny_erp_last_processed_price_installment'));

        Cache::forget('tiny_erp_all_data');
        $tinyData = $tinyErpController->all();

        if (isset($tinyData['status']) && $tinyData['status'] === 'Erro') {
            $this->error('Não foi possível sincronizar preços do Tiny ERP.');

            return self::FAILURE;
        }

        $currentVista = $this->toFloat(Setting::get('tiny_erp_price_payment'));
        $currentPrazo = $this->toFloat(Setting::get('tiny_erp_price_installment'));

        if ($currentVista <= 0 || $currentPrazo <= 0) {
            $this->error('Preços do Tiny ERP inválidos após sincronização.');

            return self::FAILURE;
        }

        if ($lastProcessedVista === null || $lastProcessedPrazo === null) {
            Setting::set('tiny_erp_last_processed_price_payment', (string) $currentVista, 'string');
            Setting::set('tiny_erp_last_processed_price_installment', (string) $currentPrazo, 'string');
            $this->info('Snapshot inicial salvo. Nenhuma reprecificação executada.');

            return self::SUCCESS;
        }

        if ($this->sameMoney($lastProcessedVista, $currentVista) && $this->sameMoney($lastProcessedPrazo, $currentPrazo)) {
            $this->info('Sem mudança de preço do Tiny ERP. Nada a reprocessar.');

            return self::SUCCESS;
        }

        RepriceStaleUnpaidOrdersAndBudgetsJob::dispatch(
            $currentVista,
            $currentPrazo,
            $lastProcessedVista,
            $lastProcessedPrazo
        );

        $this->info('Mudança detectada. Job de reprecificação enviado para fila.');

        return self::SUCCESS;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function sameMoney(float $a, float $b): bool
    {
        return round($a, 2) === round($b, 2);
    }
}
