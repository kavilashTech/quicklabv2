<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Hash;
use App\Models\Address;
use App\Models\Category;
use App\Models\FlashDeal;
use App\Models\Brand;
use App\Models\Product;
use App\Models\PickupPoint;
use App\Models\CustomerPackage;
use App\Models\User;
use App\Models\Shop;
use App\Models\Order;
use App\Models\Coupon;
use Cookie;
use Illuminate\Support\Str;
use App\Mail\SecondEmailVerifyMailManager;
use App\Models\AffiliateConfig;
use App\Models\Page;
use App\Models\Currency;
use App\Models\ProductQuery;
use Mail;
use Illuminate\Auth\Events\PasswordReset;
use Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use App\Models\Tax;
use App\Models\City;
use App\Models\State;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\validate;
use File;
use Session;
use Artisan;

class HomeController extends Controller
{

    /**
     * Show the application frontend home.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        //dd(Session::get('first_currency_symbol'));
        if (Session::get('currency_symbol') != Session::get('first_currency_symbol') ) {
            Artisan::call('cache:clear');
            get_system_default_currency()->symbol;

        }else{
             //$ip = getRealUserIp();
         $ip = '152.58.214.63';//india
        // $ip = '27.122.14.74'; //kong
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
        //dd($decoded);
        if(isset($decoded)){
            if($decoded['countryCode'] == 'IN'){
                $code = 'INR';

                $currency = Currency::where('code', $code)->first();

                session()->put('first_currency_code', $currency->code);
                session()->put('first_currency_symbol', $currency->symbol);
                get_system_default_currency()->symbol;

                //return Currency:: where('code', 'like', '%'.$code.'%')->first();
            }else{
                $code = 'USD';
                $currency = Currency::where('code', $code)->first();
                session()->put('first_currency_code', $currency->code);
                session()->put('first_currency_symbol', $currency->symbol);
                get_system_default_currency()->symbol;
                //return Currency:: where('code', 'like', '%'.$code.'%')->first();
            }
        }else{

        }

        }





          // if (!empty(auth()->user()) && auth()->user()->user_type == 'partner' && (auth()->user()->user_type == 0 || auth()->user()->status == 3)){
        if (!empty(auth()->user()) && auth()->user()->user_type == 'partner' && (auth()->user()->status == 3)){
            flash(translate('You do not have access to this page'))->error();

            return redirect()->route('franchisee-registration-form');
        }

        $featured_categories = Cache::rememberForever('featured_categories', function () {
            return Category::where('featured', 1)->get();
        });

        $todays_deal_products = Cache::rememberForever('todays_deal_products', function () {
            return filter_products(Product::where('published', 1)->where('todays_deal', '1'))->get();
        });

        $newest_products = Cache::remember('newest_products', 3600, function () {
            return filter_products(Product::latest())->limit(12)->get();
        });

        return view('frontend.index', compact('featured_categories', 'todays_deal_products', 'newest_products'));
    }

    public function login()
    {

        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('frontend.user_login');
    }

    public function registration(Request $request)
    {

        if (Auth::check()) {
            return redirect()->route('home');
        }

        if ($request->has('referral_code') && addon_is_activated('affiliate_system')) {

            try {
                $affiliate_validation_time = AffiliateConfig::where('type', 'validation_time')->first();
                $cookie_minute = 30 * 24;
                if ($affiliate_validation_time) {
                    $cookie_minute = $affiliate_validation_time->value * 60;
                }

                Cookie::queue('referral_code', $request->referral_code, $cookie_minute);
                $referred_by_user = User::where('referral_code', $request->referral_code)->first();

                $affiliateController = new AffiliateController;
                $affiliateController->processAffiliateStats($referred_by_user->id, 1, 0, 0, 0);
            } catch (\Exception $e) {
            }
        }
        return view('frontend.user_registration');
    }

    public function franchiseeRegistration(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('frontend.franchisee_registration');
    }

    public function franchiseedocumentupload(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $data = array('email' => auth()->user()->email);
        return view('frontend.franchisee_uploads_documents',compact('data'));
    }

    public function franchisee_document_upload(Request $request)
    {
        $request->validate([
            'email'         => 'required',
            'id_proof'      => 'required',
            'address_proof' => 'required',
        ]);

        $userdata = User::where('email',$request->email)->first();
        if (!empty($userdata)) {

            if(isset($request->id_proof) && !empty($request->id_proof)){
                $destinationPath =  Storage::disk('public')->path('franchisee/id_proof');
                if ($userdata->id_proof != '')
                {
                    if(!empty($userdata) && !empty($userdata->id_proof)){
                        if(File::exists($destinationPath.'/'.$userdata->id_proof)) {
                            $delete=File::delete($destinationPath.'/'.$userdata->id_proof);
                        }
                    }
                }
                // create directory if not found then
                if(!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath,0777, true, true);
                }
                $idproof        = $request->file('id_proof');
                $fileName       = time()."_".rand(11111,99999).".".$idproof->getClientOriginalExtension();
                $upload_success = $idproof->move($destinationPath, $fileName);

                User::where('id',$userdata->id)->update([
                    'id_proof'      => !empty($fileName) ? $fileName : '',
                ]);

            }
            if(isset($request->address_proof) && !empty($request->address_proof)){
                $destinationPath2 =  Storage::disk('public')->path('franchisee/address_proof');
                if ($userdata->address_proof != '')
                {
                    if(!empty($userdata) && !empty($userdata->address_proof)){
                        if(File::exists($destinationPath2.'/'.$userdata->address_proof)) {
                            $delete=File::delete($destinationPath2.'/'.$userdata->address_proof);
                        }
                    }
                }
                // create directory if not found then
                if(!File::exists($destinationPath2)) {
                    File::makeDirectory($destinationPath2,0777, true, true);
                }
                $addressproof       = $request->file('address_proof');
                $fileName2          = time()."_".rand(11111,99999).".".$addressproof->getClientOriginalExtension();
                $upload_success     = $addressproof->move($destinationPath2, $fileName2);

                User::where('id',$userdata->id)->update([
                    'address_proof'      => !empty($fileName2) ? $fileName2 : '',
                ]);
            }

            flash('Document upload successfully!')->success();
        }
        else
        {
            flash('Document upload failed!')->warning();
        }
        return redirect()->route('user.login');


     }


    public function cart_login(Request $request)
    {
        $user = null;
        if ($request->get('phone') != null) {
            $user = User::whereIn('user_type', ['customer', 'seller'])->where('phone', "+{$request['country_code']}{$request['phone']}")->first();
        } elseif ($request->get('email') != null) {
            $user = User::whereIn('user_type', ['customer', 'seller'])->where('email', $request->email)->first();
        }

        if ($user != null) {
            if (Hash::check($request->password, $user->password)) {
                if ($request->has('remember')) {
                    auth()->login($user, true);
                } else {
                    auth()->login($user, false);
                }
            } else {
                flash(translate('Invalid email or password!'))->warning();
            }
        } else {
            flash(translate('Invalid email or password!'))->warning();
        }
        return back();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the customer/seller dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        if (Auth::user()->user_type == 'seller') {
            return redirect()->route('seller.dashboard');
        } elseif (Auth::user()->user_type == 'customer') {
            return view('frontend.user.customer.dashboard');
        } elseif (Auth::user()->user_type == 'delivery_boy') {
            return view('delivery_boys.frontend.dashboard');
        } elseif(Auth::user()->user_type == 'partner'){
            return view('frontend.partner.dashboard');
        } else {
            abort(404);
        }
    }

    public function profile(Request $request)
    {
        if (Auth::user()->user_type == 'seller') {
            return redirect()->route('seller.profile.index');
        } elseif (Auth::user()->user_type == 'delivery_boy') {
            return view('delivery_boys.frontend.profile');
        } else {
            // Get User address details
            $addressDetails = getUserAddressDetails();
            return view('frontend.user.profile', compact('addressDetails'));
        }
    }

    public function userProfileUpdate(Request $request)
    {

        //echo "<pre>";print_r($request->photo);die;
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }

        $user = Auth::user();
        $user->name = $request->name;
        /*TODO - 0000273 : No need to update here below commented columns and we can added in contact address section */
        /*$user->address = $request->address;
        $user->country = $request->country;
        $user->city = $request->city;
        $user->postal_code = $request->postal_code;*/
        $user->phone = $request->phone;

        if ($request->new_password != null && ($request->new_password == $request->confirm_password)) {
            $user->password = Hash::make($request->new_password);
        }

        $user->avatar_original = $request->photo;
        $user->save();

        flash(translate('Your Profile has been updated successfully!'))->success();
        return back();
    }

    public function userCompanyUpdate(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }
        $user = Auth::user();
        $user->company_name = $request->company_name;
        $user->gst_no = $request->gst_no;
        $user->save();

        flash(translate('Your Company details has been updated successfully!'))->success();
        return back();
    }

    public function flash_deal_details($slug)
    {
        $flash_deal = FlashDeal::where('slug', $slug)->first();
        if ($flash_deal != null)
            return view('frontend.flash_deal_details', compact('flash_deal'));
        else {
            abort(404);
        }
    }

    public function load_featured_section()
    {
        return view('frontend.partials.featured_products_section');
    }

    public function load_best_selling_section()
    {
        return view('frontend.partials.best_selling_section');
    }

    public function load_auction_products_section()
    {
        if (!addon_is_activated('auction')) {
            return;
        }
        return view('auction.frontend.auction_products_section');
    }

    public function load_home_categories_section()
    {
        return view('frontend.partials.home_categories_section');
    }

    public function load_best_sellers_section()
    {
        return view('frontend.partials.best_sellers_section');
    }

    public function trackOrder(Request $request)
    {
        if ($request->has('order_code')) {
            $order = Order::where('code', $request->order_code)->first();
            if ($order != null) {
                return view('frontend.track_order', compact('order'));
            }
        }
        return view('frontend.track_order');
    }

    public function product(Request $request, $slug)
    {
        $detailedProduct  = Product::with('reviews', 'brand', 'stocks', 'user', 'user.shop')->where('auction_product', 0)->where('slug', $slug)->where('approved', 1)->first();
        $product_queries = ProductQuery::where('product_id', $detailedProduct->id)->where('customer_id', '!=', Auth::id())->latest('id')->paginate(10);
        $total_query = ProductQuery::where('product_id', $detailedProduct->id)->count();
        // Pagination using Ajax
        if (request()->ajax()) {
            return Response::json(View::make('frontend.partials.product_query_pagination', array('product_queries' => $product_queries))->render());
        }
        // End of Pagination using Ajax

        if ($detailedProduct != null && $detailedProduct->published) {
            if ($request->has('product_referral_code') && addon_is_activated('affiliate_system')) {
                $affiliate_validation_time = AffiliateConfig::where('type', 'validation_time')->first();
                $cookie_minute = 30 * 24;
                if ($affiliate_validation_time) {
                    $cookie_minute = $affiliate_validation_time->value * 60;
                }
                Cookie::queue('product_referral_code', $request->product_referral_code, $cookie_minute);
                Cookie::queue('referred_product_id', $detailedProduct->id, $cookie_minute);

                $referred_by_user = User::where('referral_code', $request->product_referral_code)->first();

                $affiliateController = new AffiliateController;
                $affiliateController->processAffiliateStats($referred_by_user->id, 1, 0, 0, 0);
            }
            if ($detailedProduct->digital == 1) {
                return view('frontend.digital_product_details', compact('detailedProduct', 'product_queries', 'total_query'));
            } else {
                return view('frontend.product_details', compact('detailedProduct', 'product_queries', 'total_query'));
            }
        }
        abort(404);
    }

    public function shop($slug)
    {
        $shop  = Shop::where('slug', $slug)->first();
        if ($shop != null) {
            if ($shop->verification_status != 0) {
                return view('frontend.seller_shop', compact('shop'));
            } else {
                return view('frontend.seller_shop_without_verification', compact('shop'));
            }
        }
        abort(404);
    }

    public function filter_shop($slug, $type)
    {
        $shop  = Shop::where('slug', $slug)->first();
        if ($shop != null && $type != null) {
            return view('frontend.seller_shop', compact('shop', 'type'));
        }
        abort(404);
    }

    public function all_categories(Request $request)
    {
        $categories = Category::where('level', 0)->orderBy('order_level', 'desc')->get();
        return view('frontend.all_category', compact('categories'));
    }

    public function all_brands(Request $request)
    {
        $categories = Category::all();
        return view('frontend.all_brand', compact('categories'));
    }

    public function home_settings(Request $request)
    {
        return view('home_settings.index');
    }

    public function top_10_settings(Request $request)
    {
        foreach (Category::all() as $key => $category) {
            if (is_array($request->top_categories) && in_array($category->id, $request->top_categories)) {
                $category->top = 1;
                $category->save();
            } else {
                $category->top = 0;
                $category->save();
            }
        }

        foreach (Brand::all() as $key => $brand) {
            if (is_array($request->top_brands) && in_array($brand->id, $request->top_brands)) {
                $brand->top = 1;
                $brand->save();
            } else {
                $brand->top = 0;
                $brand->save();
            }
        }

        flash(translate('Top 10 categories and brands have been updated successfully'))->success();
        return redirect()->route('home_settings.index');
    }

    public function variant_price(Request $request)
    {
        $product = Product::find($request->id);
        $str = '';
        $quantity = 0;
        $tax = 0;
        $max_limit = 0;

        if ($request->has('color')) {
            $str = $request['color'];
        }

        if (json_decode($product->choice_options) != null) {
            foreach (json_decode($product->choice_options) as $key => $choice) {
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request['attribute_id_' . $choice->attribute_id]);
                } else {
                    $str .= str_replace(' ', '', $request['attribute_id_' . $choice->attribute_id]);
                }
            }
        }

        $product_stock = $product->stocks->where('variant', $str)->first();
        if (Session::get('currency_code') == 'USD') {

            $price = $product_stock->usd_price;

        } else {
            $price = $product_stock->price;

        }

        //$price = $product_stock->price;


        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price;
            }
        }

        $quantity = $product_stock->qty;
        $max_limit = $product_stock->qty;

        if ($quantity >= 1 && $product->min_qty <= $quantity) {
            $in_stock = 1;
        } else {
            $in_stock = 0;
        }

        //Product Stock Visibility
        if ($product->stock_visibility_state == 'text') {
            if ($quantity >= 1 && $product->min_qty < $quantity) {
                $quantity = translate('In Stock');
            } else {
                $quantity = translate('Out Of Stock');
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

        // taxes
        // foreach ($product->taxes as $product_tax) {
        //     $tax_name = Tax::where('id',$product_tax->tax_id)->first();
        //     if(!empty($tax_name) && ($tax_name->name == 'CGST' || $tax_name->name == 'SGST')){
        //         if($product_tax->tax_type == 'percent'){
        //             $tax += ($price * $product_tax->tax) / 100;
        //         }
        //         elseif($product_tax->tax_type == 'amount'){
        //             $tax += $product_tax->tax;
        //         }
        //     }

        //     // if ($product_tax->tax_type == 'percent') {
        //     //     $tax += ($price * $product_tax->tax) / 100;
        //     // } elseif ($product_tax->tax_type == 'amount') {
        //     //     $tax += $product_tax->tax;
        //     // }
        // }
        // $price += $tax;

        return array(
            'price' => single_price($price * $request->quantity),
            'quantity' => $quantity,
            'digital' => $product->digital,
            'variation' => $str,
            'max_limit' => $max_limit,
            'in_stock' => $in_stock,
            'wholesalePrice' => single_price($product_stock->wholesale_price * 1),
        );
    }

    public function sellerpolicy()
    {
        $page =  Page::where('type', 'seller_policy_page')->first();
        return view("frontend.policies.sellerpolicy", compact('page'));
    }

    public function returnpolicy()
    {
        $page =  Page::where('type', 'return_policy_page')->first();
        return view("frontend.policies.returnpolicy", compact('page'));
    }

    public function supportpolicy()
    {
        $page =  Page::where('type', 'support_policy_page')->first();
        return view("frontend.policies.supportpolicy", compact('page'));
    }

    public function terms()
    {
        $page =  Page::where('type', 'terms_conditions_page')->first();
        return view("frontend.policies.terms", compact('page'));
    }

    public function privacypolicy()
    {
        $page =  Page::where('type', 'privacy_policy_page')->first();
        return view("frontend.policies.privacypolicy", compact('page'));
    }

    public function get_pick_up_points(Request $request)
    {
        $pick_up_points = PickupPoint::all();
        return view('frontend.partials.pick_up_points', compact('pick_up_points'));
    }

    public function get_category_items(Request $request)
    {
        $category = Category::findOrFail($request->id);
        return view('frontend.partials.category_elements', compact('category'));
    }

    public function premium_package_index()
    {
        $customer_packages = CustomerPackage::all();
        return view('frontend.user.customer_packages_lists', compact('customer_packages'));
    }

    // public function new_page()
    // {
    //     $user = User::where('user_type', 'admin')->first();
    //     auth()->login($user);
    //     return redirect()->route('admin.dashboard');

    // }


    // Ajax call
    public function new_verify(Request $request)
    {
        $email = $request->email;
        if (isUnique($email) == '0') {
            $response['status'] = 2;
            $response['message'] = 'Email already exists!';
            return json_encode($response);
        }

        $response = $this->send_email_change_verification_mail($request, $email);
        return json_encode($response);
    }


    // Form request
    public function update_email(Request $request)
    {
        $email = $request->email;
        if (isUnique($email)) {
            $this->send_email_change_verification_mail($request, $email);
            flash(translate('A verification mail has been sent to the mail you provided us with.'))->success();
            return back();
        }

        flash(translate('Email already exists!'))->warning();
        return back();
    }

    public function send_email_change_verification_mail($request, $email)
    {
        $response['status'] = 0;
        $response['message'] = 'Unknown';

        $verification_code = Str::random(32);

        $array['subject'] = 'Email Verification';
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] = 'Verify your account';
        $array['link'] = route('email_change.callback') . '?new_email_verificiation_code=' . $verification_code . '&email=' . $email;
        $array['sender'] = Auth::user()->name;
        $array['details'] = "Email Second";

        $user = Auth::user();
        $user->new_email_verificiation_code = $verification_code;
        $user->save();

        try {
            Mail::to($email)->queue(new SecondEmailVerifyMailManager($array));

            $response['status'] = 1;
            $response['message'] = translate("Your verification mail has been Sent to your email.");
        } catch (\Exception $e) {
            // return $e->getMessage();
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
        }

        return $response;
    }

    public function email_change_callback(Request $request)
    {
        if ($request->has('new_email_verificiation_code') && $request->has('email')) {
            $verification_code_of_url_param =  $request->input('new_email_verificiation_code');
            $user = User::where('new_email_verificiation_code', $verification_code_of_url_param)->first();

            if ($user != null) {

                $user->email = $request->input('email');
                $user->new_email_verificiation_code = null;
                $user->save();

                auth()->login($user, true);

                flash(translate('Email Changed successfully'))->success();
                if ($user->user_type == 'seller') {
                    return redirect()->route('seller.dashboard');
                }
                return redirect()->route('dashboard');
            }
        }

        flash(translate('Email was not verified. Please resend your mail!'))->error();
        return redirect()->route('dashboard');
    }

    public function reset_password_with_code(Request $request)
    {

        if (($user = User::where('email', $request->email)->where('verification_code', $request->code)->first()) != null) {
            if ($request->password == $request->password_confirmation) {
                $user->password = Hash::make($request->password);
                $user->email_verified_at = date('Y-m-d h:m:s');
                $user->save();
                event(new PasswordReset($user));
                auth()->login($user, true);

                flash(translate('Password updated successfully'))->success();

                if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('home');
            } else {
                flash("Password and confirm password didn't match")->warning();
                return view('auth.passwords.reset');
            }
        } else {
            flash("Verification code mismatch")->error();
            return view('auth.passwords.reset');
        }
    }


    public function all_flash_deals()
    {
        $today = strtotime(date('Y-m-d H:i:s'));

        $data['all_flash_deals'] = FlashDeal::where('status', 1)
            ->where('start_date', "<=", $today)
            ->where('end_date', ">", $today)
            ->orderBy('created_at', 'desc')
            ->get();

        return view("frontend.flash_deal.all_flash_deal_list", $data);
    }

    public function all_seller(Request $request)
    {
        $shops = Shop::whereIn('user_id', verified_sellers_id())
            ->paginate(15);

        return view('frontend.shop_listing', compact('shops'));
    }

    public function all_coupons(Request $request)
    {
        $coupons = Coupon::where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')))->paginate(15);
        return view('frontend.coupons', compact('coupons'));
    }

    public function inhouse_products(Request $request)
    {
        $products = filter_products(Product::where('added_by', 'admin'))->with('taxes')->paginate(12)->appends(request()->query());
        return view('frontend.inhouse_products', compact('products'));
    }

     /**
     * Update user company address
     * @return \Illuminate\Http\Response
     */
    public function updateUserContactAddress(Request $request)
    {
        $user = Auth::user();
        $user->address = $request->address;
        $user->country = $request->country_id;
        $user->state = $request->state_id;
        $user->city = $request->city_id;
        $user->phone = $request->phone;
        $user->postal_code = $request->postal_code;
        $user->save();

        if($request->country_id != '101'){
            $address = new Address;

            $address->user_id       = $user->id;
            $address->address       = $request->address;
            $address->country_id    = $request->country_id;
            $address->state_id      = $request->state_id;
            $address->city_id       = $request->city_id;
            $address->longitude     = $request->longitude;
            $address->latitude      = $request->latitude;
            $address->postal_code   = $request->postal_code;
            $address->phone         = $request->phone;
            $address->save();

        };
        flash(translate('Your Contact address details has been updated successfully!'))->success();
        return back();
    }

     /**
     * Edit user contact address
     * @return \Illuminate\Http\Response
     */
    public function editUserContactAddress()
    {
        // Get User address details
        $addressDetails = getUserAddressDetails();
        $data['address_data'] = $addressDetails;
        //echo "<pre>";print_r($data['address_data']);die;
        $data['states'] = State::where('status', 1)->where('country_id', $data['address_data']['country_id'])->get();
        $data['cities'] = City::where('status', 1)->where('state_id', $data['address_data']['state_id'])->get();

        $returnHTML = view('frontend.partials.user_address_edit_modal', $data)->render();
        return response()->json(array('data' => $data, 'html'=>$returnHTML));
    }

     /**
     * Franchisee registration request form
     * @return \Illuminate\Http\Response
     */
    public function franchiseeRegistrationForm()
    {
        if(auth()->user()->user_type != 'partner' || auth()->user()->status == 1){
            flash(translate('You do not have access to this page. Understood?'))->error();
            return redirect()->route('dashboard');
        }
        return view('frontend.franchisee-registration-form');
    }

     /**
     * Franchisee registration request with add their documents
     * @return \Illuminate\Http\Response
     */
    public function franchiseeRegistrationRequest(Request $request)
    {

        $request->validate([
            'total_experience'    => 'required|numeric',
            'franchisee_id_proof' => 'required|mimes:pdf,png,jpg',
            'franchisee_pan_card' => 'required|mimes:pdf,png,jpg',
        ]);

        try {
            $destinationPath = 'public/uploads/franchisee-documents';

            // Upload address id proof
            $file1 = $request->file('franchisee_id_proof');
            $fileName1 = auth()->id() . '_' . rand() .'_'. $file1->getClientOriginalName();
            $file1->move($destinationPath,$fileName1);

            // Upload pan card
            $file2 = $request->file('franchisee_pan_card');
            $fileName2 = auth()->id() . '_' . rand() .'_'. $file2->getClientOriginalName();
            $file2->move($destinationPath,$fileName2);

            // Update process
            $user = Auth::user();
            $user->total_experience = $request->total_experience;
            $user->franchisee_id_proof = $fileName1;
            $user->franchisee_pan_card = $fileName2;
            $user->franchisee_terms_check = $request->franchisee_terms_check;
            $user->status = 3;  // 3 - registeration request sent status
            $user->save();


            //admin send email

            $state_name = "";
            $state_id = auth()->user()->state;
            if (!empty($state_id)) {
                $state_data = State::select('name')->where('id', $state_id)->first();
                $state_name = !empty($state_data->name) ? $state_data->name : '';
            }
            $city_name = "";
            $city_id = auth()->user()->city;
            if (!empty($city_id)) {
                $city_data = City::select('name')->where('id', $city_id)->first();
                $city_name = !empty($city_data->name) ? $city_data->name : '';
            }
            $user_data = User::select('email')->where('user_type','admin')->first();
            if (!empty($user_data)) {
                $data["email"] = $user_data->email;
                $data["title"] = "Franchisee Registration Details";
                $data["name"]  = auth()->user()->name;
                $data["user_email"]  = auth()->user()->email;
                $data["phone"]  = !empty(auth()->user()->phone) ? auth()->user()->phone : '';
                $data["state"]  = $state_name;
                $data["city"]  = $city_name;
                $data["total_experience"]  = $request->total_experience;

                $files = [
                    $destinationPath.'/'.$fileName1,
                    $destinationPath.'/'.$fileName2,
                ];
                \Mail::send('emails.franchisee-registration-form',['data' => $data], function($message)use($data, $files) {
                    $message->to($data["email"], $data["email"])
                            ->subject($data["title"]);
                    foreach ($files as $file){
                        $message->attach($file);
                    }
                });
            }

            flash(translate('Your registration request sent successfully please wait for admin Approval'))->success();
            return redirect()->route('franchisee-registration-form');

        }catch (\Exception $e) {
            dd($e->getMessage());
        }
    }


}
