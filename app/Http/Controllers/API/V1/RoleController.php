<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
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
        $roles = $this->repository->get(['id', 'name']);

        return $this->sendResponse($roles, 'Lista de níveis de acesso');
    }
}
