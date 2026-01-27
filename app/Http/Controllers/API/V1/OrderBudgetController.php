<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderBudget;
use App\Repositories\OrderBudgetRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderBudgetController extends Controller
{
    protected $orderBudgetRepository;

    public function __construct(OrderBudgetRepository $orderBudgetRepository)
    {
        $this->orderBudgetRepository = $orderBudgetRepository;
    }

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
        $comment = \BeyondCode\Comments\Comment::findOrFail($commentId);

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
        $comment = \BeyondCode\Comments\Comment::findOrFail($commentId);

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
}
