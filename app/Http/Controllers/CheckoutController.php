<?php

namespace App\Http\Controllers;

use App\Utility\PayfastUtility;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\CombinedOrder;
use App\Models\Product;
use App\Models\Country;
use App\Models\BusinessSetting;
use App\Utility\PayhereUtility;
use App\Utility\NotificationUtility;
use Illuminate\Support\Facades\Http;
use Session;
use Auth;

class CheckoutController extends Controller
{

    public function __construct()
    {
        //
    }

    //check the selected payment gateway and redirect to that controller accordingly
    public function checkout(Request $request)
    {

        // echo "<pre>"; print_r($request->all()); die();
        // Minumum order amount check
        if(get_setting('minimum_order_amount_check') == 1){
            $subtotal = 0;
            foreach (Cart::where('user_id', Auth::user()->id)->get() as $key => $cartItem){
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                flash(translate('You order amount is less then the minimum order amount'))->warning();
                return redirect()->route('home');
            }
        }
        // Minumum order amount check end

        if ($request->payment_option != null) {

            (new OrderController)->store($request);

            $request->session()->put('payment_type', 'cart_payment');

            $data['combined_order_id'] = $request->session()->get('combined_order_id');
            $request->session()->put('payment_data', $data);

            if ($request->session()->get('combined_order_id') != null) {

                // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
                $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";


                if (class_exists($decorator)) {
                    return (new $decorator)->pay($request);
                }
                else {
                    $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));

                    //dd($combined_order);
                    $manual_payment_data = array(
                        'name'   => $request->payment_option,
                        'amount' => $combined_order->grand_total,
                        'trx_id' => $request->trx_id,
                        'photo'  => $request->photo
                    );
                    foreach ($combined_order->orders as $order) {
                        $order->manual_payment = 1;
                        $order->manual_payment_data = json_encode($manual_payment_data);
                        $order->save();
                    }
                    flash(translate('Your order has been placed successfully. Please submit payment information from purchase history'))->success();
                    return redirect()->route('order_confirmed');
                }
            }
        } else {
            flash(translate('Select Payment Option.'))->warning();
            return back();
        }
    }

    //redirects to this method after a successfull checkout
    public function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order = Order::findOrFail($order->id);
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            //TODO : Uncomment this for club point
            //calculateCommissionAffilationClubPoint($order);

            //Shiprocket create order api integration
            $response = $this->shiprocketCreateOrder($order);

            //Update invoice number in business settings & orders table
            updateInvoiceNumber($order->id);
        }

        Session::put('combined_order_id', $combined_order_id);
        return redirect()->route('order_confirmed');
    }

    public function get_shipping_info(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)->get();
//        if (Session::has('cart') && count(Session::get('cart')) > 0) {
        if ($carts && count($carts) > 0) {
            $categories = Category::all();
            return view('frontend.shipping_info', compact('categories', 'carts'));
        }
        flash(translate('Your cart is empty'))->success();
        return back();
    }

    public function store_shipping_info(Request $request)
    {
        if ($request->address_id == null) {
            flash(translate("Please add shipping address"))->warning();
            return back();
        }

        $carts = Cart::where('user_id', Auth::user()->id)->get();
        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        foreach ($carts as $key => $cartItem) {
            $cartItem->address_id = $request->address_id;
            $cartItem->save();
        }

        $carrier_list = array();
        if(get_setting('shipping_type') == 'carrier_wise_shipping'){
            $zone = \App\Models\Country::where('id',$carts[0]['address']['country_id'])->first()->zone_id;

            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id',function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->get();
        }
        return redirect()->route('checkout.store_delivery_info');
        return view('frontend.delivery_info', compact('carts','carrier_list'));
    }

    public function store_delivery_info(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();

        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $shipping_info = Address::where(['id'=>$carts[0]['address_id'],'set_default'=>1])->first();
        if(empty($shipping_info)){
            $shipping_info = Address::where(['id'=>$carts[0]['address_id']])->first();
        }

        $total = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;
        $totalWeight = 0;
        $productTotalWidth = 0;
        $productTotalHeight = 0;
        $productTotalBreadth = 0;

        if ($carts && count($carts) > 0) {
            foreach ($carts as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];

                if(get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product->user_id] == 'pickup_point'){
                    if ($request['shipping_type_' . $product->user_id] == 'pickup_point') {
                        $cartItem['shipping_type'] = 'pickup_point';
                        $cartItem['pickup_point'] = $request['pickup_point_id_' . $product->user_id];
                    } else {
                        $cartItem['shipping_type'] = 'home_delivery';
                    }
                    $cartItem['shipping_cost'] = 0;
                    if ($cartItem['shipping_type'] == 'home_delivery') {
                        $cartItem['shipping_cost'] = getShippingCost($carts, $key);
                    }
                }
                else{
                    $cartItem['shipping_type'] = 'carrier';
                    $cartItem['carrier_id'] = $request['carrier_id_' . $product->user_id];
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key, $cartItem['carrier_id']);
                }

                //Get dimensions details of the product
                if(!empty($product->stocks)){
                    foreach ($product->stocks as $key => $stocks) {
                        if(!empty($stocks->width)){
                            $productTotalWidth += $stocks->width;
                        }
                        if(!empty($stocks->height)){
                            $productTotalHeight += $stocks->height;
                        }
                        if(!empty($stocks->breadth)){
                            $productTotalBreadth += $stocks->breadth;
                        }
                    }
                }

                //Get total weight of the cart added products
                $totalWeight += $product->weight;

                $shipping += $cartItem['shipping_cost'];
                $cartItem->save();
            }
            $prodDimensionDetails['totalWeight'] = $totalWeight;
            $prodDimensionDetails['productTotalWidth'] = $productTotalWidth;
            $prodDimensionDetails['productTotalHeight'] = $productTotalHeight;
            $prodDimensionDetails['productTotalBreadth'] = $productTotalBreadth;

            //Get customer postcode based courier charge
            $courierRes = $this->getSingleCourierList($shipping_info->postal_code,$prodDimensionDetails);
            $loggedUserCountryId = Auth::user()->country;
            if(!empty($courierRes) && $courierRes['status'] == "success"){
                $shippingCourierCost = $courierRes['rate'];
                $shippingCourierName = $courierRes['courier_name'];
            }else{
                $shippingCourierCost = 0;
                $shippingCourierName = "";
            }

            $total = $subtotal + $tax + $shipping;

            $countries = Country::where('id',101)->get();
            $checkUserAddress = checkAuthUserAddress();

            return view('frontend.payment_select', compact('carts', 'shipping_info', 'total','countries','shippingCourierCost','shippingCourierName','prodDimensionDetails','checkUserAddress'));

        } else {
            flash(translate('Your Cart was empty'))->warning();
            return redirect()->route('home');
        }
    }

    public function apply_coupon_code(Request $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        $response_message = array();

        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $carts = Cart::where('user_id', Auth::user()->id)
                                    ->where('owner_id', $coupon->user_id)
                                    ->get();

                    $coupon_discount = 0;

                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
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

                    if($coupon_discount > 0){
                        Cart::where('user_id', Auth::user()->id)
                            ->where('owner_id', $coupon->user_id)
                            ->update(
                                [
                                    'discount' => $coupon_discount / count($carts),
                                    'coupon_code' => $request->code,
                                    'coupon_applied' => 1
                                ]
                            );
                        $response_message['response'] = 'success';
                        $response_message['message'] = translate('Coupon has been applied');
                    }
                    else{
                        $response_message['response'] = 'warning';
                        $response_message['message'] = translate('This coupon is not applicable to your cart products!');
                    }

                } else {
                    $response_message['response'] = 'warning';
                    $response_message['message'] = translate('You already used this coupon!');
                }
            } else {
                $response_message['response'] = 'warning';
                $response_message['message'] = translate('Coupon expired!');
            }
        } else {
            $response_message['response'] = 'danger';
            $response_message['message'] = translate('Invalid coupon!');
        }

        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        $returnHTML = view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'))->render();
        return response()->json(array('response_message' => $response_message, 'html'=>$returnHTML));
    }

    public function remove_coupon_code(Request $request)
    {
        Cart::where('user_id', Auth::user()->id)
                ->update(
                        [
                            'discount' => 0.00,
                            'coupon_code' => '',
                            'coupon_applied' => 0
                        ]
        );

        $coupon = Coupon::where('code', $request->code)->first();
        $carts = Cart::where('user_id', Auth::user()->id)
                ->get();

        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        return view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'));
    }

    public function apply_club_point(Request $request) {
        if (addon_is_activated('club_point')){

            $point = $request->point;

            if(Auth::user()->point_balance >= $point) {
                $request->session()->put('club_point', $point);
                flash(translate('Point has been redeemed'))->success();
            }
            else {
                flash(translate('Invalid point!'))->warning();
            }
        }
        return back();
    }

    public function remove_club_point(Request $request) {
        $request->session()->forget('club_point');
        return back();
    }

    public function order_confirmed()
    {
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));

        Cart::where('user_id', $combined_order->user_id)
                ->delete();

        //Session::forget('club_point');
        //Session::forget('combined_order_id');

        foreach($combined_order->orders as $order){
            NotificationUtility::sendOrderPlacedNotification($order);
        }

        return view('frontend.order_confirmed', compact('combined_order'));
    }
    public function shiprocketAuthToken(){
        $response = Http::withHeaders([
                    'Content-Type' => 'application/json'
                ])->post('https://apiv2.shiprocket.in/v1/external/auth/login', [
            //'email' => 'kavilashtech@gmail.com',
            //'password' => 'Kavilash@123#',
            'email' => 'qlkavilash@gmail.com',
            'password' => 'Quicklab@123',
        ]);
        $result = json_decode($response,true);
        return $result;
    }
    public function shipping_couriers_list(Request $request){
        //Get Auth token
        $result = $this->shiprocketAuthToken();

        if(!empty($result) && !empty($result['token'])){
            $pickupPostcode = get_setting('pickup_point') ?? "";
            $deliveryPostcode = $request->postcode;
            $productBreadth = $request->productBreadth;
            $productWidth = $request->productWidth;
            $productHeight = $request->productHeight;
            $prodTotalWeight = $request->prodTotalWeight;

            $loggedCountryId = Auth::user()->country;

            if(!empty($loggedCountryId)){

                $country = Country::where('id', $loggedCountryId)->first();

                $apiUrl = ($loggedCountryId == 101) ? 'https://apiv2.shiprocket.in/v1/external/courier/serviceability' : 'https://apiv2.shiprocket.in/v1/external/courier/international/serviceability';  // 101 - india country id

                /*if($loggedCountryId == 101 && empty($deliveryPostcode)){
                    $data['status'] = "postcode_empty";
                    echo json_encode($data);
                }*/
                $response = Http::withToken($result['token'])->get($apiUrl,[
                    'pickup_postcode' => ($loggedCountryId == 101) ? $pickupPostcode : "",
                    'delivery_postcode' => ($loggedCountryId == 101) ? $deliveryPostcode : "",
                    'weight' => ($prodTotalWeight > 0) ? $prodTotalWeight : 1,
                    'cod' =>  ($loggedCountryId == 101) ? '1' : '0',  //Todo - To check in future
                    'delivery_country' => ($loggedCountryId != 101) ? $country->code : "",
                    'breadth' => $productBreadth ?? "",
                    'height' => $productHeight ?? "",
                    'length' => $productWidth ?? "",
                ]);

                $result = json_decode($response,true);
                $data = [];
                if(!empty($result) && !empty($result['data']['available_courier_companies'])){
                    foreach($result['data']['available_courier_companies'] as $key => $value){
                        $rate = (!empty($value['rate']) && $loggedCountryId == 101) ? $value['rate'] : $value['rate']['rate'];
                        $data['data'][$key]['estimated_delivery_date'] = $value['etd'];
                        $data['data'][$key]['courier_name'] = $value['courier_name'];
                        $usdConvertPrice = exchangeRateApi($rate);
                        $data['data'][$key]['rate'] = !empty($usdConvertPrice) ? $usdConvertPrice : $rate;
                        $data['data'][$key]['freight_charge'] = $value['freight_charge'] ?? "";
                    }
                    $data['status']="success";
                }else if($loggedCountryId == 101 && !empty($deliveryPostcode)){
                    $data['status'] = "invalid_delivery_postcode";
                }else{
                    $data['status'] = "invalid_inputs_data";
                }
                echo json_encode($data);
            }
        }else{
            $data['status'] = "invalid_token";
            echo json_encode($data);
        }
    }
    public function shipping_currency_couriers_list(Request $request){
        //Get Auth token
        $result = $this->shiprocketAuthToken();

        if(!empty($result) && !empty($result['token'])){

            $productWidth = $request->productWidth;
            $productHeight = $request->productHeight;
            $prodTotalWeight = $request->prodTotalWeight;

            $loggedCountryId = Auth::user()->country;

            if(!empty($loggedCountryId)){

                $country = Country::where('id', $loggedCountryId)->first();

                $apiUrl = ($loggedCountryId == 101) ? 'https://apiv2.shiprocket.in/v1/external/courier/serviceability' : 'https://apiv2.shiprocket.in/v1/external/international/courier/serviceability';  // 101 - india country id

                /*if($loggedCountryId == 101 && empty($deliveryPostcode)){
                    $data['status'] = "postcode_empty";
                    echo json_encode($data);
                }*/
                $response = Http::withToken($result['token'])->get($apiUrl,[

                    'weight' => ($prodTotalWeight > 0) ? $prodTotalWeight : 1,
                    'cod' =>  ($loggedCountryId == 101) ? '1' : '0',  //Todo - To check in future
                    'delivery_country' => $country->code,


                ]);

                $result = json_decode($response,true);
                //dd($result);
                $data = [];
                if(!empty($result) && !empty($result['data']['available_courier_companies'])){
                    foreach($result['data']['available_courier_companies'] as $key => $value){
                        $rate = (!empty($value['rate']) && $loggedCountryId == 101) ? $value['rate'] : $value['rate']['rate'];
                        $data['data'][$key]['estimated_delivery_date'] = $value['etd'];
                        $data['data'][$key]['courier_name'] = $value['courier_name'];
                        $usdConvertPrice = exchangeRateApi($rate);
                        $data['data'][$key]['rate'] = !empty($usdConvertPrice) ? $usdConvertPrice : $rate;
                        $data['data'][$key]['freight_charge'] = $value['freight_charge'] ?? "";
                    }
                    $data['status']="success";
                }else if($loggedCountryId == 101 && !empty($deliveryPostcode)){
                    $data['status'] = "invalid_delivery_postcode";
                }else{
                    $data['status'] = "invalid_delivery_postcode";
                    $data['message'] = "Shipping is not available to the selected country. Please contact Quicklab";
                }
                echo json_encode($data);
            }
        }else{
            $data['status'] = "invalid_token";
            echo json_encode($data);
        }
    }
    public function apply_shipping_charge(Request $request){
        $grandTotal = $request->grandTotal;
        $shipCost = $request->shipCost;
        $shipDefaultCost = $request->shipDefaultCost;

        $withoutShipCostTotal = $grandTotal - $shipDefaultCost;

        $total = $withoutShipCostTotal + $shipCost;
        $grandTotal = single_price($total);
        $shippingCost = single_price($shipCost);


        $data['status'] = "success";
        $data['grandTotal'] = $grandTotal;
        $data['shippingCost'] = $shippingCost;

        echo json_encode($data);
    }
    public function shiprocketCreateOrder($order){

        //Get Auth token
        $tokenResult = $this->shiprocketAuthToken();

        $customerDetails = json_decode($order->shipping_address,true);
        $customerPhoneNo = explode(' ',$customerDetails['phone']);

        $loggedCountryId = Auth::user()->country;

        $currencyCode = ($loggedCountryId == 101) ? 'INR' : 'USD';  // 101 - india country id

        if(!empty($tokenResult) && !empty($tokenResult['token'])){
            //Get the cart details
            $carts = Cart::where('user_id', Auth::user()->id)
            ->select('id','product_id','price','quantity','tax','discount')
            ->get();

            $orderItems = [];
            $subTotal = 0;
            $totalWeight = 0;
            $productTotalWidth = "";
            $productTotalHeight = "";
            $productTotalBreadth = "";
            if(!empty($carts)){
                foreach($carts as $key => $value){
                    //Get the product details
                    $product = Product::where('id',$value['product_id'])
                    ->select('id','name')
                    ->first();

                    $cartItems['name'] = $product->name ?? "";
                    $cartItems['sku'] = $product->stocks[0]->sku ?? "";
                    $cartItems['units'] = $value->quantity ?? "";
                    $cartItems['selling_price'] = $value->price ?? "";
                    $cartItems['discount'] = $value->discount ?? "";
                    $cartItems['tax'] = $value->tax ?? "";
                    $cartItems['hsn'] = $product->stocks[0]->hsn_code ?? "";

                    $orderItems[] = $cartItems;
                    $subTotal += $value->price;

                    //Get total weight of the cart added products
                    $totalWeight += $product->weight;

                    //Get dimensions details of the product
                    if(!empty($product->stocks)){
                        foreach ($product->stocks as $key => $stocks) {
                            if(!empty($stocks->width)){
                                $productTotalWidth += $stocks->width;
                            }
                            if(!empty($stocks->height)){
                                $productTotalHeight += $stocks->height;
                            }
                            if(!empty($stocks->breadth)){
                                $productTotalBreadth += $stocks->breadth;
                            }
                        }
                    }
                }

                $orderValues = [
                    'order_id' => $order->code,
                    'order_date' => $order->created_at,
                    'pickup_location' => "Primary",
                    'channel_id' => "Custom",
                    'comment' => "Create Order",
                    'billing_customer_name' => $customerDetails['name'],
                    'billing_last_name' => $customerDetails['name'],
                    'billing_address' => $customerDetails['address'],
                    'billing_address_2' => "",
                    'billing_city' => $customerDetails['city'],
                    'billing_pincode' => $customerDetails['postal_code'],
                    'billing_state' => $customerDetails['state'],
                    'billing_country' => $customerDetails['country'],
                    'billing_email' => $customerDetails['email'],
                    'billing_phone' => $customerPhoneNo[1] ?? "",
                    'shipping_is_billing' => true,
                    'shipping_customer_name' => "",
                    'shipping_last_name' => "",
                    'shipping_address' => "",
                    'shipping_address_2' => "",
                    'shipping_city' => "",
                    'shipping_pincode' => "",
                    'shipping_country' => "",
                    'shipping_state' => "",
                    'shipping_email' => "",
                    'shipping_phone' => "",
                    'order_items' => $orderItems,
                    'payment_method' => $order->payment_type,
                    'shipping_charges' => $order->shipping_courier_charge ?? 0,
                    'giftwrap_charges' => 0,
                    'transaction_charges' => 0,
                    'total_discount' => $order->coupon_discount,
                    'sub_total' => $subTotal,
                    'length' => $productTotalWidth ?? 1,
                    'breadth' => $productTotalBreadth ?? 1,
                    'height' => $productTotalHeight ?? 1,
                    'weight' => ($totalWeight > 0) ? $totalWeight : 1,
                    'currency' => $currencyCode,
                ];

                if(!empty($orderValues)){
                    //Shiprocket order api call
                    $apiUrl = ($loggedCountryId == 101) ? 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc' : 'https://apiv2.shiprocket.in/v1/external/international/orders/create/adhoc';  // 101 - india country id

                    $response = Http::withToken($tokenResult['token'])->post($apiUrl,$orderValues);
                    $result = json_decode($response,true);
                    if(!empty($result) ){
                        return "success";
                    }else{
                        return "failure";
                    }
                }else{
                    return "failure";
                }
            }
        }else{
            return "failure";
        }
    }
    //Get shiprocket first courier details
    public function getSingleCourierList($deliveryPostcode,$prodDimensionDetails){
        //Get Auth token
        $result = $this->shiprocketAuthToken();

        $loggedCountryId = Auth::user()->country;

        if(!empty($result) && !empty($result['token'])){
            $pickupPostcode = get_setting('pickup_point') ?? "";

            if(!empty($loggedCountryId) && !empty($prodDimensionDetails)){

                $country = Country::where('id', $loggedCountryId)->first();

                $apiUrl = ($loggedCountryId == 101) ? 'https://apiv2.shiprocket.in/v1/external/courier/serviceability' : 'https://apiv2.shiprocket.in/v1/external/courier/international/serviceability';  // 101 - india country id

                $response = Http::withToken($result['token'])->get($apiUrl,[
                    'pickup_postcode' => ($loggedCountryId == 101) ? $pickupPostcode : "",
                    'delivery_postcode' => ($loggedCountryId == 101) ? $deliveryPostcode : "",
                    'weight' => ($prodDimensionDetails['totalWeight'] > 0) ? $prodDimensionDetails['totalWeight'] : 1,
                    'cod' =>  ($loggedCountryId == 101) ? '1' : '0',  //Todo - To check in future
                    'delivery_country' => ($loggedCountryId != 101) ? $country->code : "",
                    'breadth' => $prodDimensionDetails['productTotalBreadth'] ?? "",
                    'height' => $prodDimensionDetails['productTotalHeight'] ?? "",
                    'length' => $prodDimensionDetails['productTotalWidth'] ?? ""
                ]);

                $result = json_decode($response,true);
                $data = [];
                if(!empty($result) && !empty($result['data']['available_courier_companies'])){

                    $courier = $result['data']['available_courier_companies'];

                    $rate = (!empty($courier[0]['rate']) && $loggedCountryId == 101) ? $courier[0]['rate'] : $courier[0]['rate']['rate'];
                    $data['courier_name'] = $courier[0]['courier_name'];
                    $usdConvertPrice = exchangeRateApi($rate);
                    $data['rate'] = !empty($usdConvertPrice) ? $usdConvertPrice : $rate;
                    $data['status']="success";
                }else{
                    $data['status'] = "failure";
                }
                return $data;
            }else{
                $data['status'] = "failure";
                return $data;
            }
        }else{
            $data['status'] = "failure";
            return $data;
        }
    }

}


