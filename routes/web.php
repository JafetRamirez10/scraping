<?php

use App\Http\Controllers\HandleBrevoWebhookController;
use App\Http\Controllers\HandleMailRushWebhookController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/unsubscribe/{prospect}', UnsubscribeController::class)
    ->name('unsubscribe');

Route::post('/webhooks/brevo', HandleBrevoWebhookController::class)
    ->name('webhooks.brevo');

Route::post('/webhooks/mailrush', HandleMailRushWebhookController::class)
    ->name('webhooks.mailrush');
