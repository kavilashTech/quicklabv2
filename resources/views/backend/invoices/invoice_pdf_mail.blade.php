<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('INVOICE') }}</title>
    <style>
        table td {
            vertical-align: top;
        }
    </style>
</head>

<body>

    <table align="center" border="0" cellspacing="0" cellpadding="0"
        style="width: 1000px;border: 1px solid #000;font-family: Arial, Helvetica, sans-serif;font-size: 14px;vertical-align: top;">
        <tr>
            <td style="padding: 8px;">
                <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr>
                        <td style="width:300px">
                            <img src="{{ static_asset('assets/img/logo_invoice.png') }}" width="250"
                                style="display:inline-block;" alt="invoice logo">
                        </td>
                        <td>
                            <p style="margin: 0;padding: 1px 0;">
                                GSTIN : {{ get_setting('gstin_number') }}
                            </p>
                            <p style="margin: 0;padding: 1px 0;">
                                DL No :{{ get_setting('dl_number') }}.
                            </p>
                            <p style="margin: 0;padding: 1px 0;">
                                Contact : {{ get_setting('helpline_number') }},
                            </p>
                            <p style="margin: 0;padding: 1px 0;">
                                Email : <a href="mailto:{{ get_setting('contact_email') }}"
                                    style="color: #000;text-decoration: none;">{{ get_setting('contact_email') }}</a>,
                                <a href="mailto:{{ get_setting('service_email') }}"
                                    style="color: #000;text-decoration: none;">{{ get_setting('service_email') }}</a>,
                            </p>
                            <p style="margin: 0;padding: 1px 0;">
                                Website: <a href="{{ get_setting('website_url') }}"
                                    style="color: #000;text-decoration: none;">{{ get_setting('website_url') }}</a>
                            </p>
                        </td>
                        <td style="vertical-align: bottom;text-align: right;">
                            <p style="font-size: 40px;text-decoration: uppercase;margin: 0;">TAX INVOICE</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="border-top:1px solid #000;">
                <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr>
                        <td style="padding: 8px;vertical-align: top;width: 50%;">
                            <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Invoice#</p>
                                    </td>
                                    <td>
                                        <b>: {{ $order->invoice_number }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Invoice Date</p>
                                    </td>
                                    <td>
                                        <b>: {{ date('j M Y', $order->date) }}</b>
                                    </td>
                                </tr>
                                <!-- TODO : To enable in future -->
                                <!--
                <tr>
                  <td>
                    <p style="margin: 0;padding: 2px 0;">Terms</p>
                  </td>
                  <td>
                    <b style="color: red;">: Net 30</b>
                  </td>
                </tr> -->
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Payment Type</p>
                                    </td>
                                    <td>
                                        @php
                                            $paymentType = $order->payment_type;
                                            $orderPaymentType = 'COD';
                                            if (!empty($paymentType) && $paymentType != 'cash_on_delivery') {
                                                $orderPaymentType = 'Prepaid';
                                            }
                                        @endphp
                                        <b>: {{ $orderPaymentType }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Purchase Order Id</p>
                                    </td>
                                    <td>
                                        <b>: {{ $order->code }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Purchase Order Date</p>
                                    </td>
                                    <td>
                                        <b>: {{ date('j M Y', $order->date) }}</b>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        @php
                            $shipping_address = json_decode($order->shipping_address);
                        @endphp

                        <td style="padding: 8px;vertical-align: top;border-left: 1px solid;width: 50%;">
                            <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Place Of Supply </p>
                                    </td>
                                    @if (!empty($orderOtherDetails['state_id']))
                                        <td>
                                            <b>: {{ $shipping_address->state }}
                                                ({{ $orderOtherDetails['state_id'] }})</b>
                                        </td>
                                    @else
                                        <td>
                                            <b>: {{ $shipping_address->state }}</b>
                                        </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Sales person</p>
                                    </td>
                                    <td>
                                        <b>: ECOM</b>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="padding: 3px 8px;vertical-align: top;border-top: 1px solid;background: #f2f3f4;width: 50%;">
                            <b>Bill To</b>
                        </td>
                        <td
                            style="padding: 3px 8px;vertical-align: top;border-left: 1px solid;background: #f2f3f4;border-top: 1px solid;width: 50%;border-right: 1px solid;">
                            <b>Ship To</b>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 3px 8px;vertical-align: top;border-top: 1px solid;width: 50%;">
                            <b
                                style="text-transform: uppercase;padding: 5px 0;display: block;">{{ $shipping_address->name }}</b>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $shipping_address->address }},
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $shipping_address->city }} {{ $shipping_address->postal_code }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $shipping_address->phone }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                :
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                DL No :
                            </p>
                        </td>
                        <td
                            style="padding: 3px 8px;vertical-align: top;border-left: 1px solid;border-top: 1px solid;width: 50%;">
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $shipping_address->address }},
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $shipping_address->city }} {{ $shipping_address->postal_code }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $shipping_address->phone }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                :
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                DL No :
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="border-top: 1px solid;">
                <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th rowspan="2"
                                style="vertical-align: bottom; padding: 4px 8px;border-bottom: 1px solid;background: #f2f3f4;">
                                #
                            </th>
                            <th rowspan="2"
                                style="vertical-align: bottom; padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;text-align: left;width: 200px;">
                                Item & Description</th>
                            <th rowspan="2"
                                style="vertical-align: bottom; padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;text-align: right;">
                                Qty</th>
                            <th rowspan="2"
                                style="vertical-align: bottom; padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;text-align: right;">
                                Rate</th>
                            @if (!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 1)
                                <th colspan="2"
                                    style="vertical-align: bottom; padding: 4px 0px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    CGST
                                </th>
                                <th colspan="2"
                                    style="vertical-align: bottom; padding: 4px 0px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    SGST
                                </th>
                            @elseif(!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 2)
                                <th colspan="2"
                                    style="vertical-align: bottom; padding: 4px 0px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    IGST
                                </th>
                            @endif
                            <th rowspan="2"
                                style="vertical-align: bottom; padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;text-align: right;">
                                Amount</th>
                        </tr>
                        @if (!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 1)
                            <tr>
                                <th
                                    style="padding: 4px 8px;text-align: right; border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    %</th>
                                <th
                                    style="padding: 4px 8px;text-align: right; border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    Amt</th>
                                <th
                                    style="padding: 4px 8px;text-align: right; border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    %</th>
                                <th
                                    style="padding: 4px 8px;text-align: right; border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;border-right: 1px solid;">
                                    Amt</th>
                            </tr>
                        @elseif(!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 2)
                            <tr>
                                <th
                                    style="padding: 4px 8px;text-align: right; border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    %</th>
                                <th
                                    style="padding: 4px 8px;text-align: right; border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    Amt</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($order->orderDetails as $key => $orderDetail)
                        <tr>
                            <td
                                style="vertical-align: top;text-align: center;padding: 4px 8px;border-bottom: 1px solid;">
                                1
                            </td>
                            <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                @php
                                    $product_stock = json_decode($orderDetail->product->stocks->first(), true);
                                    $taxDetails = getProductTaxDetails($orderDetail);
                                @endphp
                                <p style="margin: 0;padding: 2px 0;"> {{ $orderDetail->product->name }} </p>
                                <p style="margin: 0;padding: 2px 0;"> {{ translate('Batch') }}:
                                    {{ $product_stock['batch_number'] }} </p>
                                <p style="margin: 0;padding: 2px 0;">{{ translate('Expiry') }}:
                                    {{ $product_stock['expiry_month'] }}-{{ $product_stock['expiry_year'] }} </p>
                                <p style="margin: 0;padding: 2px 0;">{{ translate('HSN') }}:
                                    {{ $product_stock['hsn_code'] }} </p>
                            </td>
                            <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $orderDetail->quantity }}
                                </p>
                                <p style="margin: 0;padding: 2px 0;text-align: right;">
                                    {{ strtoupper($orderDetail->product->unit) }} </p>
                            </td>
                            <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                <p style="margin: 0;padding: 2px 0;text-align: right;">

                                    @if (!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 1)
                                        {{ single_price($orderDetail->price / $orderDetail->quantity) }}
                                    @elseif(!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 2)
                                        {{ single_price($orderDetail->price / $orderDetail->quantity) }}
                                    @else
                                    @endif
                                </p>
                            </td>
                            @if (!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 1)
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $taxDetails['cgst'] }}%
                                    </p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ single_price($taxDetails['cgstAmt']) }}</p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $taxDetails['sgst'] }}%
                                    </p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ single_price($taxDetails['sgstAmt']) }}</p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">

                                        {{ single_price($orderDetail->price) }}
                                    </p>
                                </td>
                            @elseif(!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 2)
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $taxDetails['igst'] }}%
                                    </p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ single_price($taxDetails['igstAmt']) }}</p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">

                                        {{ single_price(($orderDetail->product->unit_price - $orderDetail->product->discount) * $orderDetail->quantity) }}
                                    </p>
                                </td>
                            @else
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ single_price(($orderDetail->product->unit_price - $orderDetail->product->discount) * $orderDetail->quantity) }}
                                    </p>
                                </td>
                            @endif
                        </tr>
                        @endforeach
        </tr>
        </tbody>
    </table>
    </td>
    </tr>
    <tr>
        @php
            $taxTotalDetails = getOrderTaxDetails($order);
            $bankAcc = get_setting('bank_account_details');
            $bankAccountDetails = json_decode($bankAcc, true);
            
            $result = roundPrice($order->grand_total);
            if ($result) {
                $grandTotal = round($order->grand_total + $taxDetails['cgstAmt'] + $taxDetails['sgstAmt']);
                $roundingVal = $grandTotal - $order->grand_total;
            } else {
                $grandTotal = floor($order->grand_total);
                $roundingVal = $grandTotal - $order->grand_total;
            }
            $roundingFinalResult = number_format($roundingVal, 2);
        @endphp
        <td style="padding-bottom: 100px;">
            <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                <tr>
                    <td style="padding: 8px;vertical-align: top;width: 60%;">
                        <p style="margin: 0;padding: 2px 0;">Items in Total {{ $taxTotalDetails['itemsTotalQty'] }}
                        </p>
                        <!--TODO : Commented temporarily
              <p style="margin: 0;padding: 2px 0 0;">
                <b> ACCOUNT DETAILS :</b>
              </p>
              <p  style="margin: 0;padding: 2px 0;"> Account name : {{ $bankAccountDetails != '' ? $bankAccountDetails['account_name'] : '' }} </p>
              <p  style="margin: 0;padding: 2px 0;"> Account No : {{ $bankAccountDetails != '' ? $bankAccountDetails['account_no'] : '' }} </p>
              <p  style="margin: 0;padding: 2px 0;"> Bank : {{ $bankAccountDetails != '' ? $bankAccountDetails['bank_name'] : '' }} </p>
              <p  style="margin: 0;padding: 2px 0;"> Branch : {{ $bankAccountDetails != '' ? $bankAccountDetails['branch'] : '' }}. </p>
              <p  style="margin: 0;padding: 2px 0;"> IFSC Code : {{ $bankAccountDetails != '' ? $bankAccountDetails['ifsc_code'] : '' }} </p>
              <p  style="margin: 0;padding: 2px 0;"> Swift Code : {{ $bankAccountDetails != '' ? $bankAccountDetails['swift_code'] : '' }} </p> -->

                        <p style="margin: 0;padding: 30px 0 0;">
                            <b> Terms & Conditions :</b>
                        </p>
                        <p style="margin: 0;padding: 2px 0;"> 1. Payment Should Be Made In favour of "Quicklab
                            Services" </p>
                        <p style="margin: 0;padding: 2px 0;"> 2.Goods Once Sold will not be taken back. </p>
                        <p style="margin: 0;padding: 2px 0;"> 3.Subject to Chennai Jurisdiction only </p>
                        <p style="margin: 0;padding: 2px 0;"> 4.Warranty available for the products as mentioned in the
                            product details. </p>
                        <p style="margin: 0;padding: 2px 0;"> 5.Delivery period may vary according to the holidays.
                        </p>
                    </td>

                    <td style="padding: 8px;vertical-align: top;width: 40%;padding: 0;">
                        <table align="center" border="0" cellspacing="0" cellpadding="0"
                            style="width: 100%;text-align: right;border-left: 1px solid;border-bottom: 1px solid;">
                            <tr>
                                <td style="padding: 5px 10px;">
                                    <p style="margin: 0;">Sub Total</p>
                                </td>
                                <td style="padding: 5px 10px;">
                                    <b>{{ single_price($order->orderDetails->sum('price')) }}</b>
                                </td>
                            </tr>
                            @if (!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 1)
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;">CGST </p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b>{{ single_price($taxTotalDetails['totalCgstAmt']) }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;">SGST </p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b>{{ single_price($taxTotalDetails['totalSgstAmt']) }}</b>
                                    </td>
                                </tr>
                            @elseif(!empty($orderOtherDetails['taxAvailable']) && $orderOtherDetails['taxAvailable'] == 2)
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;">IGST </p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b>{{ single_price($taxTotalDetails['totalIgstAmt']) }}</b>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding: 5px 10px;">
                                    <p style="margin: 0;">Shipping Charges</p>
                                </td>
                                <td style="padding: 5px 10px;">
                                    <b>{{ single_price($order->shipping_courier_charge) }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px;">
                                    <p style="margin: 0;">Rounding</p>
                                </td>
                                <td style="padding: 5px 10px;">
                                    <b> {{ $roundingFinalResult }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px;">
                                    <p style="margin: 0;">
                                        <b>Total</b>
                                    </p>
                                </td>
                                <td style="padding: 5px 10px;">
                                    <b> {{ single_price($grandTotal) }}
                                    </b>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 10px;">
                                    <p style="margin: 0;font-size: 16px;">
                                        <b>Balance Due</b>
                                    </p>
                                </td>
                                <td style="padding: 5px 10px;">
                                    <b style="font-size: 16px;">{{ single_price($grandTotal) }}</b>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </table>
    <p
        style="font-size: 12px;font-weight: 500;margin: 0;padding: 4px 0; text-align: center;font-family: Arial, Helvetica, sans-serif;">
        Reg Office : {{ get_setting('reg_office_address') }} .
        <br> Head Office : {{ get_setting('contact_address') }}.
    </p>
</body>

</html>
