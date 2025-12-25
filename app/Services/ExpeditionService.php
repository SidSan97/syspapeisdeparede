<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Auth;

class ExpeditionService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function generateLabelSeparation(object $orderBudgets, object $order): array
    {
        $label = [];
        $label['carrier_name'] = null;
        $label['packer'] = null;

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
            $label['packer'] = Auth::user()->name;

            $this->orderRepository->updateReadyToExpedition($order->id);
        }

        return $label;
    }
}
