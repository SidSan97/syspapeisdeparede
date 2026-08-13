<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteOrderBudgetRequest;
use App\Models\OrderBudget;
use App\Repositories\OrderBudgetRepository;
use App\Services\LayoutCardHistoryService;
use App\Services\OrderBudgetActivityService;
use BeyondCode\Comments\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class OrderBudgetController extends Controller
{
    public function __construct(
        protected OrderBudgetRepository $orderBudgetRepository,
        protected LayoutCardHistoryService $layoutCardHistoryService,
        protected OrderBudgetActivityService $orderBudgetActivityService,
    ) {}

    public function updateDescription(Request $request, int $orderBudgetId): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'type_page' => ['nullable', 'string', 'in:layout,product'],
        ]);

        $user = $request->user();
        $typePage = $validated['type_page'] ?? null;
        $orderBudget = $this->orderBudgetRepository->updateOrderBudgetDescription(
            $orderBudgetId,
            $validated['description'],
            $user,
            $typePage
        );

        return response()->json($orderBudget);
    }

    public function addComment(Request $request, int $orderBudgetId): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        $orderBudget = OrderBudget::findOrFail($orderBudgetId);
        $user = $request->user();

        $comment = $orderBudget->commentAsUser($user, $validated['comment']);

        return response()->json([
            'id' => $comment->id,
            'comment' => $comment->comment,
            'user_name' => $user->name,
            'user_id' => $user->id,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ], 201);
    }

    public function updateComment(Request $request, int $orderBudgetId, int $commentId): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        $orderBudget = OrderBudget::findOrFail($orderBudgetId);
        $comment = Comment::findOrFail($commentId);

        // Verificar se o comentário pertence ao order_budget
        if ($comment->commentable_id !== $orderBudget->id || $comment->commentable_type !== OrderBudget::class) {
            abort(404, 'Comentário não encontrado');
        }

        $user = $request->user();
        $comment->update([
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'id' => $comment->id,
            'comment' => $comment->comment,
            'user_name' => $user->name,
            'user_id' => $user->id,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ]);
    }

    public function deleteComment(Request $request, int $orderBudgetId, int $commentId)
    {
        $orderBudget = OrderBudget::findOrFail($orderBudgetId);
        $comment = Comment::findOrFail($commentId);

        // Verificar se o comentário pertence ao order_budget
        if ($comment->commentable_id !== $orderBudget->id || $comment->commentable_type !== OrderBudget::class) {
            abort(404, 'Comentário não encontrado');
        }

        $comment->delete();

        return response()->noContent();
    }

    public function addMember(Request $request, int $orderBudgetId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type_page' => ['nullable', 'string', 'in:layout,product'],
        ]);

        $user = $request->user();
        $typePage = $validated['type_page'] ?? null;
        $data = $this->orderBudgetRepository->addMember($orderBudgetId, $validated['user_id'], $user, $typePage);

        return response()->json($data, 201);
    }

    public function removeMember(Request $request, int $orderBudgetId, ?int $memberId = null): JsonResponse
    {
        $input = [
            'user_id' => $memberId ?? $request->input('user_id'),
            'type_page' => $request->input('type_page'),
        ];

        $validated = Validator::make($input, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type_page' => ['nullable', 'string', 'in:layout,product'],
        ])->validate();

        $user = $request->user();
        $typePage = $validated['type_page'] ?? null;
        $data = $this->orderBudgetRepository->removeMember($orderBudgetId, $validated['user_id'], $user, $typePage);

        return response()->json($data);
    }

    /**
     * Inicia (ou retoma) o cronômetro do Power-Up Activity para o card.
     */
    public function startActivity(Request $request, OrderBudget $orderBudget): JsonResponse
    {
        if ($response = $this->ensureNotCompleted($orderBudget)) {
            return $response;
        }

        if (! $orderBudget->activity_running_since) {
            $orderBudget->forceFill(['activity_running_since' => now()]);
            $orderBudget->save();

            if ($user = $request->user()) {
                $this->layoutCardHistoryService->logActivityStarted($orderBudget->id, $user, 'layout');
            }
        }

        return response()->json($this->activityPayload($orderBudget));
    }

    /**
     * Pausa o cronômetro acumulando o tempo da sessão atual.
     */
    public function pauseActivity(Request $request, OrderBudget $orderBudget): JsonResponse
    {
        if ($response = $this->ensureNotCompleted($orderBudget)) {
            return $response;
        }

        if (! $orderBudget->activity_running_since) {
            return response()->json($this->activityPayload($orderBudget));
        }

        $session = $this->orderBudgetActivityService->recordClosedSession($orderBudget);

        if ($session && $user = $request->user()) {
            $this->layoutCardHistoryService->logActivityPaused(
                $orderBudget->id,
                $user,
                (int) $session->duration_seconds,
                'layout',
            );
        }

        $orderBudget->forceFill($this->accumulateRunningSession($orderBudget));
        $orderBudget->save();

        return response()->json($this->activityPayload($orderBudget));
    }

    /**
     * Marca o card como concluído na página de Layout. Pausa automaticamente
     * o cronômetro caso esteja em execução.
     */
    public function complete(CompleteOrderBudgetRequest $request, OrderBudget $orderBudget): JsonResponse
    {
        if ($orderBudget->completed_at) {
            return response()->json($this->activityPayload($orderBudget));
        }

        $attributes = ['completed_at' => now()];

        if ($orderBudget->activity_running_since) {
            $this->orderBudgetActivityService->recordClosedSession($orderBudget);
            $attributes = array_merge($attributes, $this->accumulateRunningSession($orderBudget));
        }

        $orderBudget->forceFill($attributes);
        $orderBudget->save();

        if ($user = $request->user()) {
            $this->layoutCardHistoryService->logCardCompleted($orderBudget->id, $user, 'layout');
        }

        return response()->json($this->activityPayload($orderBudget));
    }

    /**
     * Reabre o card na página de Layout (remove a marcação de concluído).
     */
    public function reopen(Request $request, OrderBudget $orderBudget): JsonResponse
    {
        if (! $orderBudget->completed_at) {
            return response()->json($this->activityPayload($orderBudget));
        }

        $orderBudget->forceFill([
            'completed_at' => null,
        ]);
        $orderBudget->save();

        if ($user = $request->user()) {
            $this->layoutCardHistoryService->logCardReopened($orderBudget->id, $user, 'layout');
        }

        return response()->json($this->activityPayload($orderBudget));
    }

    /**
     * Retorna 422 caso o card já esteja concluído, ou null para seguir o fluxo.
     */
    protected function ensureNotCompleted(OrderBudget $orderBudget): ?JsonResponse
    {
        if (! $orderBudget->completed_at) {
            return null;
        }

        return response()->json(
            ['message' => 'Este card já foi concluído e não aceita alterações no temporizador.'],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Calcula os atributos para fechar a sessão atual do timer (acumular segundos e zerar marcador).
     *
     * @return array{activity_elapsed_seconds: int, activity_running_since: null}
     */
    protected function accumulateRunningSession(OrderBudget $orderBudget): array
    {
        $sessionSeconds = max(0, now()->diffInSeconds($orderBudget->activity_running_since, true));

        return [
            'activity_elapsed_seconds' => (int) $orderBudget->activity_elapsed_seconds + $sessionSeconds,
            'activity_running_since' => null,
        ];
    }

    protected function activityPayload(OrderBudget $orderBudget): array
    {
        $orderBudget->refresh();
        $orderBudget->load('activitySessions');

        return [
            'activity_running_since' => $orderBudget->activity_running_since?->toIso8601String(),
            'activity_elapsed_seconds' => (int) $orderBudget->activity_elapsed_seconds,
            'activity_total_seconds' => (int) $orderBudget->activity_total_seconds,
            'activity_is_running' => (bool) $orderBudget->activity_is_running,
            'activity_sessions' => $this->orderBudgetActivityService->sessionsPayload($orderBudget),
            'completed_at' => $orderBudget->completed_at?->toIso8601String(),
            'is_completed' => (bool) $orderBudget->is_completed,
            'server_time' => now()->toIso8601String(),
        ];
    }
}
