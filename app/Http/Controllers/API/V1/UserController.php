<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\TypeUser;

// use Spatie\Activitylog\Models\Activity;

class UserController extends BaseController
{
    protected $user;
    protected $userType;

    public function __construct(User $user, TypeUser $userType)
    {
        $this->middleware('auth:api');
        $this->user = $user;
        $this->userType = $userType;
    }

    public function index(Request $request)
    {
        $query = User::with('roles:id,name')->search($request->input('search'));

        // Filtro por role (papel)
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->latest()->paginate();

        return UserResource::collection($users);
    }

    public function list()
    {
        $users = User::with('roles:id,name')->get();

        return $this->sendResponse($users, 'Lista de usuários');
    }

    /**
     * COMO OS ADMINS PODEM FAZER OPERAÇÕES DE REVENDEDORES,
     * ESTARÃO NOS FILTROS
     */
    public function listResellers()
    {
        $users = User::with('roles')->role(['reseller', 'admin'])->get();

        return UserResource::collection($users);
    }

    public function listDesigners()
    {
        $users = User::with('roles')->role(['designer'])->get();

        return UserResource::collection($users);
    }

    public function store(UserRequest $request)
    {
        $validated = $request->validated();
        $role = $validated['role'];

        // Remover 'role' do array antes de criar o usuário
        unset($validated['role']);

        $user = User::create($validated);

        // Atribuir role ao usuário
        $user->assignRole($role);

        // Se for revendedor, atualizar is_dropshipping
        if ($role === 'reseller') {
            $user->update(['is_dropshipping' => $request->is_dropshipping ?? 0]);
        }

        return $this->sendResponse($user, 'Usuário criado com sucesso');
    }

    public function show(User $user)
    {
        $user->load(['roles:id,name', 'permissions']);

        return new UserResource($user);
    }

    public function update(User $user, UserRequest $request)
    {
        $validated = $request->validated();
        $role = $validated['role'];

        // Remover 'role' do array antes de atualizar o usuário
        unset($validated['role']);

        $user->update($validated);

        // Sincronizar roles (remove todas e adiciona a nova)
        $user->syncRoles([$role]);

        // Se for revendedor, atualizar is_dropshipping
        if ($role === 'reseller') {
            $user->update(['is_dropshipping' => $request->is_dropshipping ?? 0]);
        } else {
            // Se não for revendedor, garantir que is_dropshipping seja 0
            $user->update(['is_dropshipping' => 0]);
        }

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->sendResponse(null, 'Usuário removido.');
    }
}
