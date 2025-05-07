<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsExport implements FromCollection, WithHeadings
{
    protected $year;

    public function __construct($year)
    {
        $this->year = $year;
    }
    

    public function collection()
    {
        return Transaction::select(
                'transaction_id',
                        'amount',
                        'stripe_fee',
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
            )
            ->where('captured', true)
            ->whereYear('transaction_date', $this->year)
            ->get();

    }

    public function headings(): array
    {
        return [
            'Transaction ID',
            'Amount',
            'Stripe Fee',
            'Platform Fee',
            'Captured',
            'Customer ID',
            'Connect Account ID',
            'Connect Account Name',
            'Connect Account Email',
            'Session URL',
            'Status',
            'Refunded Amount',
            'Refunded At',
            'Transaction Date',     
        ];
    }
}
