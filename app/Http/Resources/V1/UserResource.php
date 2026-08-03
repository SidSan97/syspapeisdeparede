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

            'is_dropshipping' => (bool) $this->is_dropshipping,

            'reseller_id' => $this->reseller_id,
            'reseller' => $this->whenLoaded('reseller', fn () => $this->reseller ? [
                'id' => $this->reseller->id,
                'name' => $this->reseller->name,
                'fantasy_name' => $this->reseller->fantasy_name,
                'cnpj' => $this->reseller->cnpj,
            ] : null),

            'wallet_balance' => $this->whenLoaded('wallet', fn () => (string) $this->wallet->balance),

            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),

            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->getAllPermissions()->pluck('name');
            }),
        ];
    }
}
