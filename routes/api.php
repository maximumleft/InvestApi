<?php

use App\Http\Controllers\Api\InvestController;
use App\Http\Controllers\AuthController;
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

Route::prefix('auth')->group(function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/update-profile', [AuthController::class, 'updateProfile']);
});
