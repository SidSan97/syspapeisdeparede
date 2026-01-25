<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    public function __construct(
        protected Role $repository
    ) {
        $this->middleware('auth:api');
    }

    public function list()
    {
        $roles = Cache::remember('roles_list', 86400, function () {
            return $this->repository->select('id', 'name')->orderBy('name')->get();
        });

        return $this->sendResponse($roles, 'Lista de níveis de acesso');
    }

    public static function clearCache()
    {
        Cache::forget('roles_list');
    }
}
