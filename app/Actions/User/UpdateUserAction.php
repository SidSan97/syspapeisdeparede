<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Arr;

class UpdateUserAction
{
    public function execute(User $user, array $data, string $role): User
    {
        $newBalance = isset($data['wallet_balance']) ? (float) $data['wallet_balance'] : null;

        $data = Arr::except($data, ['wallet_balance', 'password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

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
