<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderRepository;
use App\Services\OrderChangeHistoryService;
use App\Services\OrderEditWalletCreditService;
use App\Services\OrderPaymentCompositionService;
use App\Services\OrderPaymentStateService;
use App\Services\OrderStructureComparisonService;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateOrderAction
{
    public function __construct(
        protected OrderRepository $repository,
        protected DropshippingRepository $dropshippingRepository,
        protected OrderPaymentCompositionService $paymentCompositionService,
        protected OrderEditWalletCreditService $walletService,
        protected OrderChangeHistoryService $changeHistoryService,
        protected OrderPaymentStateService $paymentStateService,
        protected OrderStructureComparisonService $structureComparisonService,
    ) {}

    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $wasApproved = OrderStatus::is($order->status, OrderStatus::APPROVED);
            $wasPaid = (int) $order->paid === 1;
            $snapshotBefore = $wasApproved
                ? $this->changeHistoryService->snapshot($order)
                : null;

            $hasStructuralRoomChanges = ! empty($data['rooms']) && is_array($data['rooms'])
                && $this->structureComparisonService->hasStructuralRoomChanges($order, $data['rooms']);

            // Nome, status, observação, dropshipping (ou rooms sem mudança estrutural)
            // não devem recriar paredes nem invalidar o pagamento.
            if (! empty($data['rooms']) && is_array($data['rooms']) && ! $hasStructuralRoomChanges) {
                unset($data['rooms']);
            }

            $compositionBefore = null;
            if (
                ! empty($data['rooms']) && is_array($data['rooms'])
                && $this->walletService->shouldSnapshotCompositionForRoomEdit($order)
            ) {
                $compositionBefore = $this->paymentCompositionService->getOrderComposition($order);
            }

            $order = $this->repository->update($order, $data);

            if (! empty($data['dropshipping_data']) && ($data['dropshipping_budget'] ?? null) === 1) {
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

            if ($hasStructuralRoomChanges && $wasPaid) {
                $this->paymentStateService->markAsUnpaidPending($order->fresh());
            }

            $order = $order->fresh(['rooms.walls', 'tenant']);

            if ($snapshotBefore !== null && $order) {
                $this->changeHistoryService->logApprovedOrderEdit(
                    $order,
                    $snapshotBefore,
                    $this->changeHistoryService->snapshot($order),
                );
            }

            return $order;
        });
    }
}
