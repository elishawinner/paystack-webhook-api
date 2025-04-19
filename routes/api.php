<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaystackPaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::post('/pay', [PaystackPaymentController::class, 'redirectToGateway']);
// Route::get('/payment/callback', [PaystackPaymentController::class, 'handleGatewayCallback'])->name('payment.callback');
// Route::post('/paystack/webhook', [PaystackPaymentController::class, 'handleWebhook']);

// routes/web.php or routes/api.php
Route::post('/pay', [PaystackPaymentController::class, 'redirectToGateway']);
Route::get('/payment/callback', [PaystackPaymentController::class, 'handleGatewayCallback'])
    ->name('payment.callback');
Route::post('/paystack/webhook', [PaystackPaymentController::class, 'handleWebhook'])
    ->withoutMiddleware(['throttle:api', 'csrf']);