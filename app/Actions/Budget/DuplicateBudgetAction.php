<?php

namespace App\Actions\Budget;

use App\Models\Budget;
use App\Repositories\DropshippingRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DuplicateBudgetAction
{
    public function __construct(
        protected DropshippingRepository $dropshippingRepository
    ) {}

    public function execute(Budget $source): Budget
    {
        $source->loadMissing(['rooms.walls', 'dropshippingData']);

        return DB::transaction(function () use ($source) {
            $budget = Budget::create([
                'user_id' => $source->user_id,
                'tenant_id' => $source->tenant_id,
                'order_id' => null,
                'name' => $source->name,
                'total_area' => $source->total_area,
                'total_amount' => $source->total_amount,
                'total_amount_installments' => $source->total_amount_installments,
                'total_amount_markup' => $source->total_amount_markup,
                'total_amount_installments_markup' => $source->total_amount_installments_markup,
                'delivery_time' => $source->delivery_time,
                'cep' => $source->cep,
                'selected_carrier_name' => $source->selected_carrier_name,
                'selected_carrier_price' => $source->selected_carrier_price,
                'selected_carrier_delivery_time' => $source->selected_carrier_delivery_time,
                'carriers_snapshot' => $source->carriers_snapshot,
                'primary_budget_room_id' => null,
                'status' => $source->status,
                'dropshipping_budget' => $source->dropshipping_budget,
            ]);

            foreach ($source->rooms as $room) {
                $newRoom = $budget->rooms()->create([
                    'tenant_id' => $room->tenant_id,
                    'name' => $room->name,
                    'position' => $room->position,
                    'raw_payload' => $room->raw_payload,
                ]);

                foreach ($room->walls as $wall) {
                    $newRoom->walls()->create([
                        'tenant_id' => $wall->tenant_id,
                        'name' => $wall->name,
                        'position' => $wall->position,
                        'width' => $wall->width,
                        'height' => $wall->height,
                        'continue_same_art' => $wall->continue_same_art,
                        'continuations' => $wall->continuations,
                        'collection_model_id' => $wall->collection_model_id,
                        'total_area' => $wall->total_area,
                        'comment_referring_model' => $wall->comment_referring_model,
                        'link_referring_model' => $wall->link_referring_model,
                        'files_referring_model' => $wall->files_referring_model,
                        'collection_referring_model' => $wall->collection_referring_model,
                        'request_layout_referring_model' => $wall->request_layout_referring_model,
                        'strip_height' => $wall->strip_height,
                        'strip_count' => $wall->strip_count,
                    ]);
                }
            }

            $primaryRoomId = $budget->rooms()->orderBy('position')->value('id');
            if ($primaryRoomId) {
                $budget->update(['primary_budget_room_id' => $primaryRoomId]);
            }

            if ($source->dropshipping_budget && $source->dropshippingData) {
                $dd = $source->dropshippingData;
                $this->dropshippingRepository->create(
                    [
                        'name' => $dd->name,
                        'person_type' => $dd->person_type,
                        'cpf_cnpj' => $dd->cpf_cnpj,
                        'IE' => $dd->IE,
                        'email' => $dd->email,
                        'phone' => $dd->phone,
                        'cep' => $dd->cep,
                        'uf' => $dd->uf,
                        'state' => $dd->state,
                        'city' => $dd->city,
                        'neighborhood' => $dd->neighborhood,
                        'public_space' => $dd->public_space,
                        'number' => $dd->number,
                        'complement' => $dd->complement,
                    ],
                    $budget->id,
                    null,
                    (int) ($dd->dealer_id ?: Auth::id())
                );
            }

            return $budget->fresh(['rooms.walls.collectionModel', 'dropshippingData']);
        });
    }
}
