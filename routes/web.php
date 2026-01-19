<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\CheckYookassaIp;
use Illuminate\Support\Facades\Route;

Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

Route::post('/payment/create', [PaymentController::class, 'createPayment'])->name('payment.create');

Route::get('/webhooks/check-payment/{order}', [PaymentController::class, 'checkPaymentStatus'])->name('payment.check');

Route::post('/webhooks/yookassa', [PaymentController::class, 'handleWebhook'])
    ->name('webhook.yookassa');
//    ->middleware(CheckYookassaIp::class);
