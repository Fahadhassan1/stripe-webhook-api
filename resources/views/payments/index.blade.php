@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Your Payment Information</h2>


    <!-- Display Payments Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Month</th>
                <th>Sum of Service Fee</th>
                <th>Sum of Platform Fee</th>
                <th>Sum of Amount</th>
                <th>Count of Transactions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($formattedData as $month => $data)
            <tr>
                <td>{{ $month }}</td>
                <td>{{ number_format($data['sum_service_fee'], 2) }}</td>
                <td>{{ number_format($data['sum_fee'], 2) }}</td>
                <td>{{ number_format($data['sum_amount'], 2) }}</td>
                <td>{{ $data['count_id'] }}</td>
            </tr>
         @endforeach
        </tbody>
    </table>

    <!-- Download Buttons -->
    <div class="mt-3">
        <a href="{{ route('payments.downloadExcel') }}" class="btn btn-success">Download Excel</a>
    </div>
</div>
@endsection
