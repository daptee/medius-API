<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/countries', function () {
    $countries = App\Models\Country::with('provinces')->get();
    return response(["data" => $countries]);
});

Route::get('/provinces', function () {
    $provinces = App\Models\Province::with('country')->get();
    return response(["data" => $provinces]);
});

Route::get('/specialties', function () {
    $specialties = App\Models\Specialty::get();
    return response(["data" => $specialties]);
});

Route::get('/users/status', function () {
    $users_status = App\Models\UserStatus::get();
    return response(["data" => $users_status]);
});

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
    Route::post('users/update', [UserController::class, 'update']);
    Route::post('users/profile_picture', [UserController::class, 'profile_picture']);
    
    // PatientController
    Route::post('users/patient', [PatientController::class, 'new_user_patient']);
    Route::post('users/patient/{id}', [PatientController::class, 'update_user_patient']);
    Route::get('users/patients', [PatientController::class, 'get_patients']);
    Route::post('patients/files', [PatientController::class, 'patient_files']);
    Route::post('patients/delete/files', [PatientController::class, 'delete_patient_files']);

    // ProfessionalController
    Route::post('users/profesional', [ProfessionalController::class, 'new_user_profesional']);
    Route::post('users/profesional/{id}', [ProfessionalController::class, 'update_user_profesional']);
    Route::get('users/professionals', [ProfessionalController::class, 'get_professionals']);
});
