<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function __invoke()
    {

        $user = auth()->user();

        $scriptVariables = [
            'appName' => config('app.name'),
            'user' => $user,
            'roles' => $user ? $user->getRoleNames()->toArray() : [],
            'permissions' => $user ? $user->getAllPermissions()->pluck('name')->toArray() : [],
            'direct_permissions' => $user ? $user->getDirectPermissions()->pluck('name')->toArray() : [],
            'assetUrl' => asset(''),
        ];

        return view('application', [
            'scriptVariables' => $scriptVariables
        ]);
    }
}
