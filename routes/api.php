<?php

use App\Http\Controllers\Api\InvestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('invest')->group(function() {
    Route::get('/accounts', [InvestController::class, 'accounts']);
    Route::get('/portfolio/{accountId}', [InvestController::class, 'portfolio']);
    Route::get('/positions/{accountId}', [InvestController::class, 'positions']);
});
