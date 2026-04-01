<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\V1\WalletTransactionResource;
use App\Models\UserWallet;
use Illuminate\Http\Request;

class WalletController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function show(Request $request)
    {
        $user = auth('api')->user();

        $wallet = UserWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate();

        return WalletTransactionResource::collection($transactions)
            ->additional(['balance' => (string) $wallet->balance]);
    }
}
