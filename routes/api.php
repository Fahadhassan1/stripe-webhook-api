<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StripeController;


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

Route::resource('posts', PostController::class);

// Stripe API
Route::post('/stripe/redirect', [StripeController::class, 'redirectToStripe']);
Route::get('/stripe/connect/callback/{stripe_account_id}', [StripeController::class,'handleStripeCallback']);
Route::delete('/stripe/delete/{stripe_account_id}', [StripeController::class,'deleteStripeConnectAccount']);
Route::get('/stripe/express/login-link/{stripe_account_id}', [StripeController::class, 'createLoginLink']);
Route::post('/stripe/charge-client', [StripeController::class, 'chargeClient']);
Route::post('/stripe/capture-payment', [StripeController::class, 'capturePayment']);
Route::post('/stripe/cancel-payment', [StripeController::class, 'cancelPayment']);
Route::post('/stripe/refund-payment', [StripeController::class, 'refundPayment']);




// Stripe WebHook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhooks']);


