<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StripeService;


class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function redirectToStripe()
    {
        $user = User::where('id', 1)->first();

        $account = $this->stripeService->createAccount($user);
        $accountLink = $this->stripeService->createAccountLink($account->id);

        return redirect()->away($accountLink->url);
    }
}
