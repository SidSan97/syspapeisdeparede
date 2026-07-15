<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Arr;

class CreateUserAction
{
    public function execute(array $data, string $role): User
    {
        $initialCredit = (float) ($data['wallet_balance'] ?? 0);

        $user = User::create(Arr::except($data, 'wallet_balance'));

        $user->assignRole($role);

        if ($initialCredit > 0) {
            $wallet = $user->wallet;
            $wallet->update(['balance' => $initialCredit]);
            $wallet->transactions()->create([
                'amount' => $initialCredit,
                'balance_after' => $initialCredit,
                'type' => 'manual',
                'description' => 'Crédito inicial',
            ]);
        }

        return $user;
    }
}
