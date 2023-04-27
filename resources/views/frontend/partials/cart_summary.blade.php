<div class="card rounded border-0 shadow-sm">
    <div class="card-header">
        <h3 class="fs-16 fw-600 mb-0">{{ translate('Summary') }}</h3>
        <div class="text-right">
            <span class="badge badge-inline badge-primary">
                {{ count($carts) }}
                {{ translate('Items') }}
            </span>
            @php
                $coupon_discount = 0;
            @endphp
            @if (Auth::check() && get_setting('coupon_system') == 1)
                @php
                    $coupon_code = null;
                @endphp

                @foreach ($carts as $key => $cartItem)
                    @php
                        $product = \App\Models\Product::find($cartItem['product_id']);
                    @endphp
                    @if ($cartItem->coupon_applied == 1)
                        @php
                            $coupon_code = $cartItem->coupon_code;
                            break;
                        @endphp
                    @endif
                @endforeach

                @php
                    $coupon_discount = carts_coupon_discount($coupon_code);
                @endphp
            @endif

            @php $subtotal_for_min_order_amount = 0; @endphp
            @foreach ($carts as $key => $cartItem)
                @php $subtotal_for_min_order_amount += cart_product_price($cartItem, $cartItem->product, false, false) * $cartItem['quantity']; @endphp
            @endforeach

            @if (get_setting('minimum_order_amount_check') == 1 && $subtotal_for_min_order_amount < get_setting('minimum_order_amount'))
                <span class="badge badge-inline badge-primary">
                    {{ translate('Minimum Order Amount') . ' ' . single_price(get_setting('minimum_order_amount')) }}
                </span>
            @endif
        </div>
    </div>

    <div class="card-body">
        @if (addon_is_activated('club_point'))
            @php
                $total_point = 0;
            @endphp
            @foreach ($carts as $key => $cartItem)
                @php
                    $product = \App\Models\Product::find($cartItem['product_id']);
                    $total_point += $product->earn_point * $cartItem['quantity'];
                @endphp
            @endforeach

            <div class="bg-soft-primary border-soft-primary mb-2 rounded border px-2">
                {{ translate('Total Club point') }}:
                <span class="fw-700 float-right">{{ $total_point }}</span>
            </div>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th class="product-name">{{ translate('Product') }}</th>
                    <th class="product-total text-right">{{ translate('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subtotal = 0;
                    $tax = 0;
                    $shipping_courier_cost = $shippingCourierCost;
                    $shipping = 0;
                    $product_shipping_cost = 0;
                    $shipping_region = $shipping_info['city'];
                    $shipWithCurrency = single_price($shipping_courier_cost);

                    $CGST_total = '0.00';
                        $SGST_total = '0.00';
                        $IGST_total = 0.00;
                        $GST_total = 0;

                @endphp
                @foreach ($carts as $key => $cartItem)
                    @php
                        $product = \App\Models\Product::find($cartItem['product_id']);

                        $product_shipping_cost = $cartItem['shipping_cost'];

                        $product_price = cart_product_price($cartItem, $product, false, false);
                        $product_txt = cart_product_tax($cartItem, $product,false);
                        $product_qty = $cartItem['quantity'];
                        if (Session::get('currency_code') == 'USD') {
                                                            $org_product_price = $cartItem['price'] * $cartItem['quantity'] ;

                                                        }else{
                                                            $org_product_price = ($cartItem['price'] - $cartItem['tax']) * $cartItem['quantity'] ;
                                                        }
                       // $org_product_price = $cartItem['price'];

                       // $subtotal += $org_product_price * $cartItem['quantity'];
                        $subtotal = $subtotal + ($product_price - $cartItem['tax']) * $cartItem['quantity'];
                        $tax += $cartItem['tax'] * $cartItem['quantity'];


                        $shipping += $product_shipping_cost;

                        $product_name_with_choice = $product->getTranslation('name');
                        if ($cartItem['variation'] != null) {
                            $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variation'];
                        }



                    @endphp
                    @if(!empty($checkUserAddress) && $checkUserAddress == 1)
                    @php
                        $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'];
                        $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'];
                    @endphp
                @endif
                @if(!empty($checkUserAddress) && $checkUserAddress == 2)
                    @php
                    $IGST_total += $cartItem->tax * $cartItem['quantity'];
                    @endphp
                @endif
                    <tr class="cart_item">
                        <td class="product-name">
                            {{ $product_name_with_choice }}
                            <strong class="product-quantity">
                                × {{ $cartItem['quantity'] }}
                            </strong>
                        </td>
                        <td class="product-total text-right">
                            <span
                                class="pl-4 pr-0">{{ number_format ($org_product_price,2) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <input type="hidden" id="sub_total" value="{{ $subtotal }}">
        <table class="table">

            <tfoot>
                <tr class="cart-subtotal">
                    <th>{{ translate('Product Total') }}</th>
                    <td class="text-right">
                        <span class="fw-600">{{ single_price($subtotal) }}</span>
                    </td>
                </tr>
                @php
                if(Session::get('currency_code') == 'INR'){

                @endphp

                @if(!empty($checkUserAddress) && $checkUserAddress == 1)
                <tr class="cart-shipping">
                    <th>{{ translate('CGST') }}</th>
                    <td class="text-right">
                        <span class="font-italic">{{ single_price($CGST_total) }}</span>
                    </td>
                </tr>
                <tr class="cart-shipping">
                    <th>{{ translate('SGST') }}</th>
                    <td class="text-right">
                        <span class="font-italic">{{ single_price($SGST_total) }}</span>
                    </td>
                </tr>

                @endif
                @if(!empty($checkUserAddress) && $checkUserAddress == 2)
                <tr class="cart-shipping">
                    <th>{{ translate('IGST') }}</th>
                    <td class="text-right">
                        <span class="font-italic">{{ single_price($IGST_total) }}</span>
                    </td>
                </tr>


                @endif



                @php
                    }
                    $sub_total = $subtotal + $CGST_total + $SGST_total + $IGST_total;
                   // $sub_total =
                @endphp

                <tr class="cart-shipping">
                    <th>{{ translate('Subtotal') }}</th>
                    <td class="text-right">
                        <span class="font-italic">{{ single_price($sub_total) }}</span>
                    </td>
                </tr>




<?php

if(Session::get('currency_code') == 'INR'){
    $total = $subtotal + $tax + $shipping + $shipping_courier_cost;

}else{
    $total = $subtotal + $shipping + $shipping_courier_cost;
}


    if (Session::has('club_point')) {
        $total -= Session::get('club_point');
    }
    if ($coupon_discount > 0) {
        $total -= $coupon_discount;
    }
    $result = roundPrice($total);

            if ($result) {
                $grandTotal = round($total);
                $roundingVal = $grandTotal - $total;
            } else {

                $grandTotal = floor($total);
                $roundingVal = $grandTotal - $total;
            }

            $roundingFinalResult = number_format($roundingVal, 2);
            ?>



                <tr class="cart-shipping">
                    <th>{{ translate('Total Shipping') }}</th>

                    <td class="text-right">
                        <span class="font-italic" id="shipping_charge">{{ single_price($shipping_courier_cost) }}</span>
                        <div id="shipping_estimate_country">

                            <span id="postcodeErr_us" style="color: red;"></span>

                        </div>

                        <div id="shipping_estimate" style="display:none;">
                            <select name="country" id="country">
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="postcode" id="postcode" value="">
                            <span id="postcodeErr" style="color: red;"></span>
                            <button onclick="getShippingCouriers()">Update</button>
                        </div>
                    </td>
                </tr>
                <tr class="cart-shipping">
                    <th>{{ translate('Rounding') }}</th>
                    <td class="text-right">
                        <span class="font-italic" id="rounding_charge">{{ single_price($roundingFinalResult) }}</span>
                    </td>
                </tr>
                @if (!empty(Auth::user()->country) && Auth::user()->country == 101)
                    <tr>
                        <td><button onclick="calculateShipping()">Calculate</button></td>
                    </tr>
                @endif
                <table class="bordered">
                    <div id="shipping_couriers_list">

                    </div>
                </table>

                @if (Session::has('club_point'))
                    <tr class="cart-shipping">
                        <th>{{ translate('Redeem point') }}</th>
                        <td class="text-right">
                            <span class="font-italic">{{ single_price(Session::get('club_point')) }}</span>
                        </td>
                    </tr>
                @endif

                @if ($coupon_discount > 0)
                    <tr class="cart-shipping">
                        <th>{{ translate('Coupon Discount') }}</th>
                        <td class="text-right">
                            <span class="font-italic">{{ single_price($coupon_discount) }}</span>
                        </td>
                    </tr>
                @endif

                @php

                    $totalWithCurrency = single_price($total);
                @endphp
                <input type="hidden" name="totalWithCurrency" id="totalWithCurrency" value='<?= $totalWithCurrency ?>'>
                <input type="hidden" name="total" id="total" value='<?= $total ?>'>
                <input type="hidden" name="shipping_courier_default_cost" id="shipping_courier_default_cost" value='<?= $shipping_courier_cost ?>'>
                <input type="hidden" name="shipWithCurrency" id="shipWithCurrency" value='<?= $shipWithCurrency ?>'>

                <tr class="cart-total">
                    <th><span class="strong-600">{{ translate('Total') }}</span></th>
                    <td class="text-right">
                        <strong><span id="grand_total">{{ single_price($total + $roundingFinalResult) }}</span></strong>
                    </td>
                </tr>
            </tfoot>
        </table>

        @if (addon_is_activated('club_point'))
            @if (Session::has('club_point'))
                <div class="mt-3">
                    <form class="" action="{{ route('checkout.remove_club_point') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="input-group">
                            <div class="form-control">{{ Session::get('club_point') }}</div>
                            <div class="input-group-append">
                                <button type="submit"
                                    class="btn btn-primary">{{ translate('Remove Redeem Point') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        @endif

        @if (Auth::check() && get_setting('coupon_system') == 1)
            @if ($coupon_discount > 0 && $coupon_code)
                <div class="mt-3">
                    <form class="" id="remove-coupon-form" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group">
                            <div class="form-control">{{ $coupon_code }}</div>
                            <div class="input-group-append">
                                <button type="button" id="coupon-remove"
                                    class="btn btn-primary">{{ translate('Change Coupon') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <div class="mt-3">
                {{--   <form class="" id="apply-coupon-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="owner_id" value="{{ $carts[0]['owner_id'] }}">
                         <div class="input-group">
                            <input type="text" class="form-control" name="code"
                                onkeydown="return event.key != 'Enter';"
                                placeholder="{{ translate('Have coupon code? Enter here') }}" required>
                            <div class="input-group-append">
                                <button type="button" id="coupon-apply"
                                    class="btn btn-primary">{{ translate('Apply') }}</button>
                            </div>
                        </div>
                    </form> --}}
                </div>
            @endif
        @endif

    </div>
</div>



