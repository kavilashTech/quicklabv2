<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Invoice</title>
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
                            <img src="{{ secure_asset('assets/img/logo_invoice.png') }}" width="250"
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
                            <p style="font-size: 40px;text-decoration: uppercase;margin: 0;">Proforma Invoice</p>
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
                                        <p style="margin: 0;padding: 2px 0;">Estimate #</p>
                                    </td>
                                    <td>
                                        <b>: {{ $quotationOtherDetails['quotation_estimate_number'] }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Estimate Date</p>
                                    </td>
                                    <td>
                                        <b>: {{ date('d-m-Y', strtotime($quotation[0]->updated_at)) }}</b>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding: 8px;vertical-align: top;border-left: 1px solid;width: 50%;">
                            <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                                <tr>
                                    <td>
                                        <p style="margin: 0;padding: 2px 0;">Place Of Supply</p>
                                    </td>
                                    <td>
                                        <b>: {{ $quotationOtherDetails['state_name'] }}
                                            ({{ $quotationOtherDetails['state_id'] }})</b>
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
                                style="text-transform: uppercase;padding: 5px 0;display: block;">{{ $quotationOtherDetails['company_name'] }}</b>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $quotationOtherDetails['address'] }},
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $quotationOtherDetails['city_name'] }} {{ $quotationOtherDetails['postal_code'] }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $quotationOtherDetails['phone_number'] }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                DL No :
                            </p>
                        </td>
                        <td
                            style="padding: 3px 8px;vertical-align: top;border-left: 1px solid;border-top: 1px solid;width: 50%;">
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $quotationOtherDetails['address'] }},
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $quotationOtherDetails['city_name'] }} {{ $quotationOtherDetails['postal_code'] }}
                            </p>
                            <p style="margin: 0;padding: 2px 0;">
                                {{ $quotationOtherDetails['phone_number'] }}
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
                            @if (!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 1)
                                <th colspan="2"
                                    style="vertical-align: bottom; padding: 4px 0px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    CGST
                                </th>
                                <th colspan="2"
                                    style="vertical-align: bottom; padding: 4px 0px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    SGST
                                </th>
                            @elseif(!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 2)
                                <th colspan="2"
                                    style="vertical-align: bottom; padding: 4px 0px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;">
                                    IGST
                                </th>
                            @endif
                            <th rowspan="2"
                                style="vertical-align: bottom; padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; background: #f2f3f4;text-align: right;">
                                Amount</th>
                        </tr>
                        @if (!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 1)
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
                        @elseif(!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 2)
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
                        @php
                            $total = 0;
                            $subTotal = 0;
                            $CGST_total = '0.00';
                            $SGST_total = '0.00';
                            $IGST_total = 0.0;
                        @endphp
                        @foreach ($quotation as $key => $cartItem)
                            @php
                                $product = \App\Models\Product::find($cartItem['product_id']);
                                $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                                $total = $total + cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
                                $subTotal = $subTotal + $cartItem['price'] * $cartItem['quantity'];
                                $product_name_with_choice = $product->getTranslation('name');
                                if ($cartItem['variation'] != null) {
                                    $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variation'];
                                }
                                
                                $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'];
                                $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'];
                                $IGST_total += $cartItem->tax * $cartItem['quantity'];
                            @endphp

                            <tr>
                                <td
                                    style="vertical-align: top;text-align: center;padding: 4px 8px;border-bottom: 1px solid;">
                                    <img src="{{ uploaded_asset($product->thumbnail_img) }}"
                                        alt="{{ $product->getTranslation('name') }}" width="80px">
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;"> {{ $product_name_with_choice }}</p>
                                    <p style="margin: 0;padding: 2px 0;">Details of the product can be seen in the
                                        Product Catalogue</p>
                                    <p style="margin: 0;padding: 2px 0;"> </p>
                                    <p style="margin: 0;padding: 2px 0;"> </p>
                                </td>
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    @if ($cartItem['digital'] != 1 && $product->auction_product == 0)
                                        <p style="margin: 0;padding: 2px 0;text-align: right;">
                                            {{ $cartItem['quantity'] }}</p>
                                    @elseif($product->auction_product == 1)
                                        <p style="margin: 0;padding: 2px 0;text-align: right;">1</p>
                                    @endif
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ strtoupper($product->unit) }} </p>
                                </td>
                                @php
                                    $priceWithoutTax = $cartItem['price'] - $cartItem['tax'];
                                @endphp
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ single_price($priceWithoutTax) }}</p>
                                </td>
                                @if (!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 1)
                                    <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                        <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $cartItem->tax1 }}%
                                        </p>
                                    </td>
                                    <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                        <p style="margin: 0;padding: 2px 0;text-align: right;">
                                            {{ single_price($cartItem->tax2_amount) }}</p>
                                    </td>
                                    <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                        <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $cartItem->tax2 }}%
                                        </p>
                                    </td>
                                    <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                        <p style="margin: 0;padding: 2px 0;text-align: right;">
                                            {{ single_price($cartItem->tax2_amount) }}</p>
                                    </td>
                                @elseif(!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 2)
                                    <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                        <p style="margin: 0;padding: 2px 0;text-align: right;"> {{ $cartItem->tax1 }}%
                                        </p>
                                    </td>
                                    <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                        <p style="margin: 0;padding: 2px 0;text-align: right;">
                                            {{ single_price($cartItem->tax2_amount) }}</p>
                                    </td>
                                @endif
                                <td style="padding: 4px 8px;border-bottom: 1px solid;border-left: 1px solid; ">
                                    <p style="margin: 0;padding: 2px 0;text-align: right;">
                                        {{ single_price($cartItem['price'] * $cartItem['quantity']) }} </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom: 100px;">
                <table align="center" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr>
                        <td style="padding: 8px;vertical-align: top;width: 60%;">
                            <p style="margin: 0;padding: 2px 0;"></p>

                            <p style="margin: 0;padding: 2px 0 30px;">
                                Shipping Charges are mentioned as per DHL rate.<BR>
                                Mode of Shipment:By Air
                            </p>


                            <p style="margin: 0;padding: 30px 0 0;">
                                <b> Terms & Conditions :</b>
                            </p>
                            <p style="margin: 0;padding: 2px 0;"> 1. Payment Should Be Made In favour of "Quicklab
                                Services" by NEFT/RTGS/WIRE TRANSFER </p>
                            <p style="margin: 0;padding: 2px 0;"> 2.Payment 100% advance. </p>
                            <p style="margin: 0;padding: 2px 0;"> 3.Bank Transfer Charges & Custom Clearances by Buyer.
                            </p>
                            <p style="margin: 0;padding: 2px 0;"> 4.Delivery Transport charges extra </p>
                            <p style="margin: 0;padding: 2px 0;"> 5.Subject to Chennai Jurisdiction only </p>
                            <p style="margin: 0;padding: 2px 0;"> 6.Subject to stock availability</p>
                            <p style="margin: 0;padding: 2px 0;"> 7.Validity of the PI - 2 weeks</p>
                        </td>
                        <td style="padding: 8px;vertical-align: top;width: 40%;padding: 0;">
                            <table align="center" border="0" cellspacing="0" cellpadding="0"
                                style="width: 100%;text-align: right;border-left: 1px solid;border-bottom: 1px solid;">
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;">Sub Total</p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b>{{ single_price($subTotal) }}</b>
                                    </td>
                                </tr>
                                @if (!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 1)
                                    <tr>
                                        <td style="padding: 5px 10px;">
                                            <p style="margin: 0;">CGST</p>
                                        </td>
                                        <td style="padding: 5px 10px;">
                                            <b>{{ single_price($CGST_total) }}</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 10px;">
                                            <p style="margin: 0;">SGST</p>
                                        </td>
                                        <td style="padding: 5px 10px;">
                                            <b>{{ single_price($SGST_total) }}</b>
                                        </td>
                                    </tr>
                                @elseif(!empty($quotationOtherDetails['taxAvailable']) && $quotationOtherDetails['taxAvailable'] == 2)
                                    <tr>
                                        <td style="padding: 5px 10px;">
                                            <p style="margin: 0;">IGST </p>
                                        </td>
                                        <td style="padding: 5px 10px;">
                                            <b>{{ single_price($IGST_total) }}</b>
                                        </td>
                                    </tr>
                                @endif
                                @php
                                    $result = roundPrice($total);
                                    if ($result) {
                                        $grandTotal = round($total);
                                        $roundingVal = $grandTotal - $total;
                                    } else {
                                        $grandTotal = floor($total);
                                        $roundingVal = $grandTotal - $total;
                                    }
                                    $roundingFinalResult = number_format($roundingVal, 2);
                                @endphp
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;">Rounding</p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b> {{ $roundingFinalResult }} </b>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;">
                                            <b>Total</b>
                                        </p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b> {{ single_price($total) }} </b>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 10px;">
                                        <p style="margin: 0;font-size: 16px;">
                                            <b>Balance Due</b>
                                        </p>
                                    </td>
                                    <td style="padding: 5px 10px;">
                                        <b style="font-size: 16px;"> {{ single_price($total) }} </b>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-top: 1px solid;">
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
        style="font-size: 12px;font-weight: 400;margin: 0;padding: 4px 0; text-align: center;font-family: Arial, Helvetica, sans-serif;">
        Reg Office : {{ get_setting('reg_office_address') }} .
        <b style="display: block;"> Head Office : {{ get_setting('contact_address') }}.</b>
    </p>
</body>

</html>
