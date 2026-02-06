<?php

namespace App\Http\Controllers;

use App\Http\Resources\V1\UserResource;
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
        $user->load(['roles', 'permissions']);

        $scriptVariables = [
            'appName' => config('app.name'),
            'user' => new UserResource($user),
            'appUrl' => config('app.url'),
            'baseUrl' => url(''),
            'assetUrl' => asset(''),
        ];

        return view('application', [
            'scriptVariables' => $scriptVariables
        ]);
    }
}
