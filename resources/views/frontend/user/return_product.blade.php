@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div>
        @if (Session::has('message-sucess'))
            <p class="alert {{ Session::get('alert-class', 'alert-success') }}">
                {{ Session::get('message-sucess') }}
            </p>
        @endif
    </div>
    <div>
        @if (Session::has('message-return'))
            <p class="alert {{ Session::get('alert-class', 'alert-danger') }}">
                {{ Session::get('message-return') }}
            </p>
        @endif
    </div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Order Details') }}</h5>
        </div>
        <div class="card-body">
            <table id="purchase_history" class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Invoice Number') }}</th>
                        <th data-breakpoints="md">{{ translate('Invoice Date') }}</th>
                        <th>{{ translate('Product Name') }}</th>
                        <th data-breakpoints="md">{{ translate('Quantity') }}</th>
                        <th data-breakpoints="md">{{ translate('Price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- {{ dd($order[0]->product->id) }} --}}
                    <tr>
                        <td>
                            {{ $order[0]->order->invoice_number }}
                        </td>
                        <td>{{ $order[0]->created_at->format('d/m/Y') }}</td>
                        <td>
                            {{ $order[0]->product->name }}
                        </td>
                        <td>
                            {{ $order[0]->quantity }}
                        </td>
                        <td>
                            {{ $order[0]->price }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <hr>
            <div>
                {{-- <h5 class="mb-0 h6">Return Reson</h5> --}}
                <form action="/return" method="POST">
                    @csrf
                    <h5 class="mb-4 h6">{{ translate('Reason for Return') }}</h5>
                    <input type="hidden" name="seller_id" value="{{ $order[0]->seller_id }}">
                    <input type="hidden" name="order_id" value="{{ $order[0]->id }}">
                    <input type="hidden" name="product_id" value="{{ $order[0]->product->id }}">
                    <textarea class="form-control" for="" name="return_reason" placeholder="Reason for Return" required></textarea><br>
                    <button type="submit"class="btn btn-primary" style="width: 200px">{{ translate('Submit') }}</button>
                </form>
            </div>
        </div>

    </div>
@endsection
