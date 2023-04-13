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
                {{--                 
                    <h5 class="mb-4 h6">{{ translate('Reason for Return') }}</h5>
                    <input type="hidden" name="seller_id" value="{{ $order[0]->seller_id }}">
                    <input type="hidden" name="order_id" value="{{ $order[0]->id }}">
                    <input type="hidden" name="product_id" value="{{ $order[0]->product->id }}"> --}}
                <textarea class="form-control" id="return-reason" for="" name="return_reason" placeholder="Reason for Return"
                    required></textarea><br>
                <button type="button"class="btn btn-primary return-btn" value={{ $order[0]->id }}
                    style="width: 200px">{{ translate('Submit') }}</button>

            </div>
        </div>
        <div class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h5>Are you sure you want to return the product?</h5>
                        <form action={{ route('return') }} method="POST">
                            @csrf
                            <input type="hidden" name="seller_id" value="{{ $order[0]->seller_id }}">
                            <input type="hidden" name="order_id" value="{{ $order[0]->id }}">
                            <input type="hidden" name="product_id" value="{{ $order[0]->product->id }}">
                            <input type="hidden" name="return_reason" id="retur_comment">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Return</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="msg-required" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h5>Please Provide the Vaild Return Reason</h5>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {

            $('.return-btn').click(function(e) {
                e.preventDefault();
                var order_id = $(this).val();
                var return_reason = $('#return-reason').val();
                $('#retur_comment').val(return_reason);
                if (return_reason == "") {
                    $('#msg-required').modal('show');
                } else {
                    $('#returnModal').modal('show');
                }


            });
        });
    </script>
@endsection
