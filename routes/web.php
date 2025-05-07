<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StripeController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/




// Stripe Views
Route::get('/', [StripeController::class, 'showTransactionData'])->name('payments.index');
Route::get('/payments/download/excel', [StripeController::class, 'downloadExcel'])->name('payments.downloadExcel');

// get all transactions
Route::get('/transactions/store', [StripeController::class, 'storeTransactionData'])->name('payments.storeTransactionData');

// Stripe WebHook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhooks']);
