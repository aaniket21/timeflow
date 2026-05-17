<?php

use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sessions/start', [SessionController::class, 'start']);
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::put('/sessions/{session}', [SessionController::class, 'update']);
    Route::post('/sessions/{session}/stop', [SessionController::class, 'stop']);
});
