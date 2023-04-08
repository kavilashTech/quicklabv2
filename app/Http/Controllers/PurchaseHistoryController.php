<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\InputBag;

class PurchaseHistoryController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$orders = Order::whereHas('orderDetails', function ($query) {
			$query->where('id', '>', 0);
		})->with('orderDetails')->with('orderDetails.product')
			->where('user_id', Auth::user()->id);
		$orders = $orders->orderBy('code', 'desc');
		$orders = $orders->paginate(9);
		//dd($orders);
		return view('frontend.user.purchase_history', compact('orders'));
	}

	public function digital_index()
	{
		$orders = DB::table('orders')
			->orderBy('code', 'desc')
			->join('order_details', 'orders.id', '=', 'order_details.order_id')
			->join('products', 'order_details.product_id', '=', 'products.id')
			->where('orders.user_id', Auth::user()->id)
			->where('products.digital', '1')
			->where('order_details.payment_status', 'paid')
			->select('order_details.id')
			->paginate(15);
		return view('frontend.user.digital_purchase_history', compact('orders'));
	}

	public function purchase_history_details($id)
	{
		$order = Order::findOrFail(decrypt($id));
		$orders = Order::where('id', decrypt($id))->with('orderDetails.product')->get();
		$array = array();
		//dd($orders[0]->orderDetails);
		foreach ($orders[0]->orderDetails as  $value) {
			//dd($value->product);
			// dd($value->product->returnable == 1);
			if ($value->product->returnable == 1) {
				array_push($array, $value->product->id);
			}
		}
		//dd($array);
		$order->delivery_viewed = 1;
		$order->payment_status_viewed = 1;
		$order->save();
		return view('frontend.user.order_details_customer', compact('order', 'array'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function order_cancel($id)
	{
		$order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();
		if ($order && ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')) {
			$order->delivery_status = 'cancelled';
			$order->save();

			flash(translate('Order has been canceled successfully'))->success();
		} else {
			flash(translate('Something went wrong'))->error();
		}

		return back();
	}

	public function purchase_history_franchise_index(Request $request)
	{
		$search = "";
		$start_date = "";
		$end_date = "";

		$orders = Order::select('orders.*', 'users.id as users_id', 'users.name as user_name')
			->whereHas('orderDetails', function ($query) {
				$query->where('id', '>', 0);
			});
		$orders = $orders->leftJoin('users', 'orders.user_id', '=', 'users.id');
		$orders = $orders->where('orders.franchisee_id', Auth::user()->id);
		if (!empty($request->search)) {
			$orders = $orders->where('users.name', 'like', '%' . $request->search . '%');
			$search = $request->search;
		}

		if (!empty($request->start_date) && !empty($request->end_date)) {
			$orders = $orders->whereBetween('orders.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

			$start_date = $request->start_date;
			$end_date = $request->end_date;
		} else {
			$this_month_first_date = date('Y-m-01');
			$today_date = date("Y-m-d");

			$orders = $orders->whereBetween('orders.created_at', [$this_month_first_date . ' 00:00:00', $today_date . ' 23:59:59']);
			$start_date = $this_month_first_date;
			$end_date = $today_date;
		}
		$orders = $orders->orderBy('orders.user_id', 'desc');
		$orders = $orders->orderBy('orders.code', 'desc');
		$orders = $orders->paginate(9);

		return view('frontend.user.purchase_franchise_history', compact('orders', 'search', 'start_date', 'end_date'));
	}


	public function purchase_history_franchise_details($id)
	{
		$order = Order::findOrFail(decrypt($id));
		$order->delivery_viewed = 1;
		$order->payment_status_viewed = 1;
		$order->save();
		return view('frontend.user.order_details_customer', compact('order'));
	}

	public function order_cancel_franchise($id)
	{
		$order = Order::where('id', $id)->first();
		if ($order && ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')) {
			$order->delivery_status = 'cancelled';
			$order->save();

			flash(translate('Order has been canceled successfully'))->success();
		} else {
			flash(translate('Something went wrong'))->error();
		}

		return back();
	}

	public function downloadPurchaseHistory()
	{
		$headers = [
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',   'Content-type'        => 'text/csv',   'Content-Disposition' => 'attachment; filename=' . Auth::user()->name . '_' . date("dmY") . '.csv',   'Expires'             => '0',   'Pragma'              => 'public'
		];

		$list = Order::all()->toArray();
		# add headers for each column in the CSV download
		array_unshift($list, array_keys($list[0]));

		$callback = function () use ($list) {
			$FH = fopen('php://output', 'w');
			foreach ($list as $row) {
				fputcsv($FH, $row);
			}
			fclose($FH);
		};

		return response()->stream($callback, 200, $headers);
	}

	public function returnOrder($id)
	{
		// dd($id);
		// $order = Order::find($id);
		$order = OrderDetail::where('id', $id)->with('product')->with('order')->get();


		return view('frontend.user.return_product', compact('order'));
	}
	public function return(Request $request)
	{

		$seller_id = $request->input('seller_id');
		$order_id = $request->input('order_id');
		$product_id = $request->input('product_id');
		$order  = OrderDetail::find($order_id);
		$order_date = $order->created_at;
		//dd($request->return_reason);
		$now = Carbon::now();
		//dd($now);
		$days_count = $order_date->diffInDays($now);
		//dd($days_count);
		$product = Product::find($product_id);
		//dd($product->returnable_days >= $days_count);
		if ($product->returnable_days >= $days_count) {
			$return = OrderDetail::where('id', $order_id)->update([
				'delivery_status' => "Pending Approval",
				'return_reason' => $request->return_reason,
				'return_request_date' => $now->format('d/m/Y')
			]);

			return redirect('/purchase_history')->with('message-sucess', "Your Return Request is Process.");
		} else {
			return redirect('/purchase_history')->with('message-return', "Sorry We Can't Proceed your request Maximum Time Reached!");
		}
	}
}
