<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

// use Spatie\Activitylog\Models\Activity;

class UserController extends BaseController
{
    protected $user;

    public function __construct(User $user)
    {
        $this->middleware('auth:api');
        $this->user = $user;
    }

    public function index()
    {
        $authUser = auth()->user();

        $users = $this->user->latest()->paginate(100);

        return $this->sendResponse($users, 'Lista de usuários');
    }

    public function list()
    {
        $authUser = auth()->user();

        $users = $this->user->latest()->limit(25)->get();

        return $this->sendResponse($users, 'Lista de usuários');
    }

    public function search(Request $request)
    {
        $authUser = auth()->user();

        $users = $this->user->query();

        if ($request->filled('name')) {
            $users = $users->where('name', 'like', "%{$request->name}%");
        }

        if ($request->filled('type')) {
            $users = $users->role($request->type);
        }

        if ($request->filled('user_type_id')) {
            $users = $users->where('user_type_id', $request->user_type_id);
        }

        $users = $users->paginate(100);

        return $this->sendResponse($users, 'Lista de usuários');
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        $user = $this->user->create($data);

        if (!empty($request->role)) {
            $user->syncRoles($request->role);
        }

        $this->handleUserPermissions($user, $request->all());

        return $this->sendResponse($user, 'Usuário adicionado com sucesso');
    }

    public function show($id)
    {
        $user = $this->user->with(['roles:id,name', 'permissions'])->findOrFail($id);

        return $this->sendResponse($user, 'Dados do usuário');
    }

    public function update($id, UserRequest $request)
    {
        $user = $this->user->findOrFail($id);

        $this->authorize('update', $user);

        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if (!empty($data['role'])) {
            $user->syncRoles($data['role']);
        }

        $this->handleUserPermissions($user, $data);

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
