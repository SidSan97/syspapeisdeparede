<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
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
        $query = $this->user->with('userType');

        // Filtro por busca (nome ou email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return $this->sendResponse($users, 'Lista de usuários');
    }
   

    public function list()
    {
        $authUser = auth()->user();

        $users = $this->user->latest()->limit(25)->get();

        return $this->sendResponse($users, 'Lista de usuários');
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_dropshipping' => 0,
            'user_type_id' => $request->user_type_id,
        ];

        $user = $this->user->create($data);

        // Se não veio role mas veio user_type_id, converter para role
        $role = $request->role;
        if (empty($role) && !empty($request->user_type_id)) {
            $role = $this->userType->find((int) $request->user_type_id)->name;
        }

        // Atribuir role ao usuário
        if (!empty($role)) {
            $user->syncRoles($role);
            $user->refresh();
        }

        // Determinar is_dropshipping baseado na role
        if ($role === 'reseller' || $user->hasRole('reseller')) {
            $user->is_dropshipping = (int) ($request->is_dropshipping ?? 0);
            $user->save();
        }

        $this->handleUserPermissions($user, $request->all());

        return $this->sendResponse($user, 'Usuário adicionado com sucesso');
    }

    public function show($id)
    {
        $user = $this->user->with(['roles:id,name', 'permissions', 'userType'])->findOrFail($id);

        return $this->sendResponse($user, 'Dados do usuário');
    }

    public function update($id, UserRequest $request)
    {
        $user = $this->user->findOrFail($id);

        $this->authorize('update', $user);

        $data = $request->validated();

        // Remover password se estiver vazio
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        unset($data['password_confirmation']);
        $role = $data['role'] ?? null;
        unset($data['role']);

        if (empty($role) && !empty($data['user_type_id'])) {
            $role = $this->userType->find((int) $data['user_type_id'])->name;
        }

        // Atualizar role se necessário
        if (!empty($role)) {
            $user->syncRoles($role);
            $user->refresh();
        }

        // Determinar is_dropshipping baseado na role (atual ou nova)
        $isReseller = $role === 'reseller' || $user->hasRole('reseller');
        
        if ($isReseller) {
            // Usar o valor enviado no request, ou do data validado, ou 0 como padrão
            $data['is_dropshipping'] = (int) ($request->input('is_dropshipping', $data['is_dropshipping'] ?? 0));
        } else {
            $data['is_dropshipping'] = 0;
        }

        $user->update($data);

        $this->handleUserPermissions($user, $request->all());

        return $this->sendResponse($user, 'Dados do usuário atualizados com sucesso');
    }

    public function destroy($id)
    {
        $user = $this->user->findOrFail($id);

        $this->authorize('delete', $user);

        $user->delete();

        return $this->sendResponse($user, 'Usuário excluído com sucesso');
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
