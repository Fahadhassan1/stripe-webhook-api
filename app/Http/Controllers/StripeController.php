<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StripeService;
use Illuminate\Support\Facades\Validator;


class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function redirectToStripe()
    {
        // you have to change this query according to authenticate user i'm just getting first user for testing purpose
        $user = User::where('id', 1)->first();

        $account = $this->stripeService->createAccount($user);
        $accountLink = $this->stripeService->createAccountLink($account->id);

        return $accountLink->url;

//        return redirect()->away($accountLink->url);
    }

    public function handleStripeCallback(Request $request, $accountId)
    {
        try {
            // Validate the account ID from the route parameter
            if (empty($accountId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stripe Account ID is required.',
                ], 400);
            }

            // Replace this with the actual authenticated user
            $user = User::where('id', 1)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            // Call the service
            $message = $this->stripeService->handleStripeCallback($accountId, $user);

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ], 200);

        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ],  500);
        }
    }

    public function deleteStripeConnectAccount(Request $request,$accountId)
    {
        try {

            // Validate the account ID from the route parameter
            if (empty($accountId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stripe Account ID is required.',
                ], 400);
            }
            // Replace this with the actual authenticated user
            $user = User::where('id', 1)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }

            if (empty($user) || empty($user->stripe_account_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stripe Account ID not found. Please contact the Support Team.'
                ], 400);
            }

            // Use the StripeService to handle account deletion
            $this->stripeService->deleteStripeAccount($user->stripe_account_id);

            // Update user and therapist records
            $user->isStripeConnected = 0;
            $user->stripe_account_id = '';
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Stripe Account Deleted Successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

}
