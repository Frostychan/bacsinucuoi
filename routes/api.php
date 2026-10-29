<?php

use App\Http\Controllers\SepayWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/sepay', SepayWebhookController::class)
    ->name('webhooks.sepay');
