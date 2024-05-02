<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     $user = App\Models\User::getAllDataUser(1);
//     dd($user);
// });

Route::controller(AuthController::class)->group(function () {
    // Route::post('login/admin', 'login_admin');
    Route::post('auth/register', 'auth_register');
    Route::post('auth/login', 'auth_login');
    Route::post('auth/account-recovery', 'auth_account_recovery');
    Route::post('auth/password-recovery', 'auth_password_recovery');
});

Route::controller(UserController::class)->group(function () {
    Route::post('users/plans', 'user_plan');
});

Route::group(['middleware' => ['auth:api']], function ($router) {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('auth/password-recovery-token', [AuthController::class, 'auth_password_recovery_token']);
});
