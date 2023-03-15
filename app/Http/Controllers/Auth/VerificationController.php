<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\OTPVerificationController;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Show the email verification notice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if ($request->user()->email != null) {
            $data = array('email' =>$request->user()->email );
            return $request->user()->hasVerifiedEmail()
                            ? redirect($this->redirectPath())
                            : view('auth.verify',compact('data'));
        }
        else {
            $otpController = new OTPVerificationController;
            $otpController->send_code($request->user());
            return redirect()->route('verification');
        }
    }


    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resend(Request $request)
    {

        $user = User::where('email', $request->email)->first();
        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }
        $data = array('email' => $user->email);
        $user->sendEmailVerificationNotification();
        flash(translate('A fresh verification link has been sent to your email address.'))->success();
        return view('auth.verify',compact('data'));
        //return back()->with('resent', true);
    }

    public function verification_confirmation($code){
        $user = User::where('verification_code', $code)->first();

        if($user != null){
            if(!empty($user->email_verified_at)){
                if ($user->status == 0 && $user->email_verified_at != '') {
                    if($user->user_type == 'partner'){
                        auth()->login($user, true);
                        flash(translate('Your email has been verified successfully'))->success();
                        return redirect()->route('franchisee-registration-form');
                    }else{
                        auth()->logout();
                        flash(translate('Your email has been already verified successfully please wait for admin Approval'))->error();
                        return back();
                    }
                    
                }
                flash(translate('Email already Verified'))->error();
            }else{
                $user->email_verified_at = Carbon::now();
                $user->save();

                // update conflict resolution by sampath on 18-Jan-2023
                // github commit number 17bb4df raj_dev

                // auth()->login($user, true);
                // flash(translate('Your email has been verified successfully'))->success();

                // Check partner email verify and redirects to franchisee register form
                if($user->status == 0 && $user->user_type == "partner"){
                    auth()->login($user, true);
                    flash(translate('Your email has been verified successfully'))->success();
                    return redirect()->route('franchisee-registration-form');
                }

                if ($user->status == 0) {
                    auth()->logout();
                    flash(translate('Your email has been verified successfully please wait for admin Approval'))->success();
                    return redirect()->route('logout');
                }
                else{
                    auth()->login($user, true);
                    flash(translate('Your email has been verified successfully'))->success();
                }
                if($user->user_type == 'seller') {
                    return redirect()->route('seller.dashboard');
                }
            }
        }
        else {
            // flash(translate('Sorry, we could not verifiy you. Please try again'))->error();
            flash(translate('This email is expire. Please try again'))->error();
        }

        // if(!empty($user) && $user->user_type == 'seller') {
        //     return redirect()->route('seller.dashboard');
        // }

        return redirect()->route('profile');
    }
}
