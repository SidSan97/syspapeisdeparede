<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\Profile\UpdateAvatarAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateAvatarRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return new UserResource($user);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->only(['name', 'email']));

        return new UserResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Senha atualizada com sucesso',
        ]);
    }

    public function updateAvatar(UpdateAvatarRequest $request, UpdateAvatarAction $action): JsonResponse
    {
        $user = $request->user();

        $url = $action->execute($user, $request->input('image'));

        return response()->json([
            'url' => $url,
            'message' => 'Imagem carregada com sucesso.',
        ]);
    }
}
