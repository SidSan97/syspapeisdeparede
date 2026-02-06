<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,

            // FIXME: Remover campo, pois usará somente permissions.
            'user_type_id' => $this->user_type_id,

            'is_dropshipping' => (bool) $this->is_dropshipping,

            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),

            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->getAllPermissions()->pluck('name');
            }),
        ];
    }
}
