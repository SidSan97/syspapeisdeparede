<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'version' => config('app.version')
        ]);
    }
}
