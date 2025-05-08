<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Stripe Views
Route::get('/transactions/kpi/data', [StripeController::class, 'showTransactionData'])->name('payments.index');
Route::get('/transactions/download/excel', [StripeController::class, 'downloadExcel'])->name('payments.downloadExcel');

// get all transactions
Route::get('/transactions/store', [StripeController::class, 'storeTransactionData'])->name('payments.storeTransactionData');

