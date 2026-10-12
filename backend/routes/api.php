<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProgramBudayaController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\DivisiController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Models\FokusArea;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])
    ->name('login');


/*
|--------------------------------------------------------------------------
| Protected API
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', DashboardController::class);


    /*
    |--------------------------------------------------------------------------
    | Program Budaya
    |--------------------------------------------------------------------------
    */

    Route::get('/program-budaya', [ProgramBudayaController::class, 'index'])
        ->middleware('permission:view program budaya');

    Route::get('/program-budaya/{id}', [ProgramBudayaController::class, 'show'])
        ->middleware('permission:view program budaya');

    Route::post('/program-budaya', [ProgramBudayaController::class, 'store'])
        ->middleware('permission:manage program budaya');

    Route::put('/program-budaya/{id}', [ProgramBudayaController::class, 'update'])
        ->middleware('permission:manage program budaya');

    Route::delete('/program-budaya/{id}', [ProgramBudayaController::class, 'destroy'])
        ->middleware('permission:manage program budaya');

    Route::get('/laporan', [LaporanController::class, 'index'])
    ->middleware('permission:view laporan');

    Route::get('/laporan/export', [LaporanController::class, 'export'])
        ->middleware('permission:export laporan');

    Route::get('/laporan/{id}', [LaporanController::class, 'show'])
        ->middleware('permission:view laporan');

    Route::post('/laporan', [LaporanController::class, 'store'])
        ->middleware('permission:manage laporan');

    Route::put('/laporan/{id}', [LaporanController::class, 'update'])
        ->middleware('permission:manage laporan');

    Route::delete('/laporan/{id}', [LaporanController::class, 'destroy'])
        ->middleware('permission:delete laporan');

    Route::post('/laporan/{id}/submit', [LaporanController::class, 'submit'])
        ->middleware('permission:manage laporan');

    Route::post('/laporan/{id}/approve', [LaporanController::class, 'approve'])
        ->middleware('permission:approval laporan');

    Route::post('/laporan/{id}/reject', [LaporanController::class, 'reject'])
        ->middleware('permission:approval laporan');
    
    Route::get('/fokus-area', function () {
        return response()->json([
            'message' => 'Data Fokus Area berhasil diambil.',
            'data' => FokusArea::orderBy('id')->get(),
        ]);
    })->middleware('permission:view laporan|manage laporan');

    Route::get('/divisi', [DivisiController::class, 'index'])
    ->middleware('permission:manage divisi|manage user');

    Route::get('/divisi/{id}', [DivisiController::class, 'show'])
        ->middleware('permission:manage divisi');

    Route::post('/divisi', [DivisiController::class, 'store'])
        ->middleware('permission:manage divisi');

    Route::put('/divisi/{id}', [DivisiController::class, 'update'])
        ->middleware('permission:manage divisi');

    Route::delete('/divisi/{id}', [DivisiController::class, 'destroy'])
        ->middleware('permission:manage divisi');

    Route::get('/permission', [PermissionController::class, 'index'])
        ->middleware('permission:manage permission');

    Route::get('/permission/{id}', [PermissionController::class, 'show'])
        ->middleware('permission:manage permission');

    Route::post('/permission', [PermissionController::class, 'store'])
        ->middleware('permission:manage permission');

    Route::put('/permission/{id}', [PermissionController::class, 'update'])
        ->middleware('permission:manage permission');

    Route::delete('/permission/{id}', [PermissionController::class, 'destroy'])
        ->middleware('permission:manage permission');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:manage roles|manage user');

    Route::get('/roles/{id}', [RoleController::class, 'show'])
        ->middleware('permission:manage roles');

    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:manage roles');

    Route::put('/roles/{id}', [RoleController::class, 'update'])
        ->middleware('permission:manage roles');

    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
        ->middleware('permission:manage roles');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:manage user');

    Route::get('/users/{id}', [UserController::class, 'show'])
        ->middleware('permission:manage user');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:manage user');

    Route::put('/users/{id}', [UserController::class, 'update'])
        ->middleware('permission:manage user');

    Route::delete('/users/{id}', [UserController::class, 'destroy'])
        ->middleware('permission:manage user');
});