@extends('backend.layouts.app')
{{-- {{ dd($returns) }} --}}
@section('content')
    <div>
        @if (Session::has('return'))
            <p class="alert {{ Session::get('alert-class', 'alert-success') }}">
                {{ Session::get('return') }}
            </p>
        @endif
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <!--<th>#</th>-->
                        <th>{{ translate('Order Code') }}</th>
                        <th data-breakpoints="md">{{ translate('Num. of Products') }}</th>
                        <th data-breakpoints="md">{{ translate('Customer Id') }}</th>
                        <th data-breakpoints="md">{{ translate('Amount') }}</th>
                        <th data-breakpoints="md">{{ translate('Delivery Status') }}</th>
                        <th data-breakpoints="md">{{ translate('Payment method') }}</th>
                        <th data-breakpoints="md">{{ translate('Payment Status') }}</th>
                        <th data-breakpoints="md">{{ translate('Reason For Return') }}</th>
                        <th data-breakpoints="md">{{ translate('Return Request Date') }}</th>
                        <th data-breakpoints="md">{{ translate('options') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($returns as $return)
                        {{-- {{ dd($return) }} --}}
                        <td>{{ $return->order->code }}</td>
                        <td>{{ $return->quantity }}</td>
                        <td>{{ $return->order->user_id }}</td>
                        <td>{{ $return->price }}</td>
                        <td>{{ $return->delivery_status }}</td>
                        <td>{{ $return->order->payment_type }}</td>
                        <td>{{ $return->payment_status }}</td>
                        <td>{{ $return->return_reason }}</td>
                        <td>{{ $return->return_request_date }}</td>
                        <td>
                            @if ($return->delivery_status == 'Pending Approval')
                                <div class="btn-group" role="group">
                                    <a href="approve/{{ $return->id }}"
                                        class="btn btn-success">{{ translate('Approve') }}</a>
                                    <a href="reject/{{ $return->id }}" class="btn btn-danger"
                                        style="margin-left:2px">{{ translate('Reject') }}</a>
                                </div>
                            @elseif($return->delivery_status == 'Approved')
                                {{ translate('Approved') }}
                            @elseif($return->delivery_status == 'Rejected')
                                {{ translate('Rejected') }}
                            @endif
                        </td>
                </tbody>
                @endforeach
            </table>
        </div>
    </div>
@endsection
