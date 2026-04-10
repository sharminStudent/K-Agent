<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('chat')->group(function (): void {
    Route::post('/session', [ChatController::class, 'createSession']);
    Route::post('/send-message', [ChatController::class, 'sendMessage']);
});

Route::prefix('lead')->group(function (): void {
    Route::post('/store', [LeadController::class, 'store']);
});
