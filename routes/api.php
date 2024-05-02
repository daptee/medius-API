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

Route::group(['middleware' => ['auth:api']], function ($router) {
    // AuthController
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('auth/password-recovery-token', [AuthController::class, 'auth_password_recovery_token']);

    // UserController
    Route::post('users/plans', [UserController::class, 'user_plan']);
    Route::post('users/profesional', [UserController::class, 'new_user_profesional']);
    Route::post('users/profesional/{id}', [UserController::class, 'update_user_profesional']);
    Route::post('users/update', [UserController::class, 'update']);
    Route::post('users/profile_picture', [UserController::class, 'profile_picture']);
});
