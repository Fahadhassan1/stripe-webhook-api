<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';

    protected $fillable = [
        'stripe_account_id',
        'amount_paid',
        'stripe_fee',
        'revenue_generated',
    ];
 
}
