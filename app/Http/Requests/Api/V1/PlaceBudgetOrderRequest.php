<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Budget;
use App\Models\CollectionModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlaceBudgetOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'walls' => ['nullable', 'array'],
            'walls.*.id' => ['required', 'integer', 'exists:budget_walls,id'],
            'walls.*.model' => ['nullable', 'integer', 'exists:collection_models,id'],
            'walls.*.comment_referring_model' => ['nullable', 'string', 'max:500'],
            'walls.*.link_referring_model' => ['nullable', 'string', 'url', 'max:500'],
            'walls.*.files_referring_model' => ['nullable', 'array'],
            'walls.*.files_referring_model.*' => ['nullable', 'string', 'max:2048'],
            'walls.*.collection_referring_model' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Budget|null $budget */
            $budget = $this->route('budget');
            $walls = $this->input('walls', []);
            if (! is_array($walls) || ! $budget) {
                return;
            }

            $budget->loadMissing(['rooms.walls']);
            $allowedWallIds = $budget->rooms
                ->flatMap(fn ($room) => $room->walls->pluck('id'))
                ->map(fn ($id) => (int) $id)
                ->all();

            $modelIds = [];
            foreach ($walls as $wallIndex => $wall) {
                $wallId = isset($wall['id']) ? (int) $wall['id'] : null;
                if ($wallId && ! in_array($wallId, $allowedWallIds, true)) {
                    $validator->errors()->add("walls.$wallIndex.id", 'A parede informada não pertence a este orçamento.');
                }

                if (! empty($wall['model'])) {
                    $modelIds[] = (int) $wall['model'];
                }
            }
            $modelIds = array_values(array_unique($modelIds));
            if (! $modelIds) {
                return;
            }

            $models = CollectionModel::query()->whereIn('id', $modelIds)->get()->keyBy('id');

            foreach ($walls as $wallIndex => $wall) {
                $modelId = isset($wall['model']) ? (int) $wall['model'] : null;
                if (! $modelId || ! $models->has($modelId)) {
                    continue;
                }

                $model = $models->get($modelId);
                $needComment = (bool) ($model->request_comment ?? false);
                $needLink = (bool) ($model->request_link ?? false);
                $needFile = (bool) ($model->request_file ?? false);
                $needCollection = (bool) ($model->request_collection ?? false);

                $base = "walls.$wallIndex";

                if ($needComment && ! trim((string) ($wall['comment_referring_model'] ?? ''))) {
                    $validator->errors()->add("$base.comment_referring_model", 'Descrição do modelo é obrigatória para este modelo.');
                }

                if ($needLink && ! trim((string) ($wall['link_referring_model'] ?? ''))) {
                    $validator->errors()->add("$base.link_referring_model", 'Link de referência é obrigatório para este modelo.');
                }

                if ($needFile) {
                    $files = $wall['files_referring_model'] ?? [];
                    if (! is_array($files) || count(array_filter($files, fn ($v) => trim((string) $v) !== '')) === 0) {
                        $validator->errors()->add("$base.files_referring_model", 'Arquivo(s) de referência são obrigatórios para este modelo.');
                    }
                }

                if ($needCollection && ! trim((string) ($wall['collection_referring_model'] ?? ''))) {
                    $validator->errors()->add("$base.collection_referring_model", 'Seleção de arte da coleção é obrigatória para este modelo.');
                }
            }
        });
    }
}
