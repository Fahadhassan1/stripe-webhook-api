<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\RunningTally;
use App\Exports\PaymentsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon;
use Stripe\Stripe;
use Stripe\Charge;

class StripeController extends Controller
{

    // This method is used to store transaction data in the database
    public function showTransactionData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $data = Transaction::select(
            DB::raw('MONTH(transaction_date) as month'),
            DB::raw('SUM(stripe_fee) as sum_stripe_fee'),
            DB::raw('SUM(platform_fee) as sum_fee'),
            DB::raw('SUM(amount) as sum_amount'),
            DB::raw('COUNT(DISTINCT transaction_id) as count_id')
        )
            ->where('captured', true)
            ->whereYear('transaction_date', $year)
            ->groupBy(DB::raw('MONTH(transaction_date)'))
            ->orderBy(DB::raw('MONTH(transaction_date)'))
            ->get();

        $formattedData = $data->mapWithKeys(function ($item) {
            $monthName = \Carbon\Carbon::createFromFormat('m', $item->month)->format('M');
            return [
                $monthName => [
                    'sum_service_fee' => $item->sum_stripe_fee,
                    'sum_fee' => $item->sum_fee,
                    'sum_amount' => $item->sum_amount,
                    'count_id' => $item->count_id,
                ]
            ];
        });


        // Return the data as JSON
        return response()->json([
            'data' => $formattedData,
            'year' => $year,
            'total_service_fee' => $data->sum('sum_stripe_fee'),
            'total_fee' => $data->sum('sum_fee'),
            'total_amount' => $data->sum('sum_amount'),
            'total_count_id' => $data->sum('count_id'),
        ]);
        
    }


    // This method is used to download the payments data as an Excel file
    public function downloadExcel(Request $request)
    {
        $year = $request->input('year', date('Y'));
        return Excel::download(new PaymentsExport($year), 'payments.xlsx');
    }



    // This method is used to store transaction data in the database
    public function storeTransactionData()
    {
        set_time_limit(0);
        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            $hasMore = true;
            $lastChargeId = null;

            while ($hasMore) {
                $params = ['limit' => 100];
                if ($lastChargeId) {
                    $params['starting_after'] = $lastChargeId;
                }

                $stripeObject = Charge::all($params);

                foreach ($stripeObject->data as $transaction) {
                    // Consider dispatching to a job for better performance
                    if (isset($transaction->transfer_data->destination)) {
                        $account = \Stripe\Account::retrieve($transaction->transfer_data->destination);
                        $transaction->metadata->connectAccountId = $account->id;
                        $transaction->metadata->connectAccountName = $account->business_profile->name ?? null;
                        $transaction->metadata->connectAccountEmail = $account->email ?? null;
                    }
                    $this->storeTransaction($transaction);
                }

                $hasMore = $stripeObject->has_more;
                if ($hasMore) {
                    $lastChargeId = end($stripeObject->data)->id;
                }
            }   
            return response()->json(['message' => 'Transaction data stored successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // This method is used to store transaction data in the database
    private function storeTransaction($stripeObject)
    {
        // Prepare a status column for the table
        if($stripeObject->status == 'succeeded' && $stripeObject->refunded == true) {
            $status = 'refunded';
        } elseif ($stripeObject->status == 'succeeded' && $stripeObject->refunded == false) {
            $status = 'paid';
        } else {
            $status = $stripeObject->status;
        }
        $refundedAt = $stripeObject->refunded == true ? \Carbon\Carbon::createFromTimestamp($stripeObject->created) : null;
        Transaction::updateOrCreate(
            ['transaction_id' => $stripeObject->id],
            [
                'transaction_id' => $stripeObject->id ?? null,
                'amount' => isset($stripeObject->amount) ? $stripeObject->amount / 100 : 0,
                'stripe_fee' => isset($stripeObject->metadata->stripeFee) ? ($stripeObject->metadata->stripeFee / 100 ?? 0) : 0,
                'platform_fee' => isset($stripeObject->metadata->serviceFee) ? ($stripeObject->metadata->serviceFee / 100 ?? 0) : 0,
                'captured' => true,
                'customer_id' => $stripeObject->customer ?? null,
                'connect_account_id' => $stripeObject->metadata->connectAccountId ?? null,
                'connect_account_name' => $stripeObject->metadata->connectAccountName ?? null,
                'connect_account_email' => $stripeObject->metadata->connectAccountEmail ?? null,
                'session_url' => $stripeObject->metadata->sessionUrl ?? null,

                'base_price' => isset($stripeObject->metadata->basePrice) ? ($stripeObject->metadata->basePrice / 100 ?? 0) : 0,
                'session_instance_id' => $stripeObject->metadata->sessionInstanceId ?? null,
                'session_owner_id' => $stripeObject->metadata->sessionOwnerId ?? null,
                'session_owner_name' => $stripeObject->metadata->sessionOwnerName ?? null,
                'session_for' => $stripeObject->metadata->for ?? null,
                'userId' => $stripeObject->metadata->userId ?? null,

                'status' => $status ?? null,
                'refunded_amount' => isset($stripeObject->amount_refunded) ? $stripeObject->amount_refunded / 100 : 0,
                'refunded_at' => $refundedAt ?? null,
                'transaction_date' => isset($stripeObject->created) ? \Carbon\Carbon::createFromTimestamp($stripeObject->created) : null,
                'metadata' => isset($stripeObject->metadata) ? json_encode($stripeObject->metadata) : null,
                'json_data' => isset($stripeObject) ? json_encode($stripeObject) : null,
            ]
        );
    }
}
