<?php

use App\Http\Controllers\Whatsapp\AiSettingController;
use App\Http\Controllers\Whatsapp\ConversationController;
use App\Http\Controllers\Whatsapp\MessageController;
use App\Http\Controllers\Whatsapp\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('whatsapp')->name('whatsapp.')->group(function () {
    Route::get('/', [ConversationController::class, 'index'])->name('index');

    Route::get('pairing', [SessionController::class, 'show'])->name('pairing');
    Route::post('pairing/start', [SessionController::class, 'start'])->name('pairing.start');
    Route::delete('pairing', [SessionController::class, 'destroy'])->name('pairing.destroy');

    Route::get('ai-settings', [AiSettingController::class, 'show'])->name('ai-settings.show');
    Route::put('ai-settings', [AiSettingController::class, 'update'])->name('ai-settings.update');

    Route::get('chats/{chatId}', [MessageController::class, 'show'])->where('chatId', '[^/]+')->name('chats.show');
    Route::post('chats/{chatId}/messages', [MessageController::class, 'store'])->where('chatId', '[^/]+')->name('chats.messages.store');
});
