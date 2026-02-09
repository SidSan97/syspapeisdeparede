<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request)
    {
        $users = User::with('roles:id,name')
            ->search($request->search)
            ->when($request->filled('role'), fn($query) => $query->role($request->role))
            ->latest()
            ->paginate();

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
        $data = $request->safe()->except('role');

        $user = User::create($data);

        $user->assignRole($request->validated('role'));

        return new UserResource($user);
    }

    public function show(User $user)
    {
        $user->load(['roles:id,name', 'permissions']);

        return new UserResource($user);
    }

    public function update(User $user, UserRequest $request)
    {
        $data = $request->safe()->except('role');
        $role = $request->validated('role');

        $user->update($data);
        $user->syncRoles([$role]);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}
