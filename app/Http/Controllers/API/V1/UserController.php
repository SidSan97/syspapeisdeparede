<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Models\UserWallet;
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
        $data = $request->safe()->except(['role', 'wallet_balance']);

        $user = User::create($data);

        $user->assignRole($request->validated('role'));

        if ($request->validated('role') === 'reseller') {
            $this->syncResellerWalletBalance($user, (float) $request->input('wallet_balance', 0));
        }

        $user->load('wallet');

        return new UserResource($user);
    }

    public function show(User $user)
    {
        $user->load(['roles:id,name', 'permissions', 'wallet']);

        return new UserResource($user);
    }

    public function update(User $user, UserRequest $request)
    {
        $data = $request->safe()->except(['role', 'wallet_balance']);
        $role = $request->validated('role');

        $user->update($data);
        $user->syncRoles([$role]);

        if ($role === 'reseller') {
            $this->syncResellerWalletBalance($user, (float) $request->input('wallet_balance', 0));
        }

        $user->load('wallet');

        return new UserResource($user);
    }

    protected function syncResellerWalletBalance(User $user, float $balance): void
    {
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $wallet->update([
            'balance' => round(max(0, $balance), 2),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}
