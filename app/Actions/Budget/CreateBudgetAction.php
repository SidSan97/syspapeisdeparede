<?php

namespace App\Actions\Budget;

use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Repositories\DropshippingRepository;
use App\Support\DocumentValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateBudgetAction
{
    public function __construct(
        protected BudgetRepository $repository,
        protected DropshippingRepository $dropshippingRepository,
    ) {}

    public function execute($data): Budget
    {
        return DB::transaction(function () use ($data) {
            $budget = $this->repository->create($data);

            // Criar dados de dropshipping se fornecidos
            if (! empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                if (! DocumentValidator::validateCPFCNPJ($data['dropshipping_data']['cpf_cnpj'])) {
                    abort(422, 'CPF/CNPJ inválido');
                }

                $this->dropshippingRepository->create(
                    $data['dropshipping_data'],
                    $budget->id,
                    null,
                    Auth::id()
                );
            }

            return $budget;
        });
    }
}
