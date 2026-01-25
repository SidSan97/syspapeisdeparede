<?php

namespace App\Http\Controllers\API\V1;

use App\Models\TypeUser;
use Illuminate\Http\Request;

class TypeUserController extends BaseController
{
    protected $typeUser;

    public function __construct(TypeUser $typeUser)
    {
        $this->middleware('auth:api');
        $this->typeUser = $typeUser;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $typeUsers = $this->typeUser->latest()->paginate();

        return $this->sendResponse($typeUsers, 'Lista de tipos de usuários');
    }

    /**
     * Display a listing of the resource without pagination.
     */
    public function list()
    {
        $typeUsers = $this->typeUser->orderBy('name')->get();

        return $this->sendResponse($typeUsers, 'Lista de tipos de usuários');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:type_users,name',
        ]);

        $typeUser = $this->typeUser->create([
            'name' => $request->name,
        ]);

        return $this->sendResponse($typeUser, 'Tipo de usuário criado com sucesso');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $typeUser = $this->typeUser->findOrFail($id);

        return $this->sendResponse($typeUser, 'Dados do tipo de usuário');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $typeUser = $this->typeUser->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:type_users,name,' . $id,
        ]);

        $typeUser->update([
            'name' => $request->name,
        ]);

        return $this->sendResponse($typeUser, 'Tipo de usuário atualizado com sucesso');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $typeUser = $this->typeUser->findOrFail($id);

        $typeUser->delete();

        return $this->sendResponse($typeUser, 'Tipo de usuário excluído com sucesso');
    }
}
