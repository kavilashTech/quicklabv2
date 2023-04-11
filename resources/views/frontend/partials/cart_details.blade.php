<div class="container">
    @if( $carts && count($carts) > 0 )
        <div class="row">
            <div class="col-xxl-8 col-xl-10 mx-auto">
                <div class="shadow-sm bg-white p-3 p-lg-4 rounded text-left">
                    @php
                        $CGST_total = '0.00';
                        $SGST_total = '0.00';
                        $IGST_total = 0.00;
                        $GST_total = 0;
                    @endphp
                    <div class="mb-4">
                        <div class="row gutters-5 d-none d-lg-flex border-bottom mb-3 pb-3">
                            <div class="col-md-1 fw-600">{{ translate('Product')}}</div>
                            <div class="col fw-600">{{ translate('Price')}}</div>
                            <!-- <div class="col fw-600">{{ translate('Tax')}}</div> -->
                            <div class="col fw-600">{{ translate('Quantity')}}</div>

                            @if(!empty($checkUserAddress) && $checkUserAddress == 1)
                                <div class="col fw-600">{{ translate('CGST %') }}</div>
                                <div class="col fw-600">{{ translate('CGST Amount') }}</div>
                                <div class="col fw-600">{{ translate('SGST %') }}</div>
                                <div class="col fw-600">{{ translate('SGST Amount') }}</div>
                            @endif
                            @if(!empty($checkUserAddress) && $checkUserAddress == 2)
                                <div class="col fw-600">{{ translate('IGST %') }}</div>
                                <div class="col fw-600">{{ translate('IGST Amount') }}</div>
                            @endif

                            <div class="col fw-600">{{ translate('Total')}}</div>
                            <div class="col-auto fw-600">{{ translate('Remove')}}</div>
                        </div>
                        <ul class="list-group list-group-flush">
                            @php
                                $total = 0;
                                $subTotal = 0;
                            @endphp
                            @foreach ($carts as $key => $cartItem)
                                @php
                                    $product = \App\Models\Product::find($cartItem['product_id']);
                                    $product_price = discounted_cart_variant_price($cartItem['variation'],$product,false);
                                    $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                                    if(!empty($checkUserAddress) && $checkUserAddress != '0'){
                                       // $total = $total + ($cartItem['price']  + $cartItem['tax']) * $cartItem['quantity'];
                                       $total = $total + ( $product_price - $cartItem['tax'] + $cartItem['tax']) * $cartItem['quantity'];
                                    }
                                    else{
                                        //$total = $total + ($cartItem['price']) * $cartItem['quantity'];
                                        $total = $total + ( $product_price - $cartItem['tax'] + $cartItem['tax']) * $cartItem['quantity'];
                                    }
                                    //$subTotal = $subTotal + ($cartItem['price']) * $cartItem['quantity'];
                                    $subTotal = $subTotal + ($product_price - $cartItem['tax']) * $cartItem['quantity'];

                                    $product_name_with_choice = $product->getTranslation('name');
                                    if ($cartItem['variation'] != null) {
                                        $product_name_with_choice = $product->getTranslation('name').' - '.$cartItem['variation'];
                                    }
                                @endphp
                                <li class="list-group-item px-0 px-lg-3">
                                    <div class="row gutters-5">
                                        <div class="col-lg-1">
                                            <span class="mr-2 ml-0">
                                                <img
                                                    src="{{ uploaded_asset($product->thumbnail_img) }}"
                                                    class="img-fit size-60px rounded"
                                                    alt="{{ $product->getTranslation('name')  }}"
                                                >
                                            </span>
                                            <span class="fs-14 opacity-60 d-block">{{ $product_name_with_choice }}</span>
                                        </div>
                                        @php
                                                        if (Session::get('currency_code') == 'USD') {
                                                            $priceWithoutTax = $cartItem['price'] ;

                                                        }else{
                                                            $priceWithoutTax = $cartItem['price'] - $cartItem['tax'];
                                                        }

                                                        @endphp

                                        <div class="col-lg col-4 order-1 order-lg-0 my-3 my-lg-0">
                                            <span class="opacity-60 fs-12 d-block d-lg-none">{{ translate('Price')}}</span>
                                            <span class="fw-600 fs-16">{{ single_price($priceWithoutTax) }}</span>
                                        </div>
                                        <!-- <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                            <span class="opacity-60 fs-12 d-block d-lg-none">{{ translate('Tax')}}</span>
                                            <span class="fw-600 fs-16">{{ single_price($cartItem['tax']) }}</span>
                                        </div> -->


                                        <div class="col-lg col-6 order-4 order-lg-0">
                                            @if($cartItem['digital'] != 1)
                                                <div class="row no-gutters align-items-center aiz-plus-minus mr-2 ml-0">
                                                    <button class="btn col-auto btn-icon btn-sm btn-circle btn-light" type="button" data-type="minus" data-field="quantity[{{ $cartItem['id'] }}]">
                                                        <i class="las la-minus"></i>
                                                    </button>
                                                    <input type="number" name="quantity[{{ $cartItem['id'] }}]" class="col border-0 text-center flex-grow-1 fs-16 input-number" placeholder="1" value="{{ $cartItem['quantity'] }}" min="{{ $product->min_qty }}" max="{{ $product_stock->qty }}" onchange="updateQuantity({{ $cartItem['id'] }}, this)">
                                                    <button class="btn col-auto btn-icon btn-sm btn-circle btn-light" type="button" data-type="plus" data-field="quantity[{{ $cartItem['id'] }}]">
                                                        <i class="las la-plus"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        @if(!empty($checkUserAddress) && $checkUserAddress == 1)
                                            <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                <span
                                                    class="opacity-60 fs-12 d-block d-lg-none">{{ translate('CGST %') }}</span>
                                                <span
                                                    class="fw-600 fs-16">{{ $cartItem->tax1 }}</span>
                                            </div>

                                            <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                <span
                                                    class="opacity-60 fs-12 d-block d-lg-none">{{ translate('CGST Amount') }}</span>
                                                <span
                                                        class="fw-600 fs-16">{{ $cartItem->tax1_amount * $cartItem['quantity']}}</span>
                                                @php
                                                $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'];
                                                @endphp
                                            </div>

                                            <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                <span
                                                    class="opacity-60 fs-12 d-block d-lg-none">{{ translate('SGST %') }}</span>
                                                <span
                                                    class="fw-600 fs-16">{{ $cartItem->tax2 }}</span>
                                            </div>

                                            <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                <span
                                                    class="opacity-60 fs-12 d-block d-lg-none">{{ translate('SGST Amount') }}</span>
                                                <span
                                                        class="fw-600 fs-16">{{ $cartItem->tax2_amount * $cartItem['quantity']}}</span>
                                                @php
                                                $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'];
                                                @endphp
                                            </div>
                                        @endif

                                        @if(!empty($checkUserAddress) && $checkUserAddress == 2)
                                            <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                <span
                                                class="opacity-60 fs-12 d-block d-lg-none">{{ translate('IGST %') }}</span>
                                                <span
                                                    class="fw-600 fs-16">{{ $cartItem->tax_percentage }}</span>
                                            </div>

                                            <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                <span
                                                class="opacity-60 fs-12 d-block d-lg-none">{{ translate('IGST Amount') }}</span>
                                                <span
                                                class="fw-600 fs-16">{{ $cartItem->tax * $cartItem['quantity']}}</span>
                                                @php
                                                $IGST_total += $cartItem->tax * $cartItem['quantity'];
                                                @endphp
                                            </div>
                                        @endif

                                        <div class="col-lg col-4 order-3 order-lg-0 my-3 my-lg-0">
                                            <span class="opacity-60 fs-12 d-block d-lg-none">{{ translate('Total')}}</span>
                                            <!-- <span class="fw-600 fs-16 text-primary">{{ single_price(($product_price) * $cartItem['quantity']) }}</span> -->
                                            <span class="fw-600 fs-16 text-primary">{{ single_price(($priceWithoutTax) * $cartItem['quantity']) }}</span>
                                        </div>
                                        <div class="col-lg-auto col-6 order-5 order-lg-0 text-right">
                                            <a href="javascript:void(0)" onclick="removeFromCartView(event, {{ $cartItem['id'] }})" class="btn btn-icon btn-sm btn-soft-primary btn-circle">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="px-3 py-2 border-top d-flex justify-content-end">
                        <span class="opacity-60 fs-15">{{translate('Product Total')}}</span>
                        <span class="fw-600 fs-17 pl-2">{{ single_price($subTotal) }}</span>
                    </div>

                    @if(!empty($checkUserAddress) && $checkUserAddress == 1)
                        <div class="px-3 py-2 d-flex justify-content-end mt-n3">
                            <span class="opacity-60 fs-15">{{ translate('CGST') }}</span>
                            <span class="fw-600 fs-17 pl-3">{{ single_price($CGST_total) }}</span>
                        </div>

                        <div class="px-3 py-2 d-flex justify-content-end mt-n3">
                            <span class="opacity-60 fs-15">{{ translate('SGST') }}</span>
                            <span class="fw-600 fs-17 pl-3">{{ single_price($SGST_total) }}</span>
                        </div>
                    @endif
                    @if(!empty($checkUserAddress) && $checkUserAddress == 2)
                        <div class="px-3 py-2 d-flex justify-content-end mt-n3">
                            <span class="opacity-60 fs-15">{{ translate('IGST') }}</span>
                            <span class="fw-600 fs-17 pl-3">{{ single_price($IGST_total) }}</span>
                        </div>
                    @endif
                    <div class="px-3 py-2 d-flex justify-content-end mt-n3">
                        <span class="opacity-60 fs-15">{{translate('Total')}}</span>
                        <span class="fw-600 fs-17 pl-2">{{ single_price($total) }}</span>
                    </div>
                    <!-- <div class="px-3 py-2 d-flex justify-content-end mt-n3">
                        <span class="opacity-60 fs-15">{{translate('Balance Due')}}</span>
                        <span class="fw-600 fs-17 pl-2">{{ single_price($total) }}</span>
                    </div> -->
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center text-md-left order-1 order-md-0">
                            <a href="{{ route('home') }}" class="btn btn-link">
                                <i class="las la-arrow-left"></i>
                                {{ translate('Return to shop')}}
                            </a>
                        </div>
                        <div class="col-md-6 text-center text-md-right">
                            @if(Auth::check())
                                <a href="{{ route('checkout.shipping_info') }}" class="btn btn-primary fw-600">
                                    {{ translate('Continue to Shipping')}}
                                </a>
                            @else
                                <button class="btn btn-primary fw-600" onclick="showCheckoutModal()">{{ translate('Continue to Shipping')}}</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xl-8 mx-auto">
                <div class="shadow-sm bg-white p-4 rounded">
                    <div class="text-center p-3">
                        <i class="las la-frown la-3x opacity-60 mb-3"></i>
                        <h3 class="h4 fw-700">{{translate('Your Cart is empty')}}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script type="text/javascript">
    AIZ.extra.plusMinus();
</script>