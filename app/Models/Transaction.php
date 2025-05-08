<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    protected $fillable = [
        'transaction_id',
        'amount',
        'stripe_fee',
        'service_fee',
        'platform_fee',
        'captured',
        'customer_id',
        'connect_account_id',
        'connect_account_name',
        'connect_account_email',
        'session_url',
        'status',
        'refunded_amount',
        'refunded_at',
        'transaction_date',
        'metadata',
        'json_data',
        'base_price',
        'session_instance_id',
        'session_owner_id',
        'session_owner_name',
        'session_for',
        'userId',
    ];
}
