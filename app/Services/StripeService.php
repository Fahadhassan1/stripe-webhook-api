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
            'type' => 'custom',
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
}
