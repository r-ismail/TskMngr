<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes serve the Vue frontend and any external API clients.
| Authentication is handled with Laravel Sanctum personal access tokens.
|
*/

Route::post('login', [AuthController::class, 'login'])->name('api.login');
Route::post('register', [AuthController::class, 'register'])->name('api.register');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user'])->name('api.user');
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::apiResource('tasks', TaskController::class)->names('api.tasks');
});
