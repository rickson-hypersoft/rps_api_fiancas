<?php

declare(strict_types = 1);

use App\Http\Controllers\Assets\AssetsController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Financial\FinancialAccountController;
use App\Http\Controllers\Financial\FinancialCategoryController;
use App\Http\Controllers\Financial\FinancialMoviController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\Propostal\PropostalController;
use App\Http\Controllers\PropostalPayments\PaymentsController;
use App\Http\Controllers\RealEstateSectorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::get('/teste', function () {
    echo "Teste";
});

Route::middleware(['api.auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/realEstateSector/{user}', [UserController::class, 'indexUserRealEstateSector']);
    Route::get('/users/{user}', [UserController::class, 'find']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/companies/{company}', [CompanyController::class, 'update']);

    Route::get('/realestatesector', [RealEstateSectorController::class, 'index']);
    Route::get('/realestatesector/listAll', [RealEstateSectorController::class, 'listAll']);
    Route::get('/realestatesector/{realestate}', [RealEstateSectorController::class, 'find']);
    Route::post('/realestatesector', [RealEstateSectorController::class, 'store']);
    Route::put('/realestatesector/{realestatesector}', [RealEstateSectorController::class, 'update']);
    Route::delete('/realestatesector/{realestatesector}', [RealEstateSectorController::class, 'destroy']);

    Route::get('/realestatesectorsetup/{realestatesector}', [RealEstateSectorController::class, 'findSetup']);
    Route::post('/realestatesectorsetup/{realestatesector}', [RealEstateSectorController::class, 'storeSetup']);
    Route::put('/realestatesectorsetup/{realestatesector}', [RealEstateSectorController::class, 'updateSetup']);

    Route::get('/financial/financial_account/{id}', [FinancialAccountController::class, 'index']);
    Route::get('/financial/{financial_account}/financial_account/', [FinancialAccountController::class, 'find']);
    Route::post('/financial/financial_account/', [FinancialAccountController::class, 'store']);
    Route::put('/financial/financial_account/{financial_account}', [FinancialAccountController::class, 'update']);
    Route::delete('/financial/financial_account/{financial_account}', [FinancialAccountController::class, 'destroy']);

    Route::get('/financial/financial_category/{id}', [FinancialCategoryController::class, 'index']);
    Route::get('/financial/{financial_category}/financial_category', [FinancialCategoryController::class, 'find']);
    Route::post('/financial/financial_category', [FinancialCategoryController::class, 'store']);
    Route::put('/financial/financial_category/{financial_category}', [FinancialCategoryController::class, 'update']);
    Route::delete('/financial/financial_category/{financial_category}', [FinancialCategoryController::class, 'destroy']);

    Route::get('/financial/financial_movi', [FinancialMoviController::class, 'index']);
    Route::get('/financial/financial_movi/{financial_movi}', [FinancialMoviController::class, 'find']);
    Route::post('/financial/financial_movi', [FinancialMoviController::class, 'store']);
    Route::put('/financial/financial_movi/{financial_movi}', [FinancialMoviController::class, 'update']);

    Route::get('/financial/attachment', [AttachmentController::class, 'index']);
    Route::get('/financial/attachment/exists', [AttachmentController::class, 'exists']);
    Route::get('/financial/attachment/{attachment}', [AttachmentController::class, 'find']);
    Route::post('/financial/attachment', [AttachmentController::class, 'store']);
    Route::put('/financial/attachment/{attachment}', [AttachmentController::class, 'update']);

    // Propostas
    Route::get('/propostals/{idRealEstateSector}', [PropostalController::class, 'index']);
    Route::get('/propostal/{propostal}', [PropostalController::class, 'find']);
    Route::post('/propostal/create', [PropostalController::class, 'store']);
    Route::post('/propostal/canceled/{id}', [PropostalController::class, 'canceled']);
    Route::post('/propostal/hash/{id}', [PropostalController::class, 'hashLink']);

    // Histórico
    Route::post('/history/create', [HistoryController::class, 'store']);
    Route::get('/histories/{id_movi}', [HistoryController::class, 'index']);

    // Contratos
    Route::get('/assets/active/{link_hash}', [AssetsController::class, 'active']);
    Route::get('/assets/faceId/{link_hash}', [AssetsController::class, 'faceId']);
    Route::post('/assets/checkout/{link_hash}', [AssetsController::class, 'checkout']);

    // Pagamentos
    Route::get('/propostal/payment/{id_movi}', [PaymentsController::class, 'index']);
    Route::post('/propostal/payment/{id_movi}', [PaymentsController::class, 'store'])->name('propostal_payment.store');
});
