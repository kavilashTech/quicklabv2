<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V2\QuotationViewResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Tax;
use App\Mail\QuotationMail;
use App\Models\Currency;
use App\Models\Language;
use App\Models\User;
use App\Models\Shop;
use App\Models\BusinessSetting;
use PDF;
use Config;
use Auth;
use Session;
use Cookie;
use Mail;
use Response;


class GenerateQuoteController extends Controller
{
    public function store(Request $request){
        if (auth()->user() == null) {
            return response()->json([
                'result' => false,
                'message'=>translate('PLease Login to view quotation'),

            ]);
        }

        $product = Product::find($request->id);

        $carts = array();
        $data = array();
        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $data['user_id'] = $user_id;
            $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        }
        if ($product == 0) {
            return response()->json(['result' => false, 'message' => "Invalid Product code"], 200);
        }

        $data['product_id'] = $product->id;
        $data['owner_id'] = $product->user_id;

        $str = '';
        $tax = 0;
        if ($product->auction_product == 0) {

            if ($request->has('color')) {
                $str = $request['color'];
            }
            if ($product->digital != 1) {
                //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
                foreach (json_decode(Product::find($request->id)->choice_options) as $key => $choice) {
                    if ($str != null) {
                        $str .= '-' . str_replace(' ', '', $request['attribute_id_' . $choice->attribute_id]);
                    } else {
                        $str .= str_replace(' ', '', $request['attribute_id_' . $choice->attribute_id]);
                    }
                }
            }

            $data['variation'] = $str;

            $product_stock = $product->stocks->where('variant', $str)->first();
            $price = $product_stock->price;

            if ($product->wholesale_product) {
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                if ($wholesalePrice) {
                    $price = $wholesalePrice->price;
                }
            }

            $quantity = $product_stock->qty;


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
            $data['tax1'] =  0.00;
            $data['tax1_amount'] =  0.00;
            $data['tax2'] = 0.00;
            $data['tax2_amount'] =  0.00;
            $data['quantity'] = $request['quantity'];
            if ($request['quantity'] == null) {
                $data['quantity'] = 1;
            }
            foreach ($product->taxes as $product_tax) {
                $tax_name = Tax::where('id', $product_tax->tax_id)->first();
                if ($product_tax->tax_type == 'percent') {
                    $tax += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $tax += $product_tax->tax;
                }
                $splitTax = $product_tax->tax / 2;
                if (!empty($tax_name) && $tax_name->name == 'GST') {
                    $data['tax1'] =  $splitTax;
                    $data['tax1_amount'] = (($price * $data['tax1']) / 100);

                    $data['tax2'] =  $splitTax;
                    $data['tax2_amount'] = (($price * $data['tax2']) / 100);
                }

            }
            $data['price'] = $price;
            $data['tax'] = $tax;
            //$data['shipping'] = 0;
            $data['shipping_cost'] = 0;
            $data['product_referral_code'] = null;
            $data['cash_on_delivery'] = $product->cash_on_delivery;
            $data['digital'] = $product->digital;

            if (Cookie::has('referred_product_id') && Cookie::get('referred_product_id') == $product->id) {
                $data['product_referral_code'] = Cookie::get('product_referral_code');
            }

            if ($carts && count($carts) > 0) {
                $foundInCart = false;

                foreach ($carts as $key => $cartItem) {
                    $cart_product = Product::where('id', $cartItem['product_id'])->first();
                    // if($cart_product->auction_product == 1){
                        //     return array(
                            //         'status' => 0,
                            //         'cart_count' => count($carts),
                    //         'modal_view' => view('frontend.partials.auctionProductAlredayAddedCart')->render(),
                    //         'nav_cart_view' => view('frontend.partials.cart')->render(),
                    //     );
                    // }

                    if ($cartItem['product_id'] == $request->id) {
                        $product_stock = $cart_product->stocks->where('variant', $str)->first();
                        $quantity = $product_stock->qty;
                        // if($quantity < $cartItem['quantity'] + $request['quantity']){
                            //     return array(
                                //         'status' => 0,
                                //         'cart_count' => count($carts),
                                //         'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                                //         'nav_cart_view' => view('frontend.partials.cart')->render(),
                                //     );
                                // }
                                if (($str != null && $cartItem['variation'] == $str) || $str == null) {
                                    $foundInCart = true;

                                    $cartItem['quantity'] += $request['quantity'];

                                    if ($cart_product->wholesale_product) {
                                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                                if ($wholesalePrice) {
                                    $price = $wholesalePrice->price;
                                }
                            }

                            $cartItem['price'] = $price;

                            $cartItem->save();
                        }
                    }
                }
                if (!$foundInCart) {
                   Quotation::create($data);
                }
            } else {
                Quotation::create($data);
            }

            if (auth()->user() != null) {
                $user_id = Auth::user()->id;
                $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
            }
           // return true;
            return response()->json([
                'result' => true,
                'cart_count' => count($carts),]);
        } else {
            $price = $product->bids->max('amount');

            foreach ($product->taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $tax += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $tax += $product_tax->tax;
                }
            }

            $data['quantity'] = 1;
            $data['price'] = $price;
            $data['tax'] = $tax;
            $data['shipping_cost'] = 0;
            $data['product_referral_code'] = null;
            $data['cash_on_delivery'] = $product->cash_on_delivery;
            $data['digital'] = $product->digital;

            if (count($carts) == 0) {
                Quotation::create($data);
            }
            if (auth()->user() != null) {
                $user_id = Auth::user()->id;
                $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
            } else {
                $temp_user_id = $request->session()->get('temp_user_id1');
                $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->get();
            }
            return response()->json([
                'result' => false,
                'cart_count' => count($carts),]);
        }
    }


    public function view(Request $request)
    {


        //
        $currency_symbol = currency_symbol();
        $savequotations = array();
        $quotation_expire = array();
        $quotationlist = array();
        $savedquotationlist = array();
        $business_settings = BusinessSetting::where('type', 'quotation_expire')->first();
        $shops = [];


        if (auth()->user() != null) {

            $quotation_ids = Quotation::where('user_id',auth()->user()->id)->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray();


            if(!empty($quotation_ids)){
                foreach($quotation_ids as $quotations){
                    $user_id = Auth::user()->id;
                    $quotationCollection = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->where('owner_id', $quotations)->get()->toArray();

                   $quotationlist = array();
                   $total = 0;
                   $subTotal = 0;
                   $CGST= 0;
                   $SGST= 0;
                   $IGST= 0;
                   $CGST_Amount= 0;
                   $IGST_Amount= 0;
                   $SGST_Amount= 0;

                   $CGST_total = '0.00';
                   $SGST_total = '0.00';
                   $IGST_total = 0.00;
                   if(!empty($quotationCollection)){
                    foreach ($quotationCollection as $key => $cartItem){

                        $product = Product::find($cartItem['product_id']);
                        $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                        if(!empty($quotationOtherDetails['taxAvailable'])){
                            $total = $total + (cart_product_price($cartItem, $product, false) + $cartItem['tax']) * $cartItem['quantity'];
                        }else{
                            $total = $total + cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
                        }

                        $subTotal = $subTotal + ($cartItem['price']) * $cartItem['quantity'];
                        $product_name_with_choice = $product->getTranslation('name');
                        if ($cartItem['variation'] != null) {
                            $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variation'];
                        }


                        // if ($cartItem['digital'] != 1 && $product->auction_product == 0){
                        //     $quantity = $cartItem['quantity'];

                        // }else{
                        //     $quantity = 1;
                        // }

                        if(!empty($taxAvailable) && $taxAvailable == 1){
                            $CGST = $cartItem->tax1;
                            $CGST_Amount = $cartItem->tax1_amount * $cartItem['quantity'];
                            $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'];
                            $SGST = $cartItem->tax1;
                            $SGST_Amount = $cartItem->tax2_amount * $cartItem['quantity'];
                            $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'];


                        }
                        if(!empty($taxAvailable) && $taxAvailable == 2){
                            $IGST = $cartItem->tax1 + $cartItem->tax2;
                            $IGST_Amount = $cartItem->tax * $cartItem['quantity'];
                            $IGST_total += $cartItem->tax * $cartItem['quantity'];
                        }

                      $quotationlist[] = array('quotation_ids'              =>  intval($cartItem['id']),
                                                'owner_id'                  =>intval($cartItem['owner_id']),
                                                'user_id'                   =>intval($cartItem['user_id']),
                                                'product_id'                =>intval($cartItem['product_id']),
                                                'product_thumbnail_image'   =>   uploaded_asset($product['thumbnail_img']),
                                                'product_name'              =>   $product_name_with_choice,
                                                'variation'                 =>   $cartItem['variation'],
                                                'price'                     => $cartItem['price'],
                                                'shipping_cost'             =>$cartItem['shipping_cost'],
                                                'currency_symbol'           =>$currency_symbol,
                                                'tax'                       =>single_price($CGST_total+$SGST_total+$IGST_total),
                                                'cartprice'                 => cart_product_price($cartItem, $product, true, false),
                                                'quantity'                  =>$product->stocks['0']['qty'],
                                                'lower_limit'               =>$product->min_qty,
                                                'upper_limit'               =>intval($product->stocks->where('variant', $cartItem['variation'])->first()->qty),
                                        // 'cgst'                      =>$CGST,
                                        // 'cgst_amount'               =>$CGST_Amount,
                                        // 'cgst_total'                => $CGST_total,
                                        // 'sgst'                      =>$SGST,
                                        // 'sgst_amount'               =>$SGST_Amount,
                                        // 'sgst_total'                => $SGST_total,
                                        // 'igst'                      =>$IGST,
                                        // 'igst_amount'               =>$IGST_Amount,
                                        // 'igst_total'                => $IGST_total,
                                        // 'total'                     =>single_price(($cartItem['price'] ) * $cartItem['quantity']),
                                        // 'subtotal'                  =>single_price($subTotal),
                                        // 'totalcgst'                 =>single_price($CGST_total),
                                        // 'totalsgst'                 => single_price($SGST_total),
                                        // 'totaligst'                 => single_price($IGST_total),
                                        // 'overalltotal'              =>  single_price($total)
                                    );



                    }

                   }

                   $shop_data = Shop::where('user_id', $quotations)->first();
                   if ($shop_data) {
                       $shop['name'] = $shop_data->name;
                       $shop['owner_id'] =(int) $quotations;
                       $shop['cart_items'] = $quotationlist;
                   } else {
                       $shop['name'] = "Inhouse";
                       $shop['owner_id'] =(int) $quotations;
                       $shop['cart_items'] = $quotationlist;
                   }
                   $shops[] = $shop;



                }



            }








        }

        return response()->json($shops);


    }





    public function savedquote(Request $request){



        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $email = Auth::user()->email;
            if(isset($request->quotation_ids) && is_array($request->quotation_ids)){
                $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
            }else{
                $carts = Quotation::where('user_id', $user_id)->where('quotation_id',$request->quotation_ids)->get();
            }
            // old flow
            // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id1');
            $email =  $request->email;
            $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->get();
        }
        if (empty($email)) {

            return Response::json(['error' => 1,'message' => 'Please enter email'], 404);
        }
        $array['view'] = 'emails.quotation';
        $array['subject'] = 'Quotation';
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] =   $carts;
        $array['sender'] = env('MAIL_FROM_ADDRESS');

        $array['details'] = 'l';

        //TODO: Ticket(0000254) - Check user address within tamilnadu or outer state for GST Implementation
        $quotationOtherDetails = [];
        $taxAvailable = checkAuthUserAddress();
        $quotationOtherDetails['taxAvailable'] = $taxAvailable;
        // get quotation estimate number
        if(isset($request->quotation_ids) && $request->quotation_ids > '0' && !is_array($request->quotation_ids)){
            $quotationOtherDetails['quotation_estimate_number'] = (!empty($carts[0]->quotation_estimate_number) ) ? $carts[0]->quotation_estimate_number : "";
        }
        // Get User address details
        $addressDetails = getUserAddressDetails();
        $quotationOtherDetails['state_id'] = (!empty($addressDetails) && !empty($addressDetails['state_id'])) ? $addressDetails['state_id'] : "";
        $quotationOtherDetails['state_name'] = (!empty($addressDetails) && !empty($addressDetails['state_name'])) ? $addressDetails['state_name'] : "";
        $quotationOtherDetails['company_name'] = (!empty($addressDetails) && !empty($addressDetails['company_name'])) ? $addressDetails['company_name'] : "";
        $quotationOtherDetails['address'] = (!empty($addressDetails) && !empty($addressDetails['address'])) ? $addressDetails['address'] : "";
        $quotationOtherDetails['city_name'] = (!empty($addressDetails) && !empty($addressDetails['city_name'])) ? $addressDetails['city_name'] : "";
        $quotationOtherDetails['postal_code'] = (!empty($addressDetails) && !empty($addressDetails['postal_code'])) ? $addressDetails['postal_code'] : "";
        $quotationOtherDetails['phone_number'] = (!empty($addressDetails) && !empty($addressDetails['phone_number'])) ? $addressDetails['phone_number'] : "";


        $array['quotationOtherDetails'] = $quotationOtherDetails;

        try {
            $max_quotation_id = \DB::table('quotations')->max('quotation_id');
            if (auth()->user() != null) {
                if(isset($request->quotation_ids) && is_array($request->quotation_ids)){

                    // TODO: Ticket(0000255) - Update estimate number in business settings & quotation table
                   $estimateNumber = updateQuotationEstimateNumber();

                    $user_id = Auth::user()->id;
                    $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total,'quotation_estimate_number' => $estimateNumber]);

                }
                if(isset($request->quotation_ids) && $request->quotation_ids > '0' && !is_array($request->quotation_ids)){
                    Mail::to($email)->queue(new QuotationMail($array));
                }

                // old flow

                // $user_id = Auth::user()->id;
                // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total]);
            } else {
                Mail::to($email)->queue(new QuotationMail($array));
                $temp_user_id = $request->session()->get('temp_user_id1');
                $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total]);
            }
            return response()->json([
                'result' => true,
                'message'=>translate('Success saved quotation'),

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message'=> $e->getMessage(),

            ]);

        }
    }


    public function quoteupdate(Request $request){
       // dd($request->input());


        $cartItem = Quotation::findOrFail($request->id);

        $quotationlist = array();

        if ($cartItem['id'] == $request->id) {
            $product = Product::find($cartItem['product_id']);
            $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
            $quantity = $product_stock->qty;

            $price = $product_stock->price;



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

            if ($quantity >= $request->quantity) {
                if ($request->quantity >= $product->min_qty) {
                    $cartItem['quantity'] = $request->quantity;
                }
            }

            if ($product->wholesale_product) {
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                if ($wholesalePrice) {
                    $price = $wholesalePrice->price;
                }
            }
            // $cartItem['tax1_amount'] =(($price * $cartItem['tax1']) / 100);
            // $cartItem['tax2_amount'] =(($price * $cartItem['tax2']) / 100);
            // $cartItem['price'] = $price +  $cartItem['tax1_amount'] +  $cartItem['tax2_amount'];
            $cartItem['price'] = $price;

            dd($cartItem);
            $cartItem->save();
        }

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id1');
            $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->get();
        }
        $total = 0;
        $subTotal = 0;
        $IGST = 0;
        $IGST_Amount = 0;
        $IGST_total = 0;
        $CGST_total = 0;
        $SGST_total = 0;


        foreach ($carts as $key => $cartItem){
            $product = Product::find($cartItem['product_id']);
            $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
            $total = $total + ($cartItem['price']  + $cartItem['tax']) * $cartItem['quantity'];
            $subTotal = $subTotal + ($cartItem['price']) * $cartItem['quantity'];
            $product_name_with_choice = $product->getTranslation('name');
            if ($cartItem['variation'] != null) {
                $product_name_with_choice = $product->getTranslation('name').' - '.$cartItem['variation'];
            }


            $quotationlist[] = array('quotation_ids'      =>  $cartItem->id,
            'thumbnail'                =>   uploaded_asset($product->thumbnail_img),
          'product_name'                =>   $product_name_with_choice,
            'price'                     => single_price($cartItem['price']),
            'cartprice'                 => cart_product_price($cartItem, $product, true, false),
            'quantity'                  =>$quantity,
            'cgst'                      =>$cartItem->tax1,
            'cgst_amount'               =>$cartItem->tax1_amount * $cartItem['quantity'],
            'cgst_total'                =>  $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'],
            'sgst'                      =>$cartItem->tax2,
            'sgst_amount'               =>$cartItem->tax2_amount * $cartItem['quantity'],
            'sgst_total'                => $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'],
            'igst'                      =>$IGST,
            'igst_amount'               =>$IGST_Amount,
            'igst_total'                => $IGST_total,
            'total'                     =>single_price(($cartItem['price'] ) * $cartItem['quantity']),
            'subtotal'                  =>single_price($subTotal),
            'totalcgst'                 => single_price($CGST_total),
            'totalsgst'                 => single_price($SGST_total),
            'totaligst'                 => single_price($IGST_total),
            'overalltotal'              =>  single_price($total));
        }



        return response()->json([
            'result' => true,
            'cart_count'=>count($quotationlist),
            'quotationlist' =>$quotationlist,

        ]);


    }


    public function quoteView(Request $request,$id)
    {
        $quotations_id = decrypt($id);

        //
        if (auth()->user() != null && !empty($quotations_id)) {
            $user_id = Auth::user()->id;
             $quotationCollection = Quotation::where('user_id', $user_id)->where('quotation_id',$quotations_id)->get();

            if(count($quotationCollection) == 0){
                return response()->json([
                    'result' => false,
                    'message'=>translate('Your Quotation is empty'),

                ]);
            }

            $quotationlist = array();
            $total = 0;
            $subTotal = 0;
            $CGST= 0;
            $SGST= 0;
            $IGST= 0;
            $CGST_Amount= 0;
            $IGST_Amount= 0;
            $SGST_Amount= 0;

            $CGST_total = '0.00';
            $SGST_total = '0.00';
            $IGST_total = 0.00;

            foreach ($quotationCollection as $key => $cartItem){
                $product = Product::find($cartItem['product_id']);
                $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                if(!empty($quotationOtherDetails['taxAvailable'])){
                    $total = $total + (cart_product_price($cartItem, $product, false) + $cartItem['tax']) * $cartItem['quantity'];
                }else{
                    $total = $total + cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
                }

                $subTotal = $subTotal + ($cartItem['price']) * $cartItem['quantity'];
                $product_name_with_choice = $product->getTranslation('name');
                if ($cartItem['variation'] != null) {
                    $product_name_with_choice = $product->getTranslation('name') . ' - ' . $cartItem['variation'];
                }


                if ($cartItem['digital'] != 1 && $product->auction_product == 0){
                    $quantity = $cartItem['quantity'];

                }else{
                    $quantity = 1;
                }

                if(!empty($taxAvailable) && $taxAvailable == 1){
                    $CGST = $cartItem->tax1;
                    $CGST_Amount = $cartItem->tax1_amount * $cartItem['quantity'];
                    $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'];
                    $SGST = $cartItem->tax1;
                    $SGST_Amount = $cartItem->tax2_amount * $cartItem['quantity'];
                    $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'];


                }
                if(!empty($taxAvailable) && $taxAvailable == 2){
                    $IGST = $cartItem->tax1 + $cartItem->tax2;
                    $IGST_Amount = $cartItem->tax * $cartItem['quantity'];
                    $IGST_total += $cartItem->tax * $cartItem['quantity'];
                }

              $quotationlist[] = array('quotation_ids'      =>  $cartItem->id,
                                'thumbnail'                =>   uploaded_asset($product->thumbnail_img),
                              'product_name'                =>   $product_name_with_choice,
                                'price'                     => translate('Price'),
                                'cartprice'                 => cart_product_price($cartItem, $product, true, false),
                                'quantity'                  =>$quantity,
                                'cgst'                      =>$CGST,
                                'cgst_amount'               =>$CGST_Amount,
                                'cgst_total'                => $CGST_total,
                                'sgst'                      =>$SGST,
                                'sgst_amount'               =>$SGST_Amount,
                                'sgst_total'                => $SGST_total,
                                'igst'                      =>$IGST,
                                'igst_amount'               =>$IGST_Amount,
                                'igst_total'                => $IGST_total,
                                'total'                     =>single_price(($cartItem['price'] ) * $cartItem['quantity']),
                                'subtotal'                  =>single_price($subTotal),
                                'totalcgst'                 =>single_price($CGST_total),
                                'totalsgst'                 => single_price($SGST_total),
                                'totaligst'                 => single_price($IGST_total),
                                'overalltotal'              =>  single_price($total)





                             );



            }

            return response()->json([
                'result' => true,
                'quotationcount'=>count($quotationCollection),
                'quotationlist' =>$quotationlist,

            ]);

        }  else{
            return response()->json([
                'result' => false,
                'message'=>translate('Your Quotation is empty'),

            ]);
        }

    }






    public function sendMail(Request $request){

        $request->quotation_ids = decrypt($request->quotation_ids);

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $email = Auth::user()->email;
            if(isset($request->quotation_ids) && is_array($request->quotation_ids)){
                $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
            }else{
                $carts = Quotation::where('user_id', $user_id)->where('quotation_id',$request->quotation_ids)->get();
            }
            // old flow
            // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id1');
            $email =  $request->email;
            $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->get();
        }
        if (empty($email)) {
            return Response::json(['error' => 1,'message' => 'Please enter email'], 404);
        }
        $array['view'] = 'emails.quotation';
        $array['subject'] = 'Quotation';
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] =   $carts;
        $array['sender'] = env('MAIL_FROM_ADDRESS');

        $array['details'] = 'l';

        //TODO: Ticket(0000254) - Check user address within tamilnadu or outer state for GST Implementation
        $quotationOtherDetails = [];
        $taxAvailable = checkAuthUserAddress();
        $quotationOtherDetails['taxAvailable'] = $taxAvailable;
        // get quotation estimate number
        if(isset($request->quotation_ids) && $request->quotation_ids > '0' && !is_array($request->quotation_ids)){
            $quotationOtherDetails['quotation_estimate_number'] = (!empty($carts[0]->quotation_estimate_number) ) ? $carts[0]->quotation_estimate_number : "";
        }
        // Get User address details
        $addressDetails = getUserAddressDetails();
        $quotationOtherDetails['state_id'] = (!empty($addressDetails) && !empty($addressDetails['state_id'])) ? $addressDetails['state_id'] : "";
        $quotationOtherDetails['state_name'] = (!empty($addressDetails) && !empty($addressDetails['state_name'])) ? $addressDetails['state_name'] : "";
        $quotationOtherDetails['company_name'] = (!empty($addressDetails) && !empty($addressDetails['company_name'])) ? $addressDetails['company_name'] : "";
        $quotationOtherDetails['address'] = (!empty($addressDetails) && !empty($addressDetails['address'])) ? $addressDetails['address'] : "";
        $quotationOtherDetails['city_name'] = (!empty($addressDetails) && !empty($addressDetails['city_name'])) ? $addressDetails['city_name'] : "";
        $quotationOtherDetails['postal_code'] = (!empty($addressDetails) && !empty($addressDetails['postal_code'])) ? $addressDetails['postal_code'] : "";
        $quotationOtherDetails['phone_number'] = (!empty($addressDetails) && !empty($addressDetails['phone_number'])) ? $addressDetails['phone_number'] : "";


        $array['quotationOtherDetails'] = $quotationOtherDetails;

        try {
            $max_quotation_id = \DB::table('quotations')->max('quotation_id');
            if (auth()->user() != null) {
                if(isset($request->quotation_ids) && is_array($request->quotation_ids)){

                    // TODO: Ticket(0000255) - Update estimate number in business settings & quotation table
                   $estimateNumber = updateQuotationEstimateNumber();

                    $user_id = Auth::user()->id;
                    $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total,'quotation_estimate_number' => $estimateNumber]);

                }
                if(isset($request->quotation_ids) && $request->quotation_ids > '0' && !is_array($request->quotation_ids)){
                    Mail::to($email)->queue(new QuotationMail($array));
                }

                // old flow

                // $user_id = Auth::user()->id;
                // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total]);
            } else {
                Mail::to($email)->queue(new QuotationMail($array));
                $temp_user_id = $request->session()->get('temp_user_id1');
                $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total]);
            }
            return response()->json([
                'result' => true,
                'message'=>translate('Your Quotation sent to mail'),

            ]);
        } catch (\Exception $e) {

            return Response::json(['result' => false,'message' => $e->getMessage()], 404);
        }
    }


    public function quotation_invoice_download(Request $request)
    {
        $quotations_id = decrypt($request->quotation_id);
        $id = decrypt($request->quotation_id);
        $currency_code = Currency::findOrFail(get_setting('system_default_currency'))->code;
        $language_code = Session::get('locale', Config::get('app.locale'));
        if(Language::where('code', $language_code)->first()->rtl == 1){
            $direction = 'rtl';
            $text_align = 'right';
            $not_text_align = 'left';
        }else{
            $direction = 'ltr';
            $text_align = 'left';
            $not_text_align = 'right';
        }

        if($currency_code == 'BDT' || $language_code == 'bd'){
            $font_family = "'Hind Siliguri','sans-serif'";
        }elseif($currency_code == 'KHR' || $language_code == 'kh'){
            $font_family = "'Hanuman','sans-serif'";
        }elseif($currency_code == 'AMD'){
            $font_family = "'arnamu','sans-serif'";
        }elseif($currency_code == 'AED' || $currency_code == 'EGP' || $language_code == 'sa' || $currency_code == 'IQD' || $language_code == 'ir' || $language_code == 'om' || $currency_code == 'ROM' || $currency_code == 'SDG' || $currency_code == 'ILS'|| $language_code == 'jo'){
            $font_family = "'Baloo Bhaijaan 2','sans-serif'";
        }elseif($currency_code == 'THB'){
            $font_family = "'Kanit','sans-serif'";
        }else{
            $font_family = "'Roboto','sans-serif'";
        }
        $config = [];
        //
        if (auth()->user() != null && !empty($quotations_id)) {
            $user_id = Auth::user()->id;
            $quotation = Quotation::where('user_id', $user_id)->where('quotation_id',$quotations_id)->get();

            //TODO: Ticket(0000254) - Check user address within tamilnadu or outer state for GST Implementation
            $quotationOtherDetails = [];
            $taxAvailable = checkAuthUserAddress();
            $quotationOtherDetails['taxAvailable'] = $taxAvailable;
            // Get User address details
            $addressDetails = getUserAddressDetails();
            $quotationOtherDetails['state_id'] = (!empty($addressDetails) && !empty($addressDetails['state_id'])) ? $addressDetails['state_id'] : "";
            $quotationOtherDetails['state_name'] = (!empty($addressDetails) && !empty($addressDetails['state_name'])) ? $addressDetails['state_name'] : "";
            $quotationOtherDetails['company_name'] = (!empty($addressDetails) && !empty($addressDetails['company_name'])) ? $addressDetails['company_name'] : "";
            $quotationOtherDetails['address'] = (!empty($addressDetails) && !empty($addressDetails['address'])) ? $addressDetails['address'] : "";
            $quotationOtherDetails['city_name'] = (!empty($addressDetails) && !empty($addressDetails['city_name'])) ? $addressDetails['city_name'] : "";
            $quotationOtherDetails['postal_code'] = (!empty($addressDetails) && !empty($addressDetails['postal_code'])) ? $addressDetails['postal_code'] : "";
            $quotationOtherDetails['phone_number'] = (!empty($addressDetails) && !empty($addressDetails['phone_number'])) ? $addressDetails['phone_number'] : "";
            // get quotation estimate number
            $quotationOtherDetails['quotation_estimate_number'] = (!empty($quotation[0]->quotation_estimate_number) ) ? $quotation[0]->quotation_estimate_number : "";

            return PDF::loadView('backend.invoices.quote_invoice',[
                'quotation' => $quotation,
                'font_family' => $font_family,
                'direction' => $direction,
                'text_align' => $text_align,
                'not_text_align' => $not_text_align,
                'quotationOtherDetails' => $quotationOtherDetails,
            ], [], $config)->download('Quotation-'.$id.'.pdf');
        }


    }


    public function removeQuotation(Request $request)
    {

        Quotation::destroy($request->id);
        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id1');
            $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->get();
        }

        return response()->json([
            'result' => true,

            'message' =>'Quotation Deleted sucessfully',

        ]);
    }

    public function destroy($id)
    {
        Quotation::destroy($id);
        return response()->json(['result' => true, 'message' => translate('Product is successfully removed from your Quotation')], 200);
    }



}
