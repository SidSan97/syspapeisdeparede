<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\ChangePasswordRequest;
use App\Http\Requests\Users\ProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();

        return $this->sendResponse($user, 'Perfil do usuário');
    }

    public function update(ProfileRequest $request)
    {
        $user = auth('api')->user();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return $this->sendResponse($user, 'Perfil atualizado com sucesso');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = auth('api')->user();

        User::find($user->id)->update(['password' => Hash::make($request->new_password)]);

        return $this->sendResponse([], 'Senha atualizada com sucesso');
    }

    public function uploadAvatar(Request $request)
    {
        $user = auth('api')->user();

        $data = $request->input('image');
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc.

            $data = base64_decode($data);
            $filename = 'avatar_'.Str::random(10).'.'.$type;
            $path = 'avatars/'.$filename;

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            Storage::disk('public')->put($path, $data);

            $user->update(['avatar' => $path]);

            return $this->sendResponse([
                'success' => true,
                'url' => Storage::url($path),
            ], 'Imagem carregada com sucesso.');
        }

        return $this->sendError('Algo deu errado ao carregar a imagem.');
    }
}
