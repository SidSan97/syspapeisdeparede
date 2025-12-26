<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\OrderBudgetRepository;
use Illuminate\Support\Facades\Auth;

class ExpeditionService
{
    protected $orderRepository;
    protected $orderBudgetRepository;
    public function __construct(OrderRepository $orderRepository, OrderBudgetRepository $orderBudgetRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->orderBudgetRepository = $orderBudgetRepository;
    }

    public function generateLabelSeparation(object $orderBudgets, object $order): array
    {
        $label = [];
        $label['carrier_name'] = null;
        $label['packer'] = Auth::user()->name;

        $maxIndex = $orderBudgets->max('order_index');
        $formattedOrderId = str_pad($orderBudgets->order_id, 5, '0', STR_PAD_LEFT);
        $label['title'] = $formattedOrderId . " - Revenda";

        if($orderBudgets->order_index === 1 && $orderBudgets->order_index < $maxIndex) {
            $label['status'] = "incompleto";
        } else if($orderBudgets->order_index > 1 && $orderBudgets->order_index < $maxIndex) {
            $label['status'] = "complemento incompleto";
        } else {
            $label['status'] = "complemento completo";
            $label['carrier_name'] = trim(explode(' - ', $order->selected_carrier_name)[0]);
        }

        $this->orderBudgetRepository->updateReadyToExpedition($orderBudgets->id);

        return $label;
    }
}
