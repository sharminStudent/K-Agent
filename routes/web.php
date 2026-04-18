<?php

use App\Http\Controllers\WidgetController;
use App\Http\Controllers\Filament\TranscriptDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('widget/{widgetToken}')->group(function (): void {
    Route::get('/embed.js', [WidgetController::class, 'script'])->name('widget.script');
    Route::get('/frame', [WidgetController::class, 'frame'])->name('widget.frame');
    Route::get('/preview', [WidgetController::class, 'preview'])->name('widget.preview');
    Route::get('/bootstrap', [WidgetController::class, 'bootstrap'])->name('widget.bootstrap');
    Route::get('/help', [WidgetController::class, 'help'])->name('widget.help');
    Route::get('/help/{knowledgeFile}', [WidgetController::class, 'helpArticle'])->name('widget.help.article');
});

Route::middleware('auth')->get('/admin/chat-sessions/{chatSession}/transcript', TranscriptDownloadController::class)
    ->name('admin.chat-sessions.transcript');
