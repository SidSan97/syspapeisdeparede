<?php

namespace App\Services;

use App\Models\OrderBudget;
use App\Models\ProductionReport;
use App\Models\User;
use Carbon\Carbon;

class ProductionReportService
{
    /**
     * Generate a production report for an action
     *
     * @param OrderBudget $orderBudget
     * @param User $user
     * @param string $actionType
     * @param array $additionalData
     * @return ProductionReport
     */
    public function generateReport(
        OrderBudget $orderBudget,
        User $user,
        string $actionType,
        array $additionalData = []
    ): ProductionReport {
        // Carregar relacionamentos necessários
        $orderBudget->load([
            'productionColumnName',
            'wall.collectionModel',
            'wall.room',
        ]);

        // Obter nome da coluna
        $columnName = $orderBudget->productionColumnName?->name ?? 'Sem coluna definida';

        // Preparar resumo do layout (detalhes da parede)
        $layoutSummary = null;
        if ($orderBudget->wall) {
            $wall = $orderBudget->wall;
            $layoutSummary = [
                'wall_id' => $wall->id,
                'wall_name' => $wall->name,
                'width' => $wall->width,
                'height' => $wall->height,
                'total_area' => $wall->total_area,
                'strip_height' => $wall->strip_height,
                'strip_count' => $wall->strip_count,
                'continue_same_art' => $wall->continue_same_art,
                'room_name' => $wall->room?->name,
            ];
        }

        // Obter informações do modelo
        $modelId = null;
        $modelName = null;
        if ($orderBudget->wall?->collectionModel) {
            $modelId = $orderBudget->wall->collectionModel->id;
            $modelName = $orderBudget->wall->collectionModel->name;
        }

        // Criar o relatório
        return ProductionReport::create([
            'order_budget_id' => $orderBudget->id,
            'user_id' => $user->id,
            'action_type' => $actionType,
            'column_name' => $columnName,
            'action_date' => Carbon::now(),
            'layout_summary' => $layoutSummary,
            'model_id' => $modelId,
            'model_name' => $modelName,
            'card_description' => $orderBudget->description,
            'additional_data' => $additionalData,
        ]);
    }

    /**
     * Generate report for mark as produced action
     *
     * @param OrderBudget $orderBudget
     * @param User $user
     * @return ProductionReport
     */
    public function generateMarkAsProducedReport(OrderBudget $orderBudget, User $user): ProductionReport
    {
        return $this->generateReport(
            $orderBudget,
            $user,
            'mark_as_produced',
            [
                'production_percentage' => $orderBudget->production_percentage,
                'production_date' => $orderBudget->production_date?->format('Y-m-d'),
            ]
        );
    }

    /**
     * Generate report for production percentage reaching 100%
     *
     * @param OrderBudget $orderBudget
     * @param User $user
     * @param float $percentage
     * @return ProductionReport
     */
    public function generateProductionPercentageReport(
        OrderBudget $orderBudget,
        User $user,
        float $percentage
    ): ProductionReport {
        return $this->generateReport(
            $orderBudget,
            $user,
            'production_percentage_100',
            [
                'production_percentage' => $percentage,
                'production_date' => $orderBudget->production_date?->format('Y-m-d'),
            ]
        );
    }
}

