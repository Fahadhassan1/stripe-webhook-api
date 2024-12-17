<?php

namespace App\Http\Controllers;

use App\CancelledSubscriptions;
use App\ClientPayments;
use App\FailedTransactions;
use Exception;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;


class StripeWebhookController extends Controller
{
    /**
     * Handle a successful payment webhook from Stripe.
     *
     */

    public function handleWebhooks(Request $request) {

        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
        } catch (UnexpectedValueException $exception) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $exception) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'charge.refunded':
                $this->handleBookingRefund($request);
                break;
            default:
                return response()->json(['error' => 'Unhandled event type'], 400);
        }
        return response()->json(['status' => 'success']);

    }

    public function handleBookingRefund($request) {
        try {
            $customerId = $request->data['object']['customer'];

        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
}
