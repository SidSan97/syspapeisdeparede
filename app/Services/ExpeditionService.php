<?php

namespace App\Services;

class ExpeditionService
{
    public function generateLabelSeparation(object $orderBudgets): array
    {
        $label = [];

        $maxIndex = $orderBudgets->max('order_index');
        $formattedOrderId = str_pad($orderBudgets->order_id, 5, '0', STR_PAD_LEFT);
        $label['title'] = $formattedOrderId . " - Revenda";

        if($orderBudgets->order_index === 1 && $orderBudgets->order_index < $maxIndex) {
            $label['status'] = "incompleto";
        } else if($orderBudgets->order_index > 1 && $orderBudgets->order_index < $maxIndex) {
            $label['status'] = "complemento incompleto";
        } else {
            $label['status'] = "complemento completo";
        }

        return $label;
    }
}
