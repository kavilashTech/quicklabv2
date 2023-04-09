<?php

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\City;
use App\Models\Shop;
use App\Models\User;
use App\Models\Addon;
use App\Models\Coupon;
use App\Models\Seller;
use App\Models\Upload;
use App\Models\Wallet;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\Country;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Tax;
use App\Models\CouponUsage;
use App\Models\Translation;
use App\Models\ProductStock;
use App\Models\CombinedOrder;
use App\Models\Order;
use App\Models\SellerPackage;
use App\Models\State;
use App\Models\BusinessSetting;
use App\Models\CustomerPackage;
use App\Utility\SendSMSUtility;
use App\Utility\CategoryUtility;
use App\Models\SellerPackagePayment;
use App\Utility\NotificationUtility;
use App\Http\Resources\V2\CarrierCollection;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\ClubPointController;
use App\Http\Controllers\CommissionController;

//sensSMS function for OTP
if (!function_exists('sendSMS')) {
    function sendSMS($to, $from, $text, $template_id)
    {
        return SendSMSUtility::sendSMS($to, $from, $text, $template_id);
    }
}

//highlights the selected navigation on admin panel
if (!function_exists('areActiveRoutes')) {
    function areActiveRoutes(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('areActiveRoutesHome')) {
    function areActiveRoutesHome(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('default_language')) {
    function default_language()
    {
        return env("DEFAULT_LANGUAGE");
    }
}

/**
 * Save JSON File
 * @return Response
 */
if (!function_exists('convert_to_usd')) {
    function convert_to_usd($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'USD')->first()->exchange_rate;
    }
}

if (!function_exists('convert_to_kes')) {
    function convert_to_kes($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'KES')->first()->exchange_rate;
    }
}

//filter products based on vendor activation system
if (!function_exists('filter_products')) {
    function filter_products($products)
    {
        $verified_sellers = verified_sellers_id();
        if (get_setting('vendor_system_activation') == 1) {
            return $products->where('approved', '1')
                ->where('published', '1')
                ->where('auction_product', 0)
                ->where(function ($p) use ($verified_sellers) {
                    $p->where('added_by', 'admin')->orWhere(function ($q) use ($verified_sellers) {
                        $q->whereIn('user_id', $verified_sellers);
                    });
                });
        } else {
            return $products->where('published', '1')->where('auction_product', 0)->where('added_by', 'admin');
        }
    }
}

//cache products based on category
if (!function_exists('get_cached_products')) {
    function get_cached_products($category_id = null)
    {
        $products = \App\Models\Product::where('published', 1)->where('approved', '1')->where('auction_product', 0);
        $verified_sellers = verified_sellers_id();
        if (get_setting('vendor_system_activation') == 1) {
            $products = $products->where(function ($p) use ($verified_sellers) {
                $p->where('added_by', 'admin')->orWhere(function ($q) use ($verified_sellers) {
                    $q->whereIn('user_id', $verified_sellers);
                });
            });
        } else {
            $products = $products->where('added_by', 'admin');
        }

        if ($category_id != null) {
            return Cache::remember('products-category-' . $category_id, 86400, function () use ($category_id, $products) {
                $category_ids = CategoryUtility::children_ids($category_id);
                $category_ids[] = $category_id;
                return $products->whereIn('category_id', $category_ids)->latest()->take(12)->get();
            });
        } else {
            return Cache::remember('products', 86400, function () use ($products) {
                return $products->latest()->take(12)->get();
            });
        }
    }
}

if (!function_exists('verified_sellers_id')) {
    function verified_sellers_id()
    {
        return Cache::rememberForever('verified_sellers_id', function () {
            return App\Models\Shop::where('verification_status', 1)->pluck('user_id')->toArray();
        });
    }
}

if (!function_exists('get_system_default_currency')) {

    function getRealUserIp(){
        switch(true){
          case (!empty($_SERVER['HTTP_X_REAL_IP'])) : return $_SERVER['HTTP_X_REAL_IP'];
          case (!empty($_SERVER['HTTP_CLIENT_IP'])) : return $_SERVER['HTTP_CLIENT_IP'];
          case (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) : return $_SERVER['HTTP_X_FORWARDED_FOR'];
          default : return $_SERVER['REMOTE_ADDR'];
        }
     }
    function get_system_default_currency()
    {
        return Cache::remember('system_default_currency', 86400, function () {
            //$ip = getRealUserIp();
           $ip = '152.58.214.63'; //india
            //$ip = '27.122.14.74';  //kong
            $service_url = 'http://ip-api.com/php/' . $ip;
            $curl = curl_init($service_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $curl_response = curl_exec($curl);
            if ($curl_response === false) {
                $info = curl_getinfo($curl);
                curl_close($curl);
                //die('error occured during curl exec. Additioanl info: ' . var_export($info));
            }
            curl_close($curl);
            // $decoded = json_decode($curl_response);
            $decoded =unserialize($curl_response);
           // dd($decoded);
            if(isset($decoded)){
                if($decoded['countryCode'] == 'IN'){
                    $code = 'INR';

                    $currency = Currency::where('code', $code)->first();

                    session()->put('currency_code', $currency->code);
                    session()->put('currency_symbol', $currency->symbol);

                    return Currency:: where('code', 'like', '%'.$code.'%')->first();
                }else{
                    $code = 'USD';
                    $currency = Currency::where('code', $code)->first();
                    session()->put('currency_code', $currency->code);
                    session()->put('currency_symbol', $currency->symbol);
                    return Currency:: where('code', 'like', '%'.$code.'%')->first();
                }
            }else{

            }

            //dd(Currency::find(1));
           // return Currency::find(1);
        });
    }
}


//TODO : Currency Set based on Geo Ip
//converts currency to home default currency
if (!function_exists('convert_price')) {
    function convert_price($price)
    {
        $allSessions = Session::all();
        //dd(get_system_default_currency()->code);
        if (Session::has('currency_code') && (Session::get('currency_code') == 'USD')) {
            return $price;
            // $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            // $price = floatval($price) * floatval(Session::get('currency_exchange_rate'));
        }
        return $price;
    }
}

//gets currency symbol
if (!function_exists('currency_symbol')) {
    function currency_symbol()
    {
        if((auth()->user() != null) && (auth()->user()->user_type == 'customer')) {
            $code = auth()->user()->country ;
            $country_data = Country :: find(auth()->user()->country);
            $code = ($country_data->code == 'IN') ?'INR' :'USD';


            $currency =Currency::where('code', $code)->first();
            if($currency != ''){
                if($currency->code == 'INR'){
                    session()->put('currency_code', $currency->code);
                    session()->put('currency_symbol', $currency->symbol);
                    return Session::get('currency_symbol');

                }else{
                    $code = 'USD';
                    $currency = Currency::where('code', $code)->first();
                    session()->put('currency_code', $currency->code);
                    session()->put('currency_symbol', $currency->symbol);
                    return Session::get('currency_symbol');
                }

            }else{
                    $code = 'USD';
                    $currency = Currency::where('code', $code)->first();
                    session()->put('currency_code', $currency->code);
                    session()->put('currency_symbol', $currency->symbol);
                    return Session::get('currency_symbol');

            }




        }else{
            if (Session::has('currency_symbol')) {

                return Session::get('currency_symbol');
            }
            return get_system_default_currency()->symbol;

        }

    }
}

//formats currency
if (!function_exists('format_price')) {
    function format_price($price, $isMinimize = false)
    {
        if (get_setting('decimal_separator') == 1) {
            $fomated_price = number_format($price, get_setting('no_of_decimals'));
        } else {
            $fomated_price = number_format($price, get_setting('no_of_decimals'), ',', '.');
        }


        // Minimize the price
        if ($isMinimize) {
            $temp = number_format($price / 1000000000, get_setting('no_of_decimals'), ".", "");

            if ($temp >= 1) {
                $fomated_price = $temp . "B";
            } else {
                $temp = number_format($price / 1000000, get_setting('no_of_decimals'), ".", "");
                if ($temp >= 1) {
                    $fomated_price = $temp . "M";
                }
            }
        }

        if (get_setting('symbol_format') == 1) {
            return currency_symbol() . $fomated_price;
        } else if (get_setting('symbol_format') == 3) {
            return currency_symbol() . ' ' . $fomated_price;
        } else if (get_setting('symbol_format') == 4) {
            return $fomated_price . ' ' . currency_symbol();
        }
        return $fomated_price . currency_symbol();
    }
}


//formats price to home default price with convertion
if (!function_exists('single_price')) {
    function single_price($price)
    {
        return format_price(convert_price($price));
    }
}

if (!function_exists('discount_in_percentage')) {
    function discount_in_percentage($product)
    {
        $base = home_base_price($product, false);
        $reduced = home_discounted_base_price($product, false);
        $discount = $base - $reduced;
        $dp = ($discount * 100) / ($base > 0 ? $base : 1);
        return round($dp);
    }
}

//Shows Price on page based on carts
if (!function_exists('cart_product_price')) {
    function cart_product_price($cart_product, $product, $formatted = true, $tax = true)
    {
        if ($product->auction_product == 0) {
            $str = '';
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $price = 0;
            $product_stock = $product->stocks->where('variant', $str)->first();

            if ($product_stock) {
                if (Session::get('currency_code') == 'USD') {
                    $price = ($product_stock != '') ? $product_stock->usd_price : '99443'.$product->id;
                }else{
                    $price = $product_stock->price;
                }
            }


            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $price -= ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $price -= $product->discount;
                }
            }

        } else {
            $price = $product->bids->max('amount');
        }
        //dd($tax);

        //calculation of taxes
        if ($tax) {
            $taxAmount = 0;
            foreach ($product->taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $taxAmount += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $taxAmount += $product_tax->tax;
                }
            }
            $price += $taxAmount;
        }

        if ($formatted) {
            return format_price(convert_price($price));
        } else {
            return $price;
        }
    }
}

if (!function_exists('cart_product_tax')) {
    function cart_product_tax($cart_product, $product, $formatted = true)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();

        if ($product_stock) {


            if (Session::get('currency_code') == 'USD') {


                $price = ($product_stock != '') ? $product_stock->usd_price : '99443'.$product->id;
            }else{
                $price = $product_stock->price;
            }



        }
        //$price = $product_stock->price;

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        //calculation of taxes
        $tax = 0;
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        if ($formatted) {
            return format_price(convert_price($tax));
        } else {
            return $tax;
        }
    }
}

if (!function_exists('cart_product_discount')) {
    function cart_product_discount($cart_product, $product, $formatted = false)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        if ($product_stock) {
            if (Session::get('currency_code') == 'USD') {
                $price = ($product_stock != '') ? $product_stock->usd_price : '99443' . $product->id;
            } else {
                $price = $product_stock->price;
            }
        }
        //discount calculation
        $discount_applicable = false;
        $discount = 0;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $discount = ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $discount = $product->discount;
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// all discount
if (!function_exists('carts_product_discount')) {
    function carts_product_discount($cart_products, $formatted = false)
    {
        $discount = 0;
        foreach ($cart_products as $key => $cart_product) {
            $str = '';
            $product = \App\Models\Product::find($cart_product['product_id']);
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $product_stock = $product->stocks->where('variant', $str)->first();
            if ($product_stock) {
                if (Session::get('currency_code') == 'USD') {
                    $price = ($product_stock != '') ? $product_stock->usd_price : '99443' . $product->id;
                } else {
                    $price = $product_stock->price;
                }
            }
            //$price = $product_stock->price;

            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $discount += ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $discount += $product->discount;
                }
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

if (!function_exists('carts_coupon_discount')) {
    function carts_coupon_discount($code, $formatted = false)
    {
        $coupon = Coupon::where('code', $code)->first();
        $coupon_discount = 0;
        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $carts = Cart::where('user_id', Auth::user()->id)
                        ->where('owner_id', $coupon->user_id)
                        ->get();

                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;

                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }
                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($coupon_discount > 0) {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => $coupon_discount / count($carts),
                        ]
                    );
            } else {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => 0,
                            'coupon_code' => null,
                        ]
                    );
            }
        }

        if ($formatted) {
            return format_price(convert_price($coupon_discount));
        } else {
            return $coupon_discount;
        }
    }
}

//Shows Price on page based on low to high
if (!function_exists('single_currency_price')) {
    function single_currency_price($product, $formatted = true){

        $price = $product->price;

        return $price;

    }


}
if (!function_exists('home_price')) {
    function home_price($product, $formatted = true)
    {


        if (Session::get('currency_code') == 'USD') {

            $lowest_price = $product->stocks[0]['usd_price'];
            $highest_price = $product->stocks[0]['usd_price'];
        } else {
            $lowest_price = $product->unit_price;
            $highest_price = $product->unit_price;
        }



        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

        /*foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                $highest_price += ($highest_price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $lowest_price += $product_tax->tax;
                $highest_price += $product_tax->tax;
            }
        }*/

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return format_price(convert_price($lowest_price));
            } else {
                return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}


if (!function_exists('home_price2')) {
    function home_price2($product, $formatted = true)
    {
        $lowest_price = $product->unit_price;
        $highest_price = $product->unit_price;

        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

        /*foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                $highest_price += ($highest_price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $lowest_price += $product_tax->tax;
                $highest_price += $product_tax->tax;
            }
        }*/

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return convert_price($lowest_price);
            } else {
                return convert_price($lowest_price) . ' - ' . convert_price($highest_price);
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

if (!function_exists('discounted_cart_variant_price')) {
    function discounted_cart_variant_price($variation,$product, $formatted = true)
    {


        if($product->variant_product == 0){
            //single product
            $product_stock = ProductStock::where('product_id',$product->id)->first();

        }else{
            //varient product
            $product_stock = ProductStock::where('product_id',$product->id)->where('variant',$variation)->first();

        }

        // get first variant price
        if (Session::get('currency_code') == 'USD') {
            $variantPrice = $product_stock->usd_price;
        } else {
            $variantPrice = $product_stock->price;
        }

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $variantPrice -= ($variantPrice * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $variantPrice -= $product->discount;
            }
        }

        if ($formatted) {
            return format_price(convert_price($variantPrice));
        } else {
            return $variantPrice;
        }
    }
}


//TODO: get the product variant first price
if (!function_exists('discounted_variant_price')) {
    function discounted_variant_price($product, $formatted = true)
    {
        // get first variant price
        if (Session::get('currency_code') == 'USD') {
            $variantPrice = $product->stocks[0]['usd_price'];
        } else {
            $variantPrice = $product->stocks[0]['price'];
        }

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $variantPrice -= ($variantPrice * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $variantPrice -= $product->discount;
            }
        }

        if ($formatted) {
            return format_price(convert_price($variantPrice));
        } else {
            return $variantPrice;
        }
    }
}

//Shows Price on page based on low to high with discount
if (!function_exists('home_discounted_price')) {
    function home_discounted_price($product, $formatted = true)
    {


        if (Session::get('currency_code') == 'USD') {

            $lowest_price = $product->stocks[0]['usd_price'];
            $highest_price = $product->stocks[0]['usd_price'];
        } else {
            $lowest_price = $product->unit_price;
            $highest_price = $product->unit_price;
        }


        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $lowest_price -= ($lowest_price * $product->discount) / 100;
                $highest_price -= ($highest_price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $lowest_price -= $product->discount;
                $highest_price -= $product->discount;
            }
        }

        // foreach ($product->taxes as $product_tax) {
        //     if ($product_tax->tax_type == 'percent') {
        //         $lowest_price += ($lowest_price * $product_tax->tax) / 100;
        //         $highest_price += ($highest_price * $product_tax->tax) / 100;
        //     } elseif ($product_tax->tax_type == 'amount') {
        //         $lowest_price += $product_tax->tax;
        //         $highest_price += $product_tax->tax;
        //     }
        // }

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return format_price(convert_price($lowest_price));
            } else {
                return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

//Shows Price on page based on low to high with discount
if (!function_exists('home_discounted_price2')) {
    function home_discounted_price2($product, $formatted = true)
    {
        if (Session::get('currency_code') == 'USD') {

            $lowest_price = $product->stocks[0]['usd_price'];
            $highest_price = $product->stocks[0]['usd_price'];
        } else {
            $lowest_price = $product->unit_price;
            $highest_price = $product->unit_price;
        }

        //$lowest_price = $product->unit_price;
        //$highest_price = $product->unit_price;

        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if (Session::get('currency_code') == 'USD') {
                    if ($lowest_price > $stock->usd_price) {
                        $lowest_price = $stock->usd_price;
                    }
                    if ($highest_price < $stock->usd_price) {
                        $highest_price = $stock->usd_price;
                    }

                }else{
                    if ($lowest_price > $stock->price) {
                        $lowest_price = $stock->price;
                    }
                    if ($highest_price < $stock->price) {
                        $highest_price = $stock->price;
                    }

                }

            }
        }

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $lowest_price -= ($lowest_price * $product->discount) / 100;
                $highest_price -= ($highest_price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $lowest_price -= $product->discount;
                $highest_price -= $product->discount;
            }
        }

        // foreach ($product->taxes as $product_tax) {
        //     if ($product_tax->tax_type == 'percent') {
        //         $lowest_price += ($lowest_price * $product_tax->tax) / 100;
        //         $highest_price += ($highest_price * $product_tax->tax) / 100;
        //     } elseif ($product_tax->tax_type == 'amount') {
        //         $lowest_price += $product_tax->tax;
        //         $highest_price += $product_tax->tax;
        //     }
        // }

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return convert_price($lowest_price);
            } else {
                return convert_price($lowest_price) . ' - ' . convert_price($highest_price);
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

//Shows Base Price
if (!function_exists('home_base_price_by_stock_id')) {
    function home_base_price_by_stock_id($id)
    {
        $product_stock = ProductStock::findOrFail($id);
        $price = (Session::get('currency_code') == 'USD') ? $product_stock->usd_price : $product_stock->price;

        $tax = 0;

        foreach ($product_stock->product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        //$price += $tax;
        return format_price(convert_price($price));
    }
}
if (!function_exists('home_base_price')) {
    function home_base_price($product, $formatted = true)
    {
        if (Session::get('currency_code') == 'USD') {
            $product_stock = ProductStock::where('product_id',$product->id)->first();

            $price = ($product_stock != '') ? $product_stock->usd_price : '99443'.$product->id;;

        } else {
            $price = $product->unit_price;
        }
       // $price = $product->unit_price;
        $tax = 0;

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        //$price += $tax;
        return $formatted ? format_price(convert_price($price)) : $price;
    }
}

//Shows Base Price with discount
if (!function_exists('home_discounted_base_price_by_stock_id')) {
    function home_discounted_base_price_by_stock_id($id)
    {
        $product_stock = ProductStock::findOrFail($id);
        $product = $product_stock->product;
        if (Session::get('currency_code') == 'USD') {

            $price = ($product_stock != '') ? $product_stock->usd_price : '99443'.$product->id;
        }
        else{
            $price = $product_stock->price;
        }

        $tax = 0;

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        //$price += $tax;

        return format_price(convert_price($price));
    }
}

//Shows Base Price with discount
if (!function_exists('home_discounted_base_price')) {
    function home_discounted_base_price($product, $formatted = true)
    {

        if (Session::get('currency_code') == 'USD') {
            $product_stock = ProductStock::where('product_id',$product->id)->first();
            if($product_stock != ''){
                $price = $product_stock->usd_price;
            }else{
                $price = '99443'.$product->id;
            }





        } else {
            $price = $product->unit_price;
        }

        //$price = $product->unit_price;
        $tax = 0;

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        //$price += $tax;

        return $formatted ? format_price(convert_price($price)) : $price;
    }
}

if (!function_exists('renderStarRating')) {
    function renderStarRating($rating, $maxRating = 5)
    {
        $fullStar = "<i class = 'las la-star active'></i>";
        $halfStar = "<i class = 'las la-star half'></i>";
        $emptyStar = "<i class = 'las la-star'></i>";
        $rating = $rating <= $maxRating ? $rating : $maxRating;

        $fullStarCount = (int)$rating;
        $halfStarCount = ceil($rating) - $fullStarCount;
        $emptyStarCount = $maxRating - $fullStarCount - $halfStarCount;

        $html = str_repeat($fullStar, $fullStarCount);
        $html .= str_repeat($halfStar, $halfStarCount);
        $html .= str_repeat($emptyStar, $emptyStarCount);
        echo $html;
    }
}

function translate($key, $lang = null, $addslashes = false)
{
    if ($lang == null) {
        $lang = App::getLocale();
    }

    $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($key)));

    $translations_en = Cache::rememberForever('translations-en', function () {
        return Translation::where('lang', 'en')->pluck('lang_value', 'lang_key')->toArray();
    });

    if (!isset($translations_en[$lang_key])) {
        $translation_def = new Translation;
        $translation_def->lang = 'en';
        $translation_def->lang_key = $lang_key;
        $translation_def->lang_value = str_replace(array("\r", "\n", "\r\n"), "", $key);
        $translation_def->save();
        Cache::forget('translations-en');
    }

    // return user session lang
    $translation_locale = Cache::rememberForever("translations-{$lang}", function () use ($lang) {
        return Translation::where('lang', $lang)->pluck('lang_value', 'lang_key')->toArray();
    });
    if (isset($translation_locale[$lang_key])) {
        return $addslashes ? addslashes(trim($translation_locale[$lang_key])) : trim($translation_locale[$lang_key]);
    }

    // return default lang if session lang not found
    $translations_default = Cache::rememberForever('translations-' . env('DEFAULT_LANGUAGE', 'en'), function () {
        return Translation::where('lang', env('DEFAULT_LANGUAGE', 'en'))->pluck('lang_value', 'lang_key')->toArray();
    });
    if (isset($translations_default[$lang_key])) {
        return $addslashes ? addslashes(trim($translations_default[$lang_key])) : trim($translations_default[$lang_key]);
    }

    // fallback to en lang
    if (!isset($translations_en[$lang_key])) {
        return trim($key);
    }
    return $addslashes ? addslashes(trim($translations_en[$lang_key])) : trim($translations_en[$lang_key]);
}

function remove_invalid_charcaters($str)
{
    $str = str_ireplace(array("\\"), '', $str);
    return str_ireplace(array('"'), '\"', $str);
}

function getShippingCost($carts, $index, $carrier = '')
{
    $shipping_type = get_setting('shipping_type');
    $admin_products = array();
    $seller_products = array();
    $admin_product_total_weight = 0;
    $admin_product_total_price = 0;
    $seller_product_total_weight = array();
    $seller_product_total_price = array();

    $cartItem = $carts[$index];
    $product = Product::find($cartItem['product_id']);

    if ($product->digital == 1) {
        return 0;
    }

    foreach ($carts as $key => $cart_item) {
        $item_product = Product::find($cart_item['product_id']);
        if ($item_product->added_by == 'admin') {
            array_push($admin_products, $cart_item['product_id']);

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $admin_product_total_weight += ($item_product->weight * $cart_item['quantity']);
                $admin_product_total_price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
            }
        } else {
            $product_ids = array();
            $weight = 0;
            $price = 0;
            if (isset($seller_products[$item_product->user_id])) {
                $product_ids = $seller_products[$item_product->user_id];

                // For carrier wise shipping
                if ($shipping_type == 'carrier_wise_shipping') {
                    $weight += $seller_product_total_weight[$item_product->user_id];
                    $price += $seller_product_total_price[$item_product->user_id];
                }
            }

            array_push($product_ids, $cart_item['product_id']);
            $seller_products[$item_product->user_id] = $product_ids;

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $weight += ($item_product->weight * $cart_item['quantity']);
                $seller_product_total_weight[$item_product->user_id] = $weight;

                $price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
                $seller_product_total_price[$item_product->user_id] = $price;
            }
        }
    }

    if ($shipping_type == 'flat_rate') {
        return get_setting('flat_rate_shipping_cost') / count($carts);
    } elseif ($shipping_type == 'seller_wise_shipping') {
        if ($product->added_by == 'admin') {
            return get_setting('shipping_cost_admin') / count($admin_products);
        } else {
            return Shop::where('user_id', $product->user_id)->first()->shipping_cost / count($seller_products[$product->user_id]);
        }
    } elseif ($shipping_type == 'area_wise_shipping') {
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        $city = City::where('id', $shipping_info->city_id)->first();
        if ($city != null) {
            if ($product->added_by == 'admin') {
                return $city->cost / count($admin_products);
            } else {
                return $city->cost / count($seller_products[$product->user_id]);
            }
        }
        return 0;
    } elseif ($shipping_type == 'carrier_wise_shipping') { // carrier wise shipping
        $user_zone = Address::where('id', $carts[0]['address_id'])->first()->country->zone_id;
        if ($carrier == null || $user_zone == 0) {
            return 0;
        }

        $carrier = Carrier::find($carrier);
        if ($carrier->carrier_ranges->first()) {
            $carrier_billing_type   = $carrier->carrier_ranges->first()->billing_type;
            if ($product->added_by == 'admin') {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $admin_product_total_weight : $admin_product_total_price;
            } else {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $seller_product_total_weight[$product->user_id] : $seller_product_total_price[$product->user_id];
            }
        }

        foreach ($carrier->carrier_ranges as $carrier_range) {
            if ($itemsWeightOrPrice >= $carrier_range->delimiter1 && $itemsWeightOrPrice < $carrier_range->delimiter2) {
                $carrier_price = $carrier_range->carrier_range_prices->where('zone_id', $user_zone)->first()->price;
                return $product->added_by == 'admin' ? ($carrier_price / count($admin_products)) : ($carrier_price / count($seller_products[$product->user_id]));
            }
        }
        return 0;
    } else {
        if ($product->is_quantity_multiplied && ($shipping_type == 'product_wise_shipping')) {
            return  $product->shipping_cost * $cartItem['quantity'];
        }
        return $product->shipping_cost;
    }
}

//return carrier wise shipping cost against seller
if (!function_exists('carrier_base_price')) {
    function carrier_base_price($carts, $carrier_id, $owner_id)
    {
        $shipping = 0;
        foreach ($carts as $key => $cartItem) {
            if ($cartItem->owner_id == $owner_id) {
                $shipping_cost = getShippingCost($carts, $key, $carrier_id);
                $shipping += $shipping_cost;
            }
        }
        return $shipping;
    }
}

//return seller wise carrier list
if (!function_exists('seller_base_carrier_list')) {
    function seller_base_carrier_list($owner_id)
    {
        $carrier_list = array();
        $carts = Cart::where('user_id', auth()->user()->id)->get();
        if (count($carts) > 0) {
            $zone = $carts[0]['address'] ? Country::where('id', $carts[0]['address']['country_id'])->first()->zone_id : null;
            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id', function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->active()->get();
        }
        return (new CarrierCollection($carrier_list))->extra($owner_id);
    }
}

function timezones()
{
    return Timezones::timezonesToArray();
}

if (!function_exists('app_timezone')) {
    function app_timezone()
    {
        return config('app.timezone');
    }
}

//return file uploaded via uploader
if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        if (($asset = \App\Models\Upload::find($id)) != null) {
            return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
        }
        return static_asset('assets/img/placeholder.jpg');
    }
}

if (!function_exists('my_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function my_asset($path, $secure = null)
    {
        if (env('FILESYSTEM_DRIVER') == 's3') {
            return Storage::disk('s3')->url($path);
        } else {
            return app('url')->asset('public/' . $path, $secure);
        }
    }
}

if (!function_exists('static_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function static_asset($path, $secure = null)
    {
        return app('url')->asset('public/' . $path, $secure);
    }
}


// if (!function_exists('isHttps')) {
//     function isHttps()
//     {
//         return !empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']);
//     }
// }

if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        $root = '//' . $_SERVER['HTTP_HOST'];
        $root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

        return $root;
    }
}


if (!function_exists('getFileBaseURL')) {
    function getFileBaseURL()
    {
        if (env('FILESYSTEM_DRIVER') == 's3') {
            return env('AWS_URL') . '/';
        } else {
            return getBaseURL() . 'public/';
        }
    }
}


if (!function_exists('isUnique')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function isUnique($email)
    {
        $user = \App\Models\User::where('email', $email)->first();

        if ($user == null) {
            return '1'; // $user = null means we did not get any match with the email provided by the user inside the database
        } else {
            return '0';
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
        /*TODO: Disabled to handle the invoice number update related*/
        /*$settings = Cache::remember('business_settings', 86400, function () {
            return BusinessSetting::all();
        });*/

        $settings = BusinessSetting::all();


        if ($lang == false) {
            $setting = $settings->where('type', $key)->first();
        } else {
            $setting = $settings->where('type', $key)->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        }
        return $setting == null ? $default : $setting->value;
    }
}

function hex2rgba($color, $opacity = false)
{
    return Colorcodeconverter::convertHexToRgba($color, $opacity);
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        if (Auth::check() && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('isSeller')) {
    function isSeller()
    {
        if (Auth::check() && Auth::user()->user_type == 'seller') {
            return true;
        }
        return false;
    }
}

if (!function_exists('isCustomer')) {
    function isCustomer()
    {
        if (Auth::check() && Auth::user()->user_type == 'customer') {
            return true;
        }
        return false;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// duplicates m$ excel's ceiling function
if (!function_exists('ceiling')) {
    function ceiling($number, $significance = 1)
    {
        return (is_numeric($number) && is_numeric($significance)) ? (ceil($number / $significance) * $significance) : false;
    }
}

//for api
if (!function_exists('get_images_path')) {
    function get_images_path($given_ids, $with_trashed = false)
    {
        $paths = [];
        foreach (explode(',', $given_ids) as $id) {
            $paths[] = uploaded_asset($id);
        }

        return $paths;
    }
}

//for api
if (!function_exists('checkout_done')) {
    function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::find($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            try {
                NotificationUtility::sendOrderPlacedNotification($order);
                calculateCommissionAffilationClubPoint($order);
            } catch (\Exception $e) {
            }
        }
    }
}

//for api
if (!function_exists('wallet_payment_done')) {
    function wallet_payment_done($user_id, $amount, $payment_method, $payment_details)
    {
        $user = \App\Models\User::find($user_id);
        $user->balance = $user->balance + $amount;
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $amount;
        $wallet->payment_method = $payment_method;
        $wallet->payment_details = $payment_details;
        $wallet->save();
    }
}

if (!function_exists('purchase_payment_done')) {
    function purchase_payment_done($user_id, $package_id)
    {
        $user = User::findOrFail($user_id);
        $user->customer_package_id = $package_id;
        $customer_package = CustomerPackage::findOrFail($package_id);
        $user->remaining_uploads += $customer_package->product_upload;
        $user->save();

        return 'success';
    }
}

if (!function_exists('seller_purchase_payment_done')) {
    function seller_purchase_payment_done($user_id, $seller_package_id, $amount, $payment_method, $payment_details)
    {
        $seller = Shop::where('user_id', $user_id)->first();
        $seller->seller_package_id = $seller_package_id;
        $seller_package = SellerPackage::findOrFail($seller_package_id);
        $seller->product_upload_limit = $seller_package->product_upload_limit;
        $seller->package_invalid_at = date('Y-m-d', strtotime($seller->package_invalid_at . ' +' . $seller_package->duration . 'days'));
        $seller->save();

        $seller_package = new SellerPackagePayment();
        $seller_package->user_id = $user_id;
        $seller_package->seller_package_id = $seller_package_id;
        $seller_package->payment_method = $payment_method;
        $seller_package->payment_details = $payment_details;
        $seller_package->approval = 1;
        $seller_package->offline_payment = 2;
        $seller_package->save();
    }
}

if (!function_exists('product_restock')) {
    function product_restock($orderDetail)
    {
        $variant = $orderDetail->variation;
        if ($orderDetail->variation == null) {
            $variant = '';
        }

        $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
            ->where('variant', $variant)
            ->first();

        if ($product_stock != null) {
            $product_stock->qty += $orderDetail->quantity;
            $product_stock->save();
        }
    }
}

//Commission Calculation
if (!function_exists('calculateCommissionAffilationClubPoint')) {
    function calculateCommissionAffilationClubPoint($order)
    {
        (new CommissionController)->calculateCommission($order);

        if (addon_is_activated('affiliate_system')) {
            (new AffiliateController)->processAffiliatePoints($order);
        }

        if (addon_is_activated('club_point')) {
            if ($order->user != null) {
                (new ClubPointController)->processClubPoints($order);
            }
        }

        $order->commission_calculated = 1;
        $order->save();
    }
}

// Addon Activation Check
if (!function_exists('addon_is_activated')) {
    function addon_is_activated($identifier, $default = null)
    {
        $addons = Cache::remember('addons', 86400, function () {
            return Addon::all();
        });

        $activation = $addons->where('unique_identifier', $identifier)->where('activated', 1)->first();
        return $activation == null ? false : true;
    }
}

// Addon Activation Check
if (!function_exists('seller_package_validity_check')) {
    function seller_package_validity_check($user_id = null)
    {
        $user = $user_id == null ? \App\Models\User::find(Auth::user()->id) : \App\Models\User::find($user_id);
        $shop = $user->shop;
        $package_validation = false;
        if (
            $shop->product_upload_limit > $shop->user->products()->count()
            && $shop->package_invalid_at != null
            && Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) >= 0
        ) {
            $package_validation = true;
        }

        return $package_validation;
        // Ture = Seller package is valid and seller has the product upload limit
        // False = Seller package is invalid or seller product upload limit exists.
    }
}

// Get URL params
if (!function_exists('get_url_params')) {
    function get_url_params($url, $key)
    {
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $query_params);

        return $query_params[$key] ?? '';
    }
}

// Get product tax related details
if (!function_exists('getProductTaxDetails')) {
    function getProductTaxDetails($orderDetails)
    {
        $cgst =  0.00;
        $cgstAmt =  0.00;
        $sgst = 0.00;
        $sgstAmt =  0.00;
        $igst = 0.00;
        $igstAmt =  0.00;
        $productTaxes = $orderDetails->product->taxes;
        $price = $orderDetails->price;
        if($productTaxes->isNotEmpty()){
            $tax_name = Tax::where('id',$productTaxes[0]->tax_id)->first();

            $splitTax = $productTaxes[0]->tax / 2;
            if(!empty($tax_name) && $tax_name->name == 'GST'){
                $cgst =  $splitTax;
                $cgstAmt =(($price * $cgst) / 100);

                $sgst =  $splitTax;
                $sgstAmt =(($price * $sgst) / 100);
            }
        }
        $taxDetails['cgst'] = $cgst;
        $taxDetails['sgst'] = $sgst;
        $taxDetails['cgstAmt'] = $cgstAmt;
        $taxDetails['sgstAmt'] = $sgstAmt;
        $taxDetails['igstAmt'] = $cgstAmt + $sgstAmt;
        $taxDetails['igst'] = isset($productTaxes[0]->tax) ? $productTaxes[0]->tax : 0.00;

        return $taxDetails;
    }
}

// Get order tax related details
if (!function_exists('getOrderTaxDetails')) {
    function getOrderTaxDetails($order)
    {
        $tax =  0.00;
        $cgstAmt =  0.00;
        $sgstAmt =  0.00;
        $itemsTotalQty =  0;

        foreach ($order->orderDetails as $orderDetail) {
            $productTaxes = $orderDetail->product->taxes;
            $price = $orderDetail->price;
            if($productTaxes->isNotEmpty()){
                $tax_name = Tax::where('id',$productTaxes[0]->tax_id)->first();

                $itemsTotalQty += $orderDetail->quantity;

                if($productTaxes[0]->tax_type == 'percent'){
                    $tax += ($price * $productTaxes[0]->tax) / 100;
                }

                $splitTax = $productTaxes[0]->tax / 2;
                if(!empty($tax_name) && $tax_name->name == 'GST'){
                    $cgst =  $splitTax;
                    $cgstAmt += (($price * $cgst) / 100);

                    $sgst =  $splitTax;
                    $sgstAmt += (($price * $sgst) / 100);
                }
            }
        }

        $taxDetails['totalCgstAmt'] = $cgstAmt;
        $taxDetails['totalSgstAmt'] = $sgstAmt;
        $taxDetails['totalIgstAmt'] = $tax;
        $taxDetails['itemsTotalQty'] = $itemsTotalQty;

        return $taxDetails;
    }
}

// get address details
if (!function_exists('getAddressDetails')) {
    function getAddressDetails($addressId)
    {
        $shipping_info = Address::where(['id'=>$addressId])->first();
        return $shipping_info;
    }
}

// round price
if (!function_exists('roundPrice')) {
    function roundPrice($price)
    {
        $intVal = intval($price);
        if ($price - $intVal < .50) {
            return false;
        }else{
            return true;
        }
    }
}

// Update order invoice number
if (!function_exists('updateInvoiceNumber')) {
    function updateInvoiceNumber($orderId)
    {
        $invoiceNumber = get_setting('invoice_next_number');
        $invoiceNextNumber = $invoiceNumber + 1;
        // Get invoice next number and add 0 prefix based on no of digits
        $invoiceNextNumber = getNextInvoiceNumber($invoiceNextNumber);

        $invoiceNumberPrefix = get_setting('invoice_number_prefix');
        $invoiceFullNumber = $invoiceNumberPrefix.'/'.$invoiceNumber;

        // Update invoice number in orders table
        Order::where('id', $orderId)->update(['invoice_number' => $invoiceFullNumber]);

        // Update next invoice no in business settings table
        BusinessSetting::where('id', 145)->update(['value' => $invoiceNextNumber]); // 145 - id of invoice_next_number(type column)
    }
}

// Get nect invoice number format
if (!function_exists('getNextInvoiceNumber')) {
    function getNextInvoiceNumber($invoiceNumber)
    {
        $numberOfDigits = strlen((string)$invoiceNumber);
        switch ($numberOfDigits) {
          case '2':
            $invoiceNextNumber = "0" . $invoiceNumber;
            break;
          case '1':
            $invoiceNextNumber = "00" . $invoiceNumber;
            break;
          default:
            $invoiceNextNumber = $invoiceNumber;
            break;
        }
        return $invoiceNextNumber;
    }
}
// Check user address within tamilnadu or outer state for GST Implementation
if (!function_exists('checkAuthUserAddress')) {
    function checkAuthUserAddress(){
        $userWithinTamilnadu = "";
        $tamilnaduStateId = 35;
        if(auth()->user() != null) {
            $userId = Auth::user()->id;
            $userDetails = User::where('id', $userId)->first();
            $country_data = Country :: find(auth()->user()->country);
            $code = ($country_data->code == 'IN') ?'INR' :'USD';

            $currency =Currency::where('code', $code)->first();
            if(!empty($currency)){
                if($currency->code == 'INR'){
                    $userWithinTamilnadu = 1;
                }else{
                    $userWithinTamilnadu = 0;
                }

            }
        }else{

            $userWithinTamilnadu = (Session::get('currency_code') == 'USD') ? 0 : 1;
        }

        return $userWithinTamilnadu;
    }
}

// get user address details
if (!function_exists('getUserAddressDetails')) {
    function getUserAddressDetails()
    {
        $addressDetails = [];
        if(auth()->user() != null) {
            $userId = Auth::user()->id;
            $userDetails = User::where('id', $userId)->first();
            $addressDetails['state_id'] = $userDetails->state;
            $addressDetails['country_id'] = $userDetails->country;
            $addressDetails['city_id'] = $userDetails->city;
            $addressDetails['company_name'] = $userDetails->company_name ?? "";
            $addressDetails['postal_code'] = $userDetails->postal_code;
            $addressDetails['phone_number'] = $userDetails->phone;
            $addressDetails['address'] = $userDetails->address;
            $addressDetails['phone'] = $userDetails->phone;

            $addressDetails['state_name'] = "";
            $addressDetails['city_name'] = "";
            $addressDetails['country_name'] = "";
            if(!empty($userDetails->state)){
                // get state name
                $state = State::where('id', $userDetails->state)->first();
                $addressDetails['state_name'] = !empty($state->name) ? $state->name : '';
            }
            if(!empty($userDetails->city)){
                // get city name
                $city = City::where('id', $userDetails->city)->first();
                $addressDetails['city_name'] = !empty($city->name) ? $city->name : '';
            }
            if(!empty($userDetails->country)){
                $country = Country::where('id', $userDetails->country)->first();
                $addressDetails['country_name'] = !empty($country->name) ? $country->name : '';
            }

        }

        return $addressDetails;
    }
}

// Update quotation estimate number
if (!function_exists('updateQuotationEstimateNumber')) {
    function updateQuotationEstimateNumber()
    {
        $estimateNumber = get_setting('quotation_next_number');
        $estimateNextNumber = $estimateNumber + 1;
        // Get estimate next number and add 0 prefix based on no of digits
        $estimateNextNumber = getNextInvoiceNumber($estimateNextNumber);

        $estimateNumberPrefix = get_setting('quotation_number_prefix');
        $estimateFullNumber = $estimateNumberPrefix.'-'.$estimateNumber;

        // Update next invoice no in business settings table
        BusinessSetting::where('id', 147)->update(['value' => $estimateNextNumber]); // 147 - id of quotation_next_number(type column)

        return $estimateFullNumber;
    }
}