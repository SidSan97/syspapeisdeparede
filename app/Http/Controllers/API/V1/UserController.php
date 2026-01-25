<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
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
        $query = $this->user->with('roles:id,name');

        // Filtro por busca (nome ou email)
        $query->search($request->input('search'));

        // Filtro por role (papel)
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->latest()->paginate();

        return $this->sendResponse($users, 'Lista de usuários');
    }

    public function list()
    {
        $users = $this->user->with('roles:id,name')->get();

        return $this->sendResponse($users, 'Lista de usuários');
    }

    /**
     * COMO OS ADMINS PODEM FAZER OPERAÇÕES DE REVENDEDORES,
     * ESTARÃO NOS FILTROS
     */
    public function listResellers()
    {
        $users = $this->user->with('roles')->role(['reseller','admin'])->get();

        return $this->sendResponse($users, 'Lista de revendedores');
    }

    public function listDesigners()
    {
        $users = $this->user->with('roles')->role(['designer'])->get();

        return $this->sendResponse($users, 'Lista de designers');
    }

    public function store(UserRequest $request)
    {
        $validated = $request->validated();
        $role = $validated['role'];
        
        // Remover 'role' do array antes de criar o usuário
        unset($validated['role']);
        
        $user = $this->user->create($validated);

        // Atribuir role ao usuário
        $user->assignRole($role);

        // Se for revendedor, atualizar is_dropshipping
        if ($role === 'reseller') {
            $user->update(['is_dropshipping' => $request->is_dropshipping ?? 0]);
        }

        return $this->sendResponse($user, 'Usuário criado com sucesso');
    }

    public function show($id)
    {
        $user = $this->user->with(['roles:id,name', 'permissions'])->findOrFail($id);

        return $this->sendResponse($user, 'Dados do usuário');
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

        return $this->sendResponse($user, 'Dados do usuário atualizados com sucesso');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->sendResponse(null, 'Usuário removido.');
    }

    protected function handleUserPermissions(User $user, array $data)
    {
        $permissions = [
            'manage_goals_corporative' => 'manage goals corporative',
            'manage_goals_operational' => 'manage goals operational',
        ];

        foreach ($permissions as $field => $permission) {
            if (isset($data[$field])) {
                $data[$field]
                    ? $user->givePermissionTo($permission)
                    : $user->revokePermissionTo($permission);
            }
        }
    }

    public function activity($id)
    {
        // Validate if user exists.
        $user = $this->user->findOrFail($id, ['id']);

        // $activity = Activity::where('causer_id', $id)
        //     ->where('causer_type', User::class)
        //     ->latest()
        //     ->paginate();
        $activity = collect([]);

        return $this->sendResponse($activity, 'Atividades do usuário');
    }
}
