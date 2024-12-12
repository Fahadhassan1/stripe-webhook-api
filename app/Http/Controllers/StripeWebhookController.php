<?php

namespace App\Http\Controllers;

use App\CancelledSubscriptions;
use App\ClientPayments;
use App\FailedTransactions;
use App\Models\Billing;
use App\Models\Bookings;
use App\Models\CancelledBooking;
use App\Package;
use App\SubscriptionHistory;
use App\Subscriptions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
            $billing = Billing::where(['stripe_customer_id' => $customerId])->first();
            $booking = Bookings::where(['id' => $billing->booking_id])->first();

            if($billing) {
                $cancelledBooking = CancelledBooking::where(['booking_id' => $billing->booking_id])->first();
                if($cancelledBooking) {
                    $cancelledBooking->update([
                        'status' => 1,
                        'amount' => $request->data['object']['amount_refunded'] / 100,
                        'stripe_response' => json_encode($request->data['object']),
                        'refund_date' => Carbon::now(),
                    ]);
                    $booking->update(['status' => 3]);
                    $billing->update(['invoice_status' => 3]);
                    return response()->json(['message' => 'Booking refunded successfully']);
                } else {
                    return response()->json(['error' => 'Booking not found'], 404);
                }
            } else {
                return response()->json(['error' => 'Booking not found'], 404);
            }
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
}
