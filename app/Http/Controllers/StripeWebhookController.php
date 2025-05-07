<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handleWebhooks(Request $request)
    {

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET'); 

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            // Check the type of event
            switch ($event->type) {
                
                case 'charge.succeeded':
                    $charge = $event->data->object; // contains a Stripe Charge object
                    $this->storeTransactionData($charge);
                    break;
                case 'charge.refunded':
                    $charge = $event->data->object; 
                    $this->storeRefundData($charge);
                    break;    
                
                // Add more event types as needed
                default:
                    // Log or handle other events if necessary
                    Log::info('Unhandled event type: ' . $event->type);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // Handle the error
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function storeTransactionData($stripeObject)
    {
    
        $transactionDate = Carbon::createFromTimestamp($stripeObject->created);

        if (isset($stripeObject->data->transfer_data->destination)) {
            $account = \Stripe\Account::retrieve($stripeObject->data->transfer_data->destinatio);
            $stripeObject->on_behalf_of = $account->id;
            $stripeObject->transfer_data->destination = $account->business_profile->name ?? null;
            $stripeObject->transfer_data->destination_email  = $account->email ?? null;
        }
        // Capture the relevant data from Stripe's response and store it in your database
        Transaction::UpdateOrCreate(
            ['transaction_id' => $stripeObject->id],
        [
            'transaction_id' => $stripeObject->id,
            'amount' => $stripeObject->amount / 100,  
            'stripe_fee' => $stripeObject->metadata->stripeFee / 100 ?? 0,
            'platform_fee' => $stripeObject->metadata->serviceFee / 100 ?? 0,  
            'captured' => $stripeObject->captured ?? false, 
            'customer_id' => $stripeObject->customer ?? null,
            'connect_account_id' => $stripeObject->on_behalf_of ?? null,
            'connect_account_name' => $stripeObject->transfer_data->destination ?? null,
            'connect_account_email' => $stripeObject->transfer_data->destination_email ?? null,
            'session_url' => $stripeObject->metadata->sessionUrl ?? null,
            'status' => $stripeObject->status == 'succeeded' ? 'paid' : null,
            'metadata' => json_encode($stripeObject->metadata),
            'json_data' => json_encode($stripeObject),
            'transaction_date' => $transactionDate,
            'created_at' => now(),
        ]);
    }

    public function storeRefundData($stripeObject)
    {
        $transactionDate = Carbon::createFromTimestamp($stripeObject->created);

        Transaction::UpdateOrCreate(
            ['transaction_id' => $stripeObject->id],
            [
                'refunded_amount' => $stripeObject->amount_refunded / 100,
                'status' => $stripeObject->refunded ==  true ? 'refunded' : null,
                'refunded_at' => $transactionDate,
                'metadata' => json_encode($stripeObject->metadata),
                'json_data' => json_encode($stripeObject),
                'updated_at' => now(),
                'transaction_date' => $transactionDate,
            ]
        );
    }   
}

