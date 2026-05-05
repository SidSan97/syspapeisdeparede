<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateAvatarAction
{
    public function execute(User $user, string $base64Image): string
    {
        // Valida base64
        if (! preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            throw new \InvalidArgumentException('Formato de imagem inválido.');
        }

        $data = substr($base64Image, strpos($base64Image, ',') + 1);
        $type = strtolower($type[1]);

        $data = base64_decode($data);
        $filename = 'avatar_'.Str::random(10).'.'.$type;
        $path = 'avatars/'.$filename;

        // Remove avatar antigo
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        Storage::disk('public')->put($path, $data);

        $user->update(['avatar' => $path]);

        return Storage::url($path);
    }
}
