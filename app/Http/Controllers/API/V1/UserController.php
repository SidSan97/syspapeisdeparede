<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\User\CreateUserAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request)
    {
        $users = User::with('roles:id,name')
            ->search($request->search)
            ->when($request->filled('role'), fn ($query) => $query->role($request->role))
            ->latest()
            ->paginate();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action)
    {
        $user = $action->execute(
            $request->safe()->except('role'),
            $request->validated('role'),
        );

        return new UserResource($user);
    }

    public function show(User $user)
    {
        $user->load(['roles:id,name', 'permissions', 'wallet']);

        return new UserResource($user);
    }

    public function update(User $user, UpdateUserRequest $request, UpdateUserAction $action)
    {
        $user = $action->execute(
            $user,
            $request->safe()->except('role'),
            $request->validated('role'),
        );

        $user->load('wallet');

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }

    public function list(Request $request)
    {
        $roles = $request->query('role');

        $query = User::with('roles');

        if ($roles) {
            $roleArray = explode(',', $roles);

            $query->role($roleArray);
        }

        $users = $query->get();

        return UserResource::collection($users);
    }
}
