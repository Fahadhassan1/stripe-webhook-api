<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;

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
}
