@extends('frontend.layouts.user_panel')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .box {
        background: #b8b8b8;
        border-radius: 21px;
    }

    .col-lg-9{
        margin:auto;
        margin-top: 30px !important;
    }
    .data-class{
        padding: 25px 0px 25px;
    }
</style>
@section('panel_content')
<!-- <div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="align-items-center">
        <h1 class="h3">{{translate('Customer List')}}</h1>
    </div>
</div> -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Sales Report')}}</h5>
        <form action="{{ route('franchisee_sales_report.downloadFranchiseSalesReport')}}"  method="POST">
            @csrf
            <input type='hidden' value="{{$from_date}}" name="from_date" />
            <input type='hidden' value="{{$to_date}}" name="to_date" />
            <button class="btn btn-primary mt-2 submit">{{ translate('Download CSV') }}</button>
        </form>
    </div>

    <div class="container">
        <form action="{{route('customer_list.franchisee_sales_report')}}" method="POST">
            @csrf
            <div class="row mt-4 ml-3">
                <div class='col-sm-4'>
                    <div class="form-group">
                        <label>From</label>
                        <div class='input-group date'>
                            <input type='text' class="form-control datetimepicker" id='datetimepicker' value="{{$from_date}}" name="from_date" />
                        </div>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class="form-group">
                        <label>To</label>
                        <div class='input-group date'>
                            <input type='text' class="form-control datetimepicker" id='datetimepicker' value="{{$to_date}}" name="to_date" />
                        </div>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class="form-group mt-4">
                        <button class="btn btn-primary">Search</button>
                    </div>
                </div>
            </div>
        </form>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{translate('Sl.')}}</th>
                        <th>{{translate('Customer Name')}}</th>
                        <th>{{translate('Date')}}</th>
                        <th>{{translate('Sale Value')}}</th>
                        <th>{{translate('Margin')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $key => $customer)
                    <tr>
                        <td>{{ ++$key }}</td>
                        <td>{{ $customer->user->name }}</td>
                        <td>{{ date('d-m-Y', strtotime($customer->created_at)) }}</td>
                        <td>{{$customer->total_price}}</td>
                        <td>{{$customer->total_price - $customer->wholesale_price}}</td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $customers->links() }}
            </div>
            <div class="row">
                <div class="col-lg-9">
                    <div class="box text-center">
                        <div class="data-class">
                            <div class="row">
                                <div class="col-5 text-left" style="padding-left:90px"><b>Period</b></div>
                                <div class="col-1">:</div>
                                <div class="col-6 text-left">{{date('d F Y', strtotime($from_date))}}  - {{date('d F Y', strtotime($to_date))}}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-5 text-left" style="padding-left:90px"><b>Total Sales</b></div>
                                <div class="col-1">:</div>
                                <div class="col-6 text-left">Rs. {{$total_sale_value}}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-5 text-left" style="padding-left:90px"><b>Total Margin</b></div>
                                <div class="col-1">:</div>
                                <div class="col-6 text-left">Rs. {{$total_wholesale_price}}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(".datetimepicker").flatpickr({
            dateFormat: "d-m-Y",
        });
    </script>
    @endsection