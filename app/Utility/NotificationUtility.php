<?php

namespace App\Utility;

use App\Mail\InvoiceEmailManager;
use App\Models\User;
use App\Models\SmsTemplate;
use App\Http\Controllers\OTPVerificationController;
use Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderNotification;
use App\Models\FirebaseNotification;

class NotificationUtility
{

    public static function sendFranchiseedNotification($user, $request = null)
    {
        $status = ($user->user_type == 'partner' ? 'Franchisee' :'Customer' );

            $users = User::findMany([$user->id, \App\Models\User::where('user_type', 'admin')->first()->id]);


        $order_notification = array();
        $order_notification['order_id'] = $user->name;
        $order_notification['order_code'] = $user->email;
        $order_notification['user_id'] = $user->user_id;
        $order_notification['seller_id'] = $user->seller_id;
        $order_notification['status'] = $status;

        Notification::send($users, new OrderNotification($order_notification));

    }

    public static function sendOrderPlacedNotification($order, $request = null)
    {
        //sends email to customer with the invoice pdf attached
        $orderOtherDetails = [];
        //TODO - 0000248 ticket - Invoice template related changes
        $taxAvailable = checkAuthUserAddress();
        $orderOtherDetails['taxAvailable'] = $taxAvailable;
        $addressDetails = getAddressDetails($order->address_id);
        //Updated by Sampath on 21-Jan-2023 - code from invoicecontroller.php - based on fix done by Arun Ticket No.248 related
        $orderOtherDetails['state_id'] = (!empty($addressDetails) && !empty($addressDetails->state_id)) ? $addressDetails->state_id : "";


        // $array['view'] = 'emails.invoice'; //Old invoice template view
        $array['view'] = 'backend.invoices.invoice'; //New Invoice template view

        $array['subject'] = translate('A new order has been placed') . ' - ' . $order->code;
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['order'] = $order;
        $array['orderOtherDetails'] = $orderOtherDetails;
        $array['body'] = "<html><h4>Your order placed successfully</h4></html>";
       
        try {
            Mail::to($order->user->email)->queue(new InvoiceEmailManager($array));
            Mail::to($order->orderDetails->first()->product->user->email)->queue(new InvoiceEmailManager($array));
        } catch (\Exception $e) {

        }

        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'order_placement')->first()->status == 1) {
            try {
                $otpController = new OTPVerificationController;
                $otpController->send_order_code($order);
            } catch (\Exception $e) {

            }
        }

        //sends Notifications to user
        self::sendNotification($order, 'placed');
        if ($request !=null && get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order placed !";
            $request->text = "An order {$order->code} has been placed";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            self::sendFirebaseNotification($request);
        }
    }

    public static function sendNotification($order, $order_status)
    {
        if ($order->seller_id == \App\Models\User::where('user_type', 'admin')->first()->id) {
            $users = User::findMany([$order->user->id, $order->seller_id]);
        } else {
            $users = User::findMany([$order->user->id, $order->seller_id, \App\Models\User::where('user_type', 'admin')->first()->id]);
        }

        $order_notification = array();
        $order_notification['order_id'] = $order->id;
        $order_notification['order_code'] = $order->code;
        $order_notification['user_id'] = $order->user_id;
        $order_notification['seller_id'] = $order->seller_id;
        $order_notification['status'] = $order_status;

        Notification::send($users, new OrderNotification($order_notification));
    }

    public static function sendFirebaseNotification($req)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $fields = array
        (
            'to' => $req->device_token,
            'notification' => [
                'body' => $req->text,
                'title' => $req->title,
                'sound' => 'default' /*Default sound*/
            ],
            'data' => [
                'item_type' => $req->type,
                'item_type_id' => $req->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        );

        //$fields = json_encode($arrayToSend);
        $headers = array(
            'Authorization: key=' . env('FCM_SERVER_KEY'),
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        curl_close($ch);

        $firebase_notification = new FirebaseNotification;
        $firebase_notification->title = $req->title;
        $firebase_notification->text = $req->text;
        $firebase_notification->item_type = $req->type;
        $firebase_notification->item_type_id = $req->id;
        $firebase_notification->receiver_id = $req->user_id;

        $firebase_notification->save();
    }
}
