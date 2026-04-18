<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('agents')->group(function (): void {
    Route::post('/', [AgentController::class, 'store']);
    Route::get('/{agent}', [AgentController::class, 'show']);
    Route::put('/{agent}', [AgentController::class, 'update']);
    Route::post('/{agent}/regenerate-widget-token', [AgentController::class, 'regenerateWidgetToken']);
});

Route::prefix('chat')->group(function (): void {
    Route::post('/session', [ChatController::class, 'createSession']);
    Route::post('/send-message', [ChatController::class, 'sendMessage']);
});

Route::prefix('lead')->group(function (): void {
    Route::post('/store', [LeadController::class, 'store']);
});

Route::prefix('knowledge')->group(function (): void {
    Route::post('/upload', [KnowledgeController::class, 'store']);
    Route::post('/{knowledgeFile}/process', [KnowledgeController::class, 'process']);
});
