<?php

namespace App\Observers;

use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\BudgetWall;
use App\Models\OrderBudget;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Model;

class TenantObserver
{
    /**
     * Handle the model "creating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function creating(Model $model)
    {
        // Only set tenant_id if the model uses HasTenantScope trait
        if (in_array('App\Traits\HasTenantScope', class_uses_recursive($model))) {
            $tenantId = $this->getTenantIdForModel($model);

            if ($tenantId && !isset($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }
        }
    }

    /**
     * Handle the model "updating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updating(Model $model)
    {
        // Prevent tenant_id from being changed if user is a tenant
        if (TenantService::isTenant()) {
            $tenantId = TenantService::getCurrentTenantId();

            // If the model has tenant_id and user is trying to change it, prevent it
            if (isset($model->getOriginal()['tenant_id']) &&
                $model->getOriginal()['tenant_id'] == $tenantId) {
                // Keep the original tenant_id
                $model->tenant_id = $tenantId;
            }
        }
    }

    /**
     * Get tenant_id for a model, checking relationships if needed.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return int|null
     */
    protected function getTenantIdForModel(Model $model): ?int
    {
        // First, try to get from current authenticated user
        $tenantId = TenantService::getCurrentTenantId();
        if ($tenantId) {
            return $tenantId;
        }

        // If no tenant from auth, try to get from parent relationships
        // This handles cases where models are created through relationships
        if ($model instanceof BudgetRoom) {
            // Try to get from budget relationship if already loaded
            if ($model->relationLoaded('budget') && $model->budget && $model->budget->tenant_id) {
                return $model->budget->tenant_id;
            }
            // Otherwise, try to get from budget_id
            if ($model->budget_id) {
                $budget = Budget::find($model->budget_id);
                if ($budget && $budget->tenant_id) {
                    return $budget->tenant_id;
                }
            }
        }

        if ($model instanceof BudgetWall) {
            // Try to get from room relationship if already loaded
            if ($model->relationLoaded('room') && $model->room) {
                if ($model->room->tenant_id) {
                    return $model->room->tenant_id;
                }
                // Try through budget if room doesn't have tenant_id
                if ($model->room->relationLoaded('budget') && $model->room->budget && $model->room->budget->tenant_id) {
                    return $model->room->budget->tenant_id;
                }
            }
            // Otherwise, try to get from budget_room_id
            if ($model->budget_room_id) {
                $room = BudgetRoom::find($model->budget_room_id);
                if ($room) {
                    if ($room->tenant_id) {
                        return $room->tenant_id;
                    }
                    // Try through budget
                    if ($room->budget_id) {
                        $budget = Budget::find($room->budget_id);
                        if ($budget && $budget->tenant_id) {
                            return $budget->tenant_id;
                        }
                    }
                }
            }
        }

        if ($model instanceof OrderBudget) {
            // Try to get from budget relationship if already loaded
            if ($model->relationLoaded('budget') && $model->budget && $model->budget->tenant_id) {
                return $model->budget->tenant_id;
            }
            // Otherwise, try to get from budget_id
            if ($model->budget_id) {
                $budget = Budget::find($model->budget_id);
                if ($budget && $budget->tenant_id) {
                    return $budget->tenant_id;
                }
            }
        }

        if ($model instanceof \App\Models\LayoutCardHistory) {
            // Try to get from orderBudget relationship if already loaded
            if ($model->relationLoaded('orderBudget') && $model->orderBudget) {
                if ($model->orderBudget->tenant_id) {
                    return $model->orderBudget->tenant_id;
                }
                // Try through budget
                if ($model->orderBudget->relationLoaded('budget') && $model->orderBudget->budget && $model->orderBudget->budget->tenant_id) {
                    return $model->orderBudget->budget->tenant_id;
                }
            }
            // Otherwise, try to get from card_id
            if ($model->card_id) {
                $orderBudget = OrderBudget::find($model->card_id);
                if ($orderBudget) {
                    if ($orderBudget->tenant_id) {
                        return $orderBudget->tenant_id;
                    }
                    // Try through budget
                    if ($orderBudget->budget_id) {
                        $budget = Budget::find($orderBudget->budget_id);
                        if ($budget && $budget->tenant_id) {
                            return $budget->tenant_id;
                        }
                    }
                }
            }
        }

        if ($model instanceof \App\Models\RequestLayoutArt) {
            // Try to get from budget or orderBudget relationships
            if ($model->relationLoaded('budget') && $model->budget && $model->budget->tenant_id) {
                return $model->budget->tenant_id;
            }
            if ($model->relationLoaded('orderBudget') && $model->orderBudget && $model->orderBudget->tenant_id) {
                return $model->orderBudget->tenant_id;
            }
            // Otherwise, try to get from IDs
            if ($model->budget_id) {
                $budget = Budget::find($model->budget_id);
                if ($budget && $budget->tenant_id) {
                    return $budget->tenant_id;
                }
            }
            if ($model->order_budget_id) {
                $orderBudget = OrderBudget::find($model->order_budget_id);
                if ($orderBudget && $orderBudget->tenant_id) {
                    return $orderBudget->tenant_id;
                }
            }
        }

        return null;
    }
}

