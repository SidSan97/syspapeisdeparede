<?php

use App\Http\Controllers\API\V1\{
    ProfileController,
    UserController,
};
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\AppVersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// App version
// ----------------------------------

Route::get('version', AppVersionController::class);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    Log::debug('User:' . serialize($request->user()));
    return $request->user();
});

// Version 1 endpoints
// --------------------------------------

Route::prefix('v1')->group(function () {

    // Profile
    //----------------------------------

    Route::get('profile', [ProfileController::class, 'index']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);

    Route::get('roles/list', [RoleController::class, 'list']);

    // Users
    //----------------------------------

    Route::get('users/search', [UserController::class, 'search']);
    Route::get('users/list', [UserController::class, 'list']);

    Route::apiResources([
        'users'     => UserController::class,
    ]);
});
