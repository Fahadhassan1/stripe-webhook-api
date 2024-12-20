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

            case 'payment_intent.succeeded':
                $this->handlePaymentSuccess($request);
                break;
            case 'charge.refunded':
                $this->handleBookingRefund($request);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailure($request);
                break;
            case 'payout.paid':
                $this->handlePayoutCompleted($request);
                break;
            default:
                return response()->json(['error' => 'Unhandled event type'], 400);
        }
        return response()->json(['status' => 'success']);

    }

    public function handlePaymentSuccess($request)
    {
        try {

            $paymentIntent = $request->data->object; // Contains a Stripe PaymentIntent
            $paymentStatus = $paymentIntent->status; // Should be 'succeeded'
            // Perform actions and update your DB according to your business logic

        } catch (Exception $exception) {
            return $exception->getMessage();
        }


    }

    public function handleBookingRefund($request)
    {
        try {
            $charge = $request->data->object; // Contains a Stripe Charge object
            $refundAmount = $charge->amount_refunded; // Amount refunded
            $chargeId = $charge->id;

            // Perform actions and update your DB according to your business logic

        } catch (Exception $exception) {
            return $exception->getMessage();
        }

    }

    public function handlePaymentFailure($request)
    {
        try {
            $paymentIntent = $request->data->object; // Contains a Stripe PaymentIntent
            $errorMessage = $paymentIntent->last_payment_error->message; // Failure reason


            // Perform actions and update your DB according to your business logic

        } catch (Exception $exception) {
            return $exception->getMessage();
        }

    }

    public function handlePayoutCompleted($request)
    {
        try {
            $payout = $request->data->object; // Contains a Stripe Payout object
            $payoutAmount = $payout->amount; // Amount of payout
            $payoutStatus = $payout->status; // Status of the payout

            // Perform actions and update your DB according to your business logic

        } catch (Exception $exception) {
            return $exception->getMessage();
        }

    }

}
