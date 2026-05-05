<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Arr;

class UpdateUserAction
{
    public function execute(User $user, array $data, string $role): User
    {
        $newBalance = isset($data['wallet_balance']) ? (float) $data['wallet_balance'] : null;

        $user->update(Arr::except($data, 'wallet_balance'));

        $user->syncRoles([$role]);

        if ($newBalance !== null) {
            $wallet = $user->wallet;
            $amount = $newBalance - (float) $wallet->balance;
            $wallet->update(['balance' => $newBalance]);
            $wallet->transactions()->create([
                'amount' => $amount,
                'balance_after' => $newBalance,
                'type' => 'manual',
                'description' => 'Ajuste de saldo',
            ]);
        }

        return $user;
    }
}
