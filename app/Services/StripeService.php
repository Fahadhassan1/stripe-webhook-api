<?php

namespace App\Services;

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Charge;
use Stripe\Transfer;
use Stripe\BalanceTransaction;
use Stripe\Payout;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SK'));
    }

    public function createAccount($user)
    {
        return Account::create([
            'type' => 'express',
            'country' => 'GB',
            'email' => $user['email'],
            'business_type' => 'individual',
            'individual' => [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                // Include date of birth (required for identity verification)
//                'dob' => [
//                    'day' => $user['dob_day'],
//                    'month' => $user['dob_month'],
//                    'year' => $user['dob_year'],
//                ],
            ],
//            'address' => [  // Include address (often required)
//                'line1' => $user['address_line1'],
//                'city' => $user['city'],
//                'postal_code' => $user['postal_code'],
//                'state' => $user['state'], // For countries with states/provinces
//            ],
            'capabilities' => [
                'transfers' => ['requested' => true],
                'card_payments' => ['requested' => true],
            ],

            'settings' =>  [
            "payouts" => [
                "schedule" => ["interval" => "weekly",'weekly_anchor' => 'monday',],  # Options: 'daily', 'weekly', 'monthly'
                ],
            ],
        ]);
    }

    public function createAccountLink($accountId)
    {
        return AccountLink::create([
            'account' => $accountId,
            'refresh_url' => env('STRIPE_REFRESH_URL'),
            'return_url' => env('STRIPE_REDIRECT_URL') . '/' . $accountId,
            'type' => 'account_onboarding',
        ]);
    }

    public function handleStripeCallback($accountId, $user)
    {
        if (!$user) {
            throw new \Exception('Unauthorized access', 401);
        }

        if (!$accountId) {
            throw new \Exception('Stripe Account ID not found. Please contact support.', 400);
        }

        // Logic to save Stripe Connected and Stripe Connect ID in the database
        $user->isStripeConnected = 1;
        $user->stripe_account_id = $accountId;
        $user->save();

        return 'Stripe Account Connected Successfully';
    }

    public function deleteStripeAccount($stripeAccountId)
    {
        $account = Account::retrieve($stripeAccountId);

        if (!$account) {
            throw new \Exception('Stripe Account ID not found. Please contact the Support Team.', 400);
        }

        $account->delete();
    }

    /**
     * Create a login link for a connected account.
     *
     * @param string $connectedAccountId
     * @return string|null
     */
    public function createLoginLink(string $connectedAccountId): ?string
    {
        try {
            $loginLink = Account::createLoginLink($connectedAccountId);
            return $loginLink->url;
        } catch (\Exception $e) {
            // Log or handle errors as needed
            \Log::error('Stripe Login Link Error: ' . $e->getMessage());
            return null;
        }
    }

    public function chargeClient(float $amount, string $currency, string $paymentMethodId,  string $destination,
        ?string $customerId = null,
        float $feePercentage = 0.5,
        ?string $email = null,
        ?string $name = null,
        ?string $description = null
    ): array {

        try {
            // Create a new customer if no customer ID is provided
            if (!$customerId) {
                if (!$email || !$name) {
                    return [
                        'success' => false,
                        'error' => 'Email and name are required to create a new customer.',
                    ];
                }


                $customerResult = $this->createCustomer($email, $name);

                if (!$customerResult['success']) {
                    return [
                        'success' => false,
                        'error' => $customerResult['error'],
                    ];
                }

                $customerId = $customerResult['customer']->id;
            }

            // Calculate total fee and amount with fee
            $totalFee = $amount * ($feePercentage / 100);
            $amountWithFee = $amount + $totalFee;


                // Create a new PaymentIntent
                $paymentIntentData = [
                    'amount' => (int)($amountWithFee * 100), // Convert to cents
                    'currency' => $currency,
                    'payment_method' => $paymentMethodId,
                    'payment_method_types' => ['card'],
                    'customer' => $customerId, // Associate the customer
                    'capture_method' => 'manual',
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'application_fee_amount' => (int)($totalFee * 100), // Fee in cents
                    'description' => $description,
                    'transfer_data' => [
                        'destination' => $destination,
                    ],
                ];

                $paymentIntent = PaymentIntent::create($paymentIntentData);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
            ];
        } catch (\Exception $e) {
            \Log::error('Stripe Charge Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new Stripe customer.
     *
     * @param string $email
     * @param string $name
     * @return array
     */
    public function createCustomer(string $email, string $name): array
    {
        try {

            $customer = Customer::create([
                'email' => $email,
                'source' => 'tok_visa', // Token from Stripe.js or Elements
                'name' => $name,
            ]);


            return [
                'success' => true,
                'customer' => $customer,
            ];
        } catch (\Exception $e) {
            \Log::error('Stripe Create Customer Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Capture an authorized payment.
     *
     * @param string $paymentIntentId
     * @return array
     */
    public function capturePayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            // Now capture the PaymentIntent
            $paymentIntent->capture();

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
            ];
        } catch (\Exception $e) {
            \Log::error('Stripe Capture Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * Cancel a payment intent.
     *
     * @param string $paymentIntentId
     * @return array
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = paymentIntent::retrieve($paymentIntentId);
            // Now cancel the PaymentIntent
            $paymentIntent->cancel();

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
            ];
        } catch (\Exception $e) {
            \Log::error('Stripe Cancel Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * Cancel a payment intent.
     *
     * @param string $paymentIntentId
     * @param int|null $amount
     * @return array
     */
    public function processRefund(string $paymentIntentId, int $amount = null): array
    {
        try {

            $paymentIntent = paymentIntent::retrieve($paymentIntentId);

            // Get the last charge ID
            $chargeId =   $paymentIntent->latest_charge;


            $charge = Charge::retrieve($chargeId);
            $transferId = $charge->transfer;

            $refundParams = ['charge' => $chargeId];
            if ($amount) {
                $refundParams['amount'] = $amount * 100; // Amount in cents
            }

            $reversalParams = ['amount' => $amount * 100]; // Amount in cents
            $transferReversal = Transfer::createReversal($transferId, $reversalParams);
            // Create the refund via Stripe
            $refund = Refund::create($refundParams);

            return [
                'success' => true,
                'refund' => $refund,
                'transfer_reversal' => $transferReversal,
            ];
        } catch (ApiErrorException $e) {
            \Log::error('Stripe Refund Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    public function manualPayout(string $connectedAccountId, int $amount, string $currency, float $platformFee = 0.0): array
    {
        try {
            // Calculate platform fee
            $feeAmount = $amount * $platformFee / 100;
            $transferAmount = $amount - $feeAmount;

            // Create a Transfer to the connected account
            $transfer = Transfer::create([
                'amount' => $transferAmount * 100, // Amount in cents
                'currency' => $currency,
                'destination' => $connectedAccountId,
//                'metadata' => [
//                    'payment_intent_id' => $paymentIntentId,
//                    'reason' => 'Manual payout for completed transaction',
//                ]
            ]);

            return [
                'success' => true,
                'transfer' => $transfer,
                'fee_deducted' => $feeAmount,
            ];
        } catch (\Exception $e) {
            \Log::error('Stripe Manual Payout Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    public function getAllTransactionsByAccountId($accountId)
    {
        try {
            $transactions = BalanceTransaction::all(['limit' => 100], ['stripe_account' => $accountId]);

            return $transactions->data;
        } catch (\Exception $e) {
            throw new \Exception('Error fetching transactions: ' . $e->getMessage());
        }
    }
}
