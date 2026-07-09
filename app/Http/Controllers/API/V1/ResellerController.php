<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ResellerResource;
use App\Models\Reseller;
use Illuminate\Http\Request;

class ResellerController extends Controller
{
    /**
     * Search resellers that are still available to be linked to a user
     * (or, when editing, the reseller currently linked to that user).
     */
    public function list(Request $request)
    {
        $search = $request->query('search');
        $currentId = $request->query('current_id');

        $resellers = Reseller::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('fantasy_name', 'like', "%{$search}%")
                        ->orWhere('cnpj', 'like', "%{$search}%");
                });
            })
            ->where(function ($query) use ($currentId) {
                $query->whereDoesntHave('user');

                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return ResellerResource::collection($resellers);
    }
}
