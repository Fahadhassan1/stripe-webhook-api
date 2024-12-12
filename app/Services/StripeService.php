<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Customer;
use Stripe\PaymentIntent;


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
            'email' => $user->email,
            'business_type' => 'individual',
            'individual' => [
                'first_name' => $user->fname,
                'last_name' => $user->lname,
            ],
            'capabilities' => [
                'transfers' => ['requested' => true],
                'card_payments' => ['requested' => true],
            ],

//            'settings' =>  [
//            "payouts" => [
//                "schedule" => ["interval" =>"weekly"],  # Options: 'daily', 'weekly', 'monthly'
//                ],
//            ],
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
}
