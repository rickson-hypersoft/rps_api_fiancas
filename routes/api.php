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
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Propostal\PropostalController;
use App\Http\Controllers\PropostalPayments\CheckoutController;
use App\Http\Controllers\RealEstateSectorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
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

    Route::get('/financial/financial_movi/{id}', [FinancialMoviController::class, 'index']);
    Route::get('/financial/{financial_movi}/financial_movi', [FinancialMoviController::class, 'find']);
    Route::post('/financial/financial_movi', [FinancialMoviController::class, 'store']);
    Route::put('/financial/financial_movi/{financial_movi}', [FinancialMoviController::class, 'update']);
    Route::delete('/financial/financial_movi/{financial_movi}', [FinancialMoviController::class, 'destroy']);

    Route::get('/attachment', [AttachmentController::class, 'index']);
    Route::get('/attachment/exists', [AttachmentController::class, 'exists']);
    Route::get('/attachment/{attachment}', [AttachmentController::class, 'find']);
    Route::post('/attachment', [AttachmentController::class, 'store']);
    Route::put('/attachment/{attachment}', [AttachmentController::class, 'update']);
    Route::get('/search/attachment', [AttachmentController::class, 'search']);

    // Propostas
    Route::get('/propostals/{idRealEstateSector}', [PropostalController::class, 'index']);
    Route::get('/propostal/{propostal}', [PropostalController::class, 'find']);
    Route::post('/propostal/create', [PropostalController::class, 'store']);
    Route::post('/propostal/canceled/{id}', [PropostalController::class, 'canceled']);
    Route::post('/propostal/hash/{id}', [PropostalController::class, 'hashLink']);
    Route::post('/propostal/editStatus/{id}', [PropostalController::class, 'updateStatus']);

    // Histórico
    Route::post('/history/create', [HistoryController::class, 'store']);
    Route::get('/histories/{id_movi}', [HistoryController::class, 'index']);

    // Contratos
    Route::prefix('assets')->group(function () {
        Route::get('/{idIMobiliaria}', [AssetsController::class, 'index']);
        Route::get('/{idIMobiliaria}/{idContrato}', [AssetsController::class, 'findAsset']);
    });

    // Route::get('/assetsHome/{idImobiliaria}', [AssetsController::class, 'index']);

    Route::get('/activation/{id}/find', [AssetsController::class, 'findAsset']);

    Route::get('/activation/{link_hash}', [AssetsController::class, 'find']);
    Route::get('/activation/faceId/{link_hash}', [AssetsController::class, 'faceId']);

    Route::prefix('checkout')->group(function () {
        Route::post('/canceled/{paymentId}/{linkHash}', [CheckoutController::class, 'cancelarPagamento']);
        Route::post('/pix/{linkHash}', [CheckoutController::class, 'criarPagamentoPix']);
        Route::post('/boleto/{linkHash}', [CheckoutController::class, 'criarPagamentoBoleto']);
        Route::post('/cartao/{linkHash}', [CheckoutController::class, 'criarPagamentoCartao']);

        Route::get('/info/{id_payment}', [CheckoutController::class, 'recuperarPagamento']);
    });

    Route::post('/enviar-whatsapp/{messageType}/{linkHash}', [WhatsAppController::class, 'sendMessageByType']);

    // Home
    Route::get('/home/{idImobiliaria?}', [HomeController::class, 'index']);
});
