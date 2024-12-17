<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StripeService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;



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


    public function createLoginLink(Request $request,$accountId)
    {
        try {

            // Validate the account ID from the route parameter
            if (empty($accountId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stripe Account ID is required.',
                ], 400);
            }


            $loginLink = $this->stripeService->createLoginLink($accountId);

            if ($loginLink) {
                return response()->json([
                    'success' => true,
                    'login_link' => $loginLink,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to create login link. Please try again.',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }


    public function chargeClient(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.5',
                'currency' => 'required|string|max:3',
                'payment_method_id' => 'required|string',
                'customer_id' => 'nullable|string',
                'destination' => 'required|string',
                'email' => 'nullable|email',
                'name' => 'nullable|string',
                'description' => 'nullable|string',
            ]);

            $result = $this->stripeService->chargeClient(
                $validated['amount'],
                $validated['currency'],
                $validated['payment_method_id'],
                $validated['destination'],
                $validated['customer_id'] ?? null,
                0.5, // Fee percentage
                $validated['email'] ?? null,
                $validated['name'] ?? null,
                $validated['description'] ?? null
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'payment_intent' => $result['payment_intent'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors(),
            ], $e->getCode() ?: 500);
        }
    }




    /**
     * Capture a payment.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function capturePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_intent_id' => 'required|string',
            ]);

            $result = $this->stripeService->capturePayment($validated['payment_intent_id']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'payment_intent' => $result['payment_intent'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors(),
            ], $e->getCode() ?: 500);
        }

    }


    /**
     * Cancel a payment.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $result = $this->stripeService->cancelPayment($validated['payment_intent_id']);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'payment_intent' => $result['payment_intent'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }


    public function refundPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.5', // Optional for partial refund
        ]);

        // Convert amount to cents
        $amountInCents = $validated['amount'] ? $validated['amount'] * 100 : null;

        $result = $this->stripeService->processRefund(
            $validated['payment_intent_id'],
            $validated['amount'] ?? null
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'refund' => $result['refund'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }


}
