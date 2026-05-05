<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderRepository;
use App\Services\OrderEditWalletCreditService;
use App\Services\OrderPaymentCompositionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateOrderAction
{
    public function __construct(
        protected OrderRepository $repository,
        protected DropshippingRepository $dropshippingRepository,
        protected OrderPaymentCompositionService $paymentCompositionService,
        protected OrderEditWalletCreditService $walletService
    ) {}

    public function execute(Order $order, $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $compositionBefore = null;
            if (
                ! empty($data['rooms']) && is_array($data['rooms'])
                && $this->walletService->shouldSnapshotCompositionForRoomEdit($order)
            ) {
                $compositionBefore = $this->paymentCompositionService->getOrderComposition($order);
            }

            $order = $this->repository->update($order, $data);

            if (!empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                $existingDropshipping = $order->dropshippingData;

                if ($existingDropshipping) {
                    $this->dropshippingRepository->update(
                        $data['dropshipping_data'],
                        $existingDropshipping->id
                    );
                } else {
                    $this->dropshippingRepository->create(
                        $data['dropshipping_data'],
                        null,
                        $order->id,
                        Auth::id()
                    );
                }
            } elseif (isset($data['dropshipping_budget']) && $data['dropshipping_budget'] === 0) {
                $order->dropshippingData()->delete();
            }

            if ($compositionBefore !== null) {
                $this->walletService->creditIfCompositionDecreased($order->fresh(), $compositionBefore);
            }

            return $order->fresh();
        });
    }
}
