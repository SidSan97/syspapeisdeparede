<?php

namespace App\Actions\Budget;

use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Repositories\DropshippingRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateBudgetAction
{
    public function __construct(
        protected BudgetRepository $repository,
        protected DropshippingRepository $dropshippingRepository
    ) {}

    public function execute(Budget $budget, $data): Budget
    {
        return DB::transaction(function () use ($budget, $data) {
            $budget = $this->repository->update($budget, $data);

            if (! empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                $existingDropshipping = $budget->dropshippingData;

                if ($existingDropshipping) {
                    $this->dropshippingRepository->update(
                        $data['dropshipping_data'],
                        $existingDropshipping->id
                    );
                } else {
                    $this->dropshippingRepository->create(
                        $data['dropshipping_data'],
                        $budget->id,
                        null,
                        Auth::id()
                    );
                }
            } elseif (isset($data['dropshipping_budget']) && $data['dropshipping_budget'] === 0) {
                $budget->dropshippingData()->delete();
            }

            return $budget->fresh(['dropshippingData']);
        });
    }
}
