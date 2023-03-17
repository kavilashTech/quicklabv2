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
    public function store(Request $request)
    {
        $product_id = $request->id;
        $variant = $request->variant;
        $quantity = $request->quantity;
        if (auth()->user() == null) {
            return response()->json([
                'result' => false,
                'message' => translate('PLease Login to view quotation'),

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
        if ($product == '') {
            return response()->json(['result' => false, 'message' => "Invalid Product code"], 200);
        }

        $data['product_id'] = $product->id;
        $data['owner_id'] = $product->user_id;

        $str = '';
        $tax = 0;
        if ($product->auction_product == 0) {



            $data['variation'] = $variant;
            if ($variant == '') {
                $product_stock = $product->stocks->where('product_id', $product_id)->first();
                $price = $product->unit_price;
            } else {
                $product_stock = $product->stocks->where('variant', $variant)->first();
                $price = $product_stock->price;
            }
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
            $data['tax1'] = 0.00;
            $data['tax1_amount'] = 0.00;
            $data['tax2'] = 0.00;
            $data['tax2_amount'] = 0.00;
            $data['quantity'] = $request['quantity'];
            if ($request['quantity'] == null) {
                $data['quantity'] = 1;
            }
            foreach ($product->taxes as $product_tax) {
                $tax_name = Tax::where('id',$product_tax->tax_id)->first();

                $pproduct_tax = $product_tax->tax;
                $cut_pr = round($price * $pproduct_tax / (100 + $pproduct_tax),2);

                if($product_tax->tax_type == 'percent'){
                    $tax += ($price * $product_tax->tax) / 100;
                }
               /* elseif($product_tax->tax_type == 'amount'){
                    $tax += $product_tax->tax;
                }*/

                if(!empty($tax_name) && $tax_name->name == 'GST'){
                    $data['tax'] = $cut_pr;
                    $data['tax_percentage'] =  $product_tax->tax;
                }

                $splitTax = $product_tax->tax / 2;
                if(!empty($tax_name) && $tax_name->name == 'GST'){
                    $data['tax1'] =  $splitTax;
                    $data['tax1_amount'] =(($cut_pr) / 2);

                    $data['tax2'] =  $splitTax;
                    $data['tax2_amount'] =(($cut_pr) / 2);
                }
            }
            $cut_pr = round($price * $pproduct_tax / (100 + $pproduct_tax),2);

            $total_show_product_price = round($price - $cut_pr,2);

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

                    if ($cartItem['product_id'] == $request->id) {

                        $quantity = $product_stock->qty;


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
                // dd($data);
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
                'cart_count' => count($carts),
            ]);
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
                'result' => true,
                'message' => translate('Product added to cart successfully')
            ]);
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
            if (isset($request->id)) {
                // own view
                $quotation_ids = Quotation::where('user_id', auth()->user()->id)->where('quotation_id', $request->id)->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray();
            } else {
                //current view
                $quotation_ids = Quotation::where('user_id', auth()->user()->id)->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray();
            }




            if (!empty($quotation_ids)) {
                foreach ($quotation_ids as $quotations) {
                    $user_id = Auth::user()->id;
                    if (isset($request->id)) {
                        // own view
                        $quotationCollection = Quotation::where('user_id', $user_id)->where('quotation_id', $request->id)->where('owner_id', $quotations)->get()->toArray();
                    } else {
                        //current view
                        $quotationCollection = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->where('owner_id', $quotations)->get()->toArray();
                    }


                    $quotationlist = array();
                    $total = 0;
                    $subTotal = 0;
                    $CGST = 0;
                    $SGST = 0;
                    $IGST = 0;
                    $CGST_Amount = 0;
                    $IGST_Amount = 0;
                    $SGST_Amount = 0;

                    $CGST_total = '0.00';
                    $SGST_total = '0.00';
                    $IGST_total = 0.00;
                    if (!empty($quotationCollection)) {
                        foreach ($quotationCollection as $key => $cartItem) {

                            $product = Product::find($cartItem['product_id']);
                            $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                            if (!empty($quotationOtherDetails['taxAvailable'])) {
                                $total = $total + (cart_product_price($cartItem, $product, false) + $cartItem['tax']) * $cartItem['quantity'];
                            } else {
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

                            if (!empty($taxAvailable) && $taxAvailable == 1) {
                                $CGST = $cartItem->tax1;
                                $CGST_Amount = $cartItem->tax1_amount * $cartItem['quantity'];
                                $CGST_total += $cartItem->tax1_amount * $cartItem['quantity'];
                                $SGST = $cartItem->tax1;
                                $SGST_Amount = $cartItem->tax2_amount * $cartItem['quantity'];
                                $SGST_total += $cartItem->tax2_amount * $cartItem['quantity'];


                            }
                            if (!empty($taxAvailable) && $taxAvailable == 2) {
                                $IGST = $cartItem->tax1 + $cartItem->tax2;
                                $IGST_Amount = $cartItem->tax * $cartItem['quantity'];
                                $IGST_total += $cartItem->tax * $cartItem['quantity'];
                            }

                            $quotationlist[] = array(
                                'id' => intval($cartItem['id']),
                                'owner_id' => intval($cartItem['owner_id']),
                                'user_id' => intval($cartItem['user_id']),
                                'product_id' => intval($cartItem['product_id']),
                                'product_thumbnail_image' => uploaded_asset($product['thumbnail_img']),
                                'product_name' => $product_name_with_choice,
                                'variation' => $cartItem['variation'],
                                'price' => (double) $cartItem['price'],
                                'shipping_cost' => $cartItem['shipping_cost'],
                                'currency_symbol' => $currency_symbol,
                                'tax' => intval($CGST_total + $SGST_total + $IGST_total),
                                'cartprice' => (double) cart_product_price($cartItem, $product, true, false),
                                'quantity' => $cartItem['quantity'],
                                'lower_limit' => $product->min_qty,
                                'upper_limit' => intval($product_stock->qty), //static
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
                        $shop['owner_id'] = (int) $quotations;
                        $shop['cart_items'] = $quotationlist;
                    } else {
                        $shop['name'] = "Inhouse";
                        $shop['owner_id'] = (int) $quotations;
                        $shop['cart_items'] = $quotationlist;
                    }
                    $shops[] = $shop;



                }



            }








        }

        return response()->json($shops);


    }





    public function savedquote(Request $request)
    {

        $quotation_ids = explode(",", $request->quotation_ids);

        // $email =Auth::user()->email;
        $i = 0;

        $user_id = Auth::user()->id;
        $email = Auth::user()->email;
        if (isset($quotation_ids[$i]) && is_array($quotation_ids)) {
            $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        } else {
            $carts = Quotation::where('user_id', $user_id)->where('quotation_id', $quotation_ids[$i])->get();
        }
        if (empty($email)) {

            return Response::json(['error' => 1, 'message' => 'Please enter email'], 404);
        }
        $array['view'] = 'emails.quotation';
        $array['subject'] = 'Quotation';
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] = $carts;
        $array['sender'] = env('MAIL_FROM_ADDRESS');

        $array['details'] = 'l';

        //TODO: Ticket(0000254) - Check user address within tamilnadu or outer state for GST Implementation
        $quotationOtherDetails = [];
        $taxAvailable = checkAuthUserAddress();
        $quotationOtherDetails['taxAvailable'] = $taxAvailable;
        // get quotation estimate number
        if (isset($quotation_ids[$i]) && $quotation_ids[$i] > '0' && !is_array($quotation_ids)) {
            $quotationOtherDetails['quotation_estimate_number'] = (!empty($carts[0]->quotation_estimate_number)) ? $carts[0]->quotation_estimate_number : "";
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

                if (isset($quotation_ids[$i]) && is_array($quotation_ids)) {

                    // TODO: Ticket(0000255) - Update estimate number in business settings & quotation table
                    $estimateNumber = updateQuotationEstimateNumber();
                    $quotationid = $quotation_ids[$i];
                    //dd($estimateNumber);


                    $user_id = Auth::user()->id;
                    $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id', $quotation_ids)->update(['quotation_id' => $max_quotation_id + 1, 'quote_total' => $request->total, 'quotation_estimate_number' => $estimateNumber]);
                    // dd($carts);
                }

                if (isset($quotation_ids[$i]) && $quotation_ids[$i] > '0' && !is_array($quotation_ids)) {
                    Mail::to($email)->queue(new QuotationMail($array));
                }

                // old flow

                // $user_id = Auth::user()->id;
                // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),

            ]);

        }


        return response()->json([
            'result' => true,
            'message' => translate('Success saved quotation'),

        ]);
    }




    public function save_quote_view(Request $request)
    {

        $savequotationsdata = array();
        $quotation_expire = array();
        $business_settings = BusinessSetting::where('type', 'quotation_expire')->first();
        $user_id = Auth::user()->id;

        $savequotations = Quotation::where('user_id', Auth::user()->id)->whereNotNull('quotation_id')->groupBy('quotation_id')->get();
        if (count($savequotations) > 0) {
            $i = 1;
            foreach ($savequotations as $quotation) {

                $addday = ($business_settings != '') ? $business_settings->value : '';
                $last_date = date('Y-m-d', strtotime($quotation->created_at->addDays($addday)));
                $today_date = date('Y-m-d', strtotime($quotation->created_at));
                $date_now = date("Y-m-d");

                if ($date_now > $last_date) {
                    $status = 'Expired';
                } else {
                    $status = 'Active';
                }

                $savequotationsdata[] = array(
                    'sno' => $i++,
                    'date' => date('d-m-Y', strtotime($quotation->created_at)),
                    'amount' => single_price($quotation->quote_total),
                    'valid till' => date('d-m-Y', strtotime($last_date)),
                    'status' => $status,
                    'downlink' => route('quotationInvoice.download', $quotation->quotation_id),
                    'quotation_id' => $quotation->quotation_id

                );

            }



        }
        return response()->json($savequotationsdata);






    }
    public function quoteupdate(Request $request)
    {

        $quotation_ids = explode(",", $request->id);
        $quotation_quantities = explode(",", $request->quantities);

        if (!empty($quotation_ids)) {
            $i = 0;
            foreach ($quotation_ids as $quotation_id) {
                $cartItem = Quotation::findOrFail($quotation_ids[$i]);
                if ($cartItem == '') {
                    return response()->json(['result' => false, 'message' => translate('Invalid Quotation id')], 200);
                }
                $product = Product::where('id', $cartItem['product_id'])->first();
                if ($product->variant_product == '1') {
                    $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                } else {
                    $product_stock = $product->stocks->where('product_id', $product->id)->first();
                }
                $quantity = $product_stock->qty;
                $price = $product_stock->price;
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

                if ($quantity >= $quotation_quantities[$i]) {
                    if ($quotation_quantities[$i] >= $product->min_qty) {
                        $cartItem['quantity'] = $quotation_quantities[$i];
                    }
                }
                if ($product->wholesale_product) {
                    $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $quotation_quantities[$i])->where('max_qty', '>=', $quotation_quantities[$i])->first();
                    if ($wholesalePrice) {
                        $price = $wholesalePrice->price;
                    }
                }
                //dd($cartItem);
                $cartItem['price'] = $price;
                $cartItem->save();
                $i++;


            }


            return response()->json(['result' => true, 'message' => translate('Quotation updated')], 200);

        } else {
            return response()->json(['result' => false, 'message' => translate('Quotation is empty')], 200);
        }

    }






    public function sendMail(Request $request)
    {

        $request->quotation_ids = $request->quotation_ids;

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $email = 'vetri654vel@gmail.com';
            if (isset($request->quotation_ids) && is_array($request->quotation_ids)) {
                $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
            } else {
                $carts = Quotation::where('user_id', $user_id)->where('quotation_id', $request->quotation_ids)->get();
            }
            // old flow
            // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id1');
            $email = $request->email;
            $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->get();
        }
        if (empty($email)) {
            return Response::json(['error' => 1, 'message' => 'Please enter email'], 404);
        }
        $array['view'] = 'emails.quotation';
        $array['subject'] = 'Quotation';
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] = $carts;
        $array['sender'] = env('MAIL_FROM_ADDRESS');

        $array['details'] = 'l';

        //TODO: Ticket(0000254) - Check user address within tamilnadu or outer state for GST Implementation
        $quotationOtherDetails = [];
        $taxAvailable = checkAuthUserAddress();
        $quotationOtherDetails['taxAvailable'] = $taxAvailable;
        // get quotation estimate number
        if (isset($request->quotation_ids) && $request->quotation_ids > '0' && !is_array($request->quotation_ids)) {
            $quotationOtherDetails['quotation_estimate_number'] = (!empty($carts[0]->quotation_estimate_number)) ? $carts[0]->quotation_estimate_number : "";
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
                if (isset($request->quotation_ids) && is_array($request->quotation_ids)) {

                    // TODO: Ticket(0000255) - Update estimate number in business settings & quotation table
                    $estimateNumber = updateQuotationEstimateNumber();

                    $user_id = Auth::user()->id;
                    $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id', $request->quotation_ids)->update(['quotation_id' => $max_quotation_id + 1, 'quote_total' => $request->total, 'quotation_estimate_number' => $estimateNumber]);

                }
                if (isset($request->quotation_ids) && $request->quotation_ids > '0' && !is_array($request->quotation_ids)) {
                    Mail::to($email)->queue(new QuotationMail($array));
                }

                // old flow

                // $user_id = Auth::user()->id;
                // $carts = Quotation::where('user_id', $user_id)->whereNull('quotation_id')->whereIn('id',$request->quotation_ids)->update(['quotation_id' => $max_quotation_id+1,'quote_total' => $request->total]);
            } else {
                Mail::to($email)->queue(new QuotationMail($array));
                $temp_user_id = $request->session()->get('temp_user_id1');
                $carts = Quotation::where('temp_user_id', $temp_user_id)->whereNull('quotation_id')->whereIn('id', $request->quotation_ids)->update(['quotation_id' => $max_quotation_id + 1, 'quote_total' => $request->total]);
            }
            return response()->json([
                'result' => true,
                'message' => translate('Your Quotation sent to mail'),

            ]);
        } catch (\Exception $e) {

            return Response::json(['result' => false, 'message' => $e->getMessage()], 404);
        }
    }


    public function quotation_invoice_download(Request $request)
    {
        $quotations_id = decrypt($request->quotation_id);
        $id = decrypt($request->quotation_id);
        $currency_code = Currency::findOrFail(get_setting('system_default_currency'))->code;
        $language_code = Session::get('locale', Config::get('app.locale'));
        if (Language::where('code', $language_code)->first()->rtl == 1) {
            $direction = 'rtl';
            $text_align = 'right';
            $not_text_align = 'left';
        } else {
            $direction = 'ltr';
            $text_align = 'left';
            $not_text_align = 'right';
        }

        if ($currency_code == 'BDT' || $language_code == 'bd') {
            $font_family = "'Hind Siliguri','sans-serif'";
        } elseif ($currency_code == 'KHR' || $language_code == 'kh') {
            $font_family = "'Hanuman','sans-serif'";
        } elseif ($currency_code == 'AMD') {
            $font_family = "'arnamu','sans-serif'";
        } elseif ($currency_code == 'AED' || $currency_code == 'EGP' || $language_code == 'sa' || $currency_code == 'IQD' || $language_code == 'ir' || $language_code == 'om' || $currency_code == 'ROM' || $currency_code == 'SDG' || $currency_code == 'ILS' || $language_code == 'jo') {
            $font_family = "'Baloo Bhaijaan 2','sans-serif'";
        } elseif ($currency_code == 'THB') {
            $font_family = "'Kanit','sans-serif'";
        } else {
            $font_family = "'Roboto','sans-serif'";
        }
        $config = [];
        //
        if (auth()->user() != null && !empty($quotations_id)) {
            $user_id = Auth::user()->id;
            $quotation = Quotation::where('user_id', $user_id)->where('quotation_id', $quotations_id)->get();

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
            $quotationOtherDetails['quotation_estimate_number'] = (!empty($quotation[0]->quotation_estimate_number)) ? $quotation[0]->quotation_estimate_number : "";

            return PDF::loadView('backend.invoices.quote_invoice', [
                'quotation' => $quotation,
                'font_family' => $font_family,
                'direction' => $direction,
                'text_align' => $text_align,
                'not_text_align' => $not_text_align,
                'quotationOtherDetails' => $quotationOtherDetails,
            ], [], $config)->download('Quotation-' . $id . '.pdf');
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

            'message' => 'Quotation Deleted sucessfully',

        ]);
    }

    public function destroy($id)
    {
        Quotation::destroy($id);
        return response()->json(['result' => true, 'message' => translate('Product is successfully removed from your Quotation')], 200);
    }



}
