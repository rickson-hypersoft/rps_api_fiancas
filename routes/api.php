<?php

declare(strict_types = 1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RealEstateSectorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware(['api.auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/companies/{company}', [CompanyController::class, 'update']);

    Route::get('/realestatesector', [RealEstateSectorController::class, 'index']);
    Route::post('/realestatesector', [RealEstateSectorController::class, 'store']);
    Route::put('/realestatesector/{realestatesector}', [RealEstateSectorController::class, 'update']);

    Route::post('/realestatesectorsetup/{realestatesector}', [RealEstateSectorController::class, 'storeSetup']);
    Route::put('/realestatesectorsetup/{realestatesector}', [RealEstateSectorController::class, 'updateSetup']);
});
