<?php

namespace App\Services;

use App\Models\BudgetWall;
use App\Models\Order;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Support\Collection;

class OrderService
{
    public function prepareMergeOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
        sort($orderIds);

        return $orderIds;
    }

    public function normalizePaymentMethodForMerge(?string $method): string
    {
        if ($method === null) {
            return '';
        }

        return $method === 'installment' ? 'credit_card' : $method;
    }

    public function normalizeCepDigits(?string $cep): string
    {
        return preg_replace('/\D/', '', (string) ($cep ?? ''));
    }

    public function normalizeCpfCnpjDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) ($value ?? ''));
    }

    public function validateOrdersForMerge(Collection $orders): bool
    {
        $base = $orders->first();

        foreach ($orders as $o) {
            if ((int) $o->paid === 1) {
                throw new \InvalidArgumentException('Não é possível juntar pedidos já pagos.');
            }
            $st = strtolower((string) ($o->status ?? ''));
            if ($st === 'cancelado') {
                throw new \InvalidArgumentException('Não é possível juntar pedidos cancelados.');
            }
        }

        $tenantIds = $orders->pluck('tenant_id')->unique()->filter(fn ($id) => $id !== null);
        if ($tenantIds->count() > 1) {
            throw new \InvalidArgumentException('Os pedidos devem pertencer ao mesmo revendedor.');
        }

        $payBase = $this->normalizePaymentMethodForMerge($base->payment_method);
        foreach ($orders as $o) {
            if ($this->normalizePaymentMethodForMerge($o->payment_method) !== $payBase) {
                throw new \InvalidArgumentException('Todos os pedidos devem ter o mesmo tipo de pagamento.');
            }
        }

        if ($payBase === 'credit_card') {
            $inst0 = (int) ($base->installments ?? 0);
            $lim0 = (int) ($base->installment_limit ?? 0);
            foreach ($orders as $o) {
                if ((int) ($o->installments ?? 0) !== $inst0) {
                    throw new \InvalidArgumentException('No cartão parcelado, todos os pedidos devem ter o mesmo número de parcelas.');
                }
                if ((int) ($o->installment_limit ?? 0) !== $lim0) {
                    throw new \InvalidArgumentException('No cartão parcelado, todos os pedidos devem ter o mesmo limite de parcelas.');
                }
            }
        }

        $name0 = $base->selected_carrier_name;
        $price0 = round((float) ($base->selected_carrier_price ?? 0), 2);
        $time0 = (int) ($base->selected_carrier_delivery_time ?? 0);
        $cep0 = $this->normalizeCepDigits($base->cep);

        foreach ($orders as $o) {
            if ($o->selected_carrier_name !== $name0) {
                throw new \InvalidArgumentException('Todos os pedidos devem ter exatamente a mesma transportadora (frete).');
            }
            if (round((float) ($o->selected_carrier_price ?? 0), 2) !== $price0) {
                throw new \InvalidArgumentException('Todos os pedidos devem ter o mesmo valor de frete.');
            }
            if ((int) ($o->selected_carrier_delivery_time ?? 0) !== $time0) {
                throw new \InvalidArgumentException('Todos os pedidos devem ter o mesmo prazo de frete.');
            }
            if ($this->normalizeCepDigits($o->cep) !== $cep0) {
                throw new \InvalidArgumentException('Todos os pedidos devem ter o mesmo CEP de frete.');
            }
        }

        $dsFlags = $orders->map(fn ($o) => (int) ($o->dropshipping_budget ?? 0))->all();
        $hasDs = in_array(1, $dsFlags, true);
        $allDs = ! in_array(0, $dsFlags, true);
        if ($hasDs && ! $allDs) {
            throw new \InvalidArgumentException('Se algum pedido tiver dropshipping, todos devem ter dropshipping.');
        }

        if ($allDs) {
            $docs = $orders->map(function ($o) {
                $cpf = $o->dropshippingData?->cpf_cnpj;

                return $this->normalizeCpfCnpjDigits($cpf);
            })->all();
            foreach ($docs as $d) {
                if ($d === '') {
                    throw new \InvalidArgumentException('Dados de dropshipping incompletos (CPF/CNPJ) em um dos pedidos.');
                }
            }
            $d0 = $docs[0];
            foreach ($docs as $d) {
                if ($d !== $d0) {
                    throw new \InvalidArgumentException('Com dropshipping, todos os pedidos devem ter o mesmo CPF/CNPJ.');
                }
            }
        }

        return $allDs;
    }

    public function collectMergedRoomsFromOrders(Collection $orders): array
    {
        $mergedRooms = [];
        foreach ($orders as $order) {
            foreach ($this->orderRoomsToPayloadArray($order) as $roomPayload) {
                $mergedRooms[] = $roomPayload;
            }
        }

        if (count($mergedRooms) === 0) {
            throw new \InvalidArgumentException('Não há ambientes/paredes para juntar nestes pedidos.');
        }

        return $mergedRooms;
    }

    public function computeMergedOrderTotals(array $mergedRooms, array $selectedCarrier): array
    {
        $totalArea = BudgetCalculator::calculateTotalArea($mergedRooms);
        $totalAmount = BudgetCalculator::calculateTotalAmountVista($totalArea, $mergedRooms, $selectedCarrier);
        $totalAmountInstallments = BudgetCalculator::calculateTotalAmountPrazo($totalArea, $mergedRooms, $selectedCarrier);
        $deliveryTime = BudgetCalculator::calculateDeliveryTime($mergedRooms, $selectedCarrier);

        return [
            'total_area' => $totalArea,
            'total_amount' => $totalAmount,
            'total_amount_installments' => $totalAmountInstallments,
            'delivery_time' => $deliveryTime,
        ];
    }

    protected function orderRoomsToPayloadArray(Order $order): array
    {
        $out = [];
        foreach ($order->rooms->sortBy('position') as $room) {
            $walls = [];
            foreach ($room->walls->sortBy('position') as $wall) {
                $walls[] = $this->budgetWallToRoomWallPayload($wall);
            }
            $out[] = [
                'name' => $room->name,
                'walls' => $walls,
            ];
        }

        return $out;
    }

    protected function budgetWallToRoomWallPayload(BudgetWall $wall): array
    {
        return [
            'name' => $wall->name,
            'width' => $wall->width !== null ? (float) $wall->width : null,
            'height' => $wall->height !== null ? (float) $wall->height : null,
            'model' => $wall->collection_model_id,
            'continueSameArt' => (bool) $wall->continue_same_art,
            'continuations' => is_array($wall->continuations) ? $wall->continuations : [],
            'comment_referring_model' => $wall->comment_referring_model,
            'link_referring_model' => $wall->link_referring_model,
            'files_referring_model' => $wall->files_referring_model,
            'collection_referring_model' => $wall->collection_referring_model,
        ];
    }
}
