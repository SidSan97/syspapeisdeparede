<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\LayoutService;

class OrderController extends Controller
{
    protected $repository;
    protected $layoutService;
    public function __construct(OrderRepository $repository, LayoutService $layoutService)
    {
        $this->middleware('auth:api');
        $this->repository = $repository;
        $this->layoutService = $layoutService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $perPage = $perPage > 0 ? $perPage : 15;

            $orders = $this->repository->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => OrderResource::collection($orders),
                    'meta' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'last_page' => $orders->lastPage(),
                    ],
                ],
                'message' => 'Lista de pedidos recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos',
            ], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $orders = $this->repository->all();
            $data = OrderResource::collection($orders)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de pedidos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos',
            ], 500);
        }
    }

    public function layouts(): JsonResponse
    {
        try {
            $orderBudgets = $this->repository->getLayoutsForApprove();
            $data = $this->layoutService->transformLayouts($orderBudgets, 'layout');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de layouts',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar layouts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'user_id' => 'nullable|exists:users,id',
                'tenant_id' => 'nullable|exists:users,id',
                'primary_budget_room_id' => 'nullable|exists:budget_rooms,id',
                'total_area' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'total_amount_installments' => 'nullable|numeric|min:0',
                'delivery_time' => 'nullable|integer|min:0',
                'payment_method' => 'nullable|string',
                'installment_limit' => 'nullable|integer|min:1',
                'installments' => 'nullable|integer|min:1',
                'cep' => 'nullable|string|max:9',
                'selected_carrier_name' => 'nullable|string',
                'selected_carrier_price' => 'nullable|numeric|min:0',
                'selected_carrier_delivery_time' => 'nullable|integer|min:0',
                'carriers_snapshot' => 'nullable|array',
                'status' => 'nullable|string|max:50',
                'payment_file' => 'nullable|string',
                'comment_referring_model' => 'nullable|string|max:500',
                'link_referring_model' => 'nullable|string|max:150',
                'files_referring_model' => 'nullable|array',
                'collection_referring_model' => 'nullable|string',
                'dropshipping_budget' => 'nullable|boolean',
            ]);

            $order = $this->repository->create($validated);
            $transformed = new OrderResource($order);

            return response()->json([
                'success' => true,
                'data' => $transformed->toArray($request),
                'message' => 'Pedido criado com sucesso',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'data' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $transformed = new OrderResource($order);

            return response()->json([
                'success' => true,
                'data' => $transformed->toArray(request()),
                'message' => 'Pedido recuperado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar pedido',
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'user_id' => 'sometimes|nullable|exists:users,id',
                'tenant_id' => 'sometimes|nullable|exists:users,id',
                'primary_budget_room_id' => 'sometimes|nullable|exists:budget_rooms,id',
                'total_area' => 'sometimes|nullable|numeric|min:0',
                'total_amount' => 'sometimes|nullable|numeric|min:0',
                'total_amount_installments' => 'sometimes|nullable|numeric|min:0',
                'delivery_time' => 'sometimes|nullable|integer|min:0',
                'payment_method' => 'sometimes|nullable|string',
                'installment_limit' => 'sometimes|nullable|integer|min:1',
                'installments' => 'sometimes|nullable|integer|min:1',
                'cep' => 'sometimes|nullable|string|max:9',
                'selected_carrier_name' => 'sometimes|nullable|string',
                'selected_carrier_price' => 'sometimes|nullable|numeric|min:0',
                'selected_carrier_delivery_time' => 'sometimes|nullable|integer|min:0',
                'carriers_snapshot' => 'sometimes|nullable|array',
                'status' => 'sometimes|nullable|string|max:50',
                'payment_file' => 'sometimes|nullable|string',
                'comment_referring_model' => 'sometimes|nullable|string|max:500',
                'link_referring_model' => 'sometimes|nullable|string|max:150',
                'files_referring_model' => 'sometimes|nullable|array',
                'collection_referring_model' => 'sometimes|nullable|string',
                'dropshipping_budget' => 'sometimes|nullable|boolean',
            ]);

            $order = $this->repository->update($order, $validated);
            $transformed = new OrderResource($order);

            return response()->json([
                'success' => true,
                'data' => $transformed->toArray($request),
                'message' => 'Pedido atualizado com sucesso',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'data' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar pedido',
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $this->repository->delete($order);

            return response()->json([
                'success' => true,
                'message' => 'Pedido excluído com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir pedido',
            ], 500);
        }
    }

    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $orders = $this->repository->getByStatus($status);
            $data = OrderResource::collection($orders)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => "Lista de pedidos com status: {$status}",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos por status',
            ], 500);
        }
    }
}

