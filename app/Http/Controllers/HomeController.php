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

        $scriptVariables = [
            'appName' => config('app.name'),
            'user' => auth()->user(),
            'roles' => auth()->user() ? auth()->user()->getRoleNames() : [],
            'permissions' => auth()->user() ? auth()->user()->getAllPermissions()->pluck('name') : [],
            'assetUrl' => asset(''),
        ];

        return view('application', [
            'scriptVariables' => $scriptVariables
        ]);
    }
}
