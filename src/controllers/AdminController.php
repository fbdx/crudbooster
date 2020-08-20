<?php namespace crocodicstudio\crudbooster\controllers;

use crocodicstudio\crudbooster\controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\PasswordHistory;
use App\Mail\ForgotPassword;
use Illuminate\Support\Facades\Mail;
use Socialite;
use CRUDBooster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;


class AdminController extends CBController {

	function getIndex() {

		$dashboard = CRUDBooster::sidebarDashboard();
		if($dashboard && $dashboard->url) {
			return redirect($dashboard->url);
		}

		$data = array();
		$data['page_title']       = '<strong>Dashboard</strong>';
		$data['page_menu']        = Route::getCurrentRoute()->getActionName();
		return view('crudbooster::home',$data);
	}

	public function getLockscreen() {

		if(!CRUDBooster::myId()) {
			Session::flush();
			return redirect()->route('getLogin')->with('message',trans('crudbooster.alert_session_expired'));
		}

		Session::put('admin_lock',1);
		return view('crudbooster::lockscreen');
	}

	public function postUnlockScreen() {
		$id       = CRUDBooster::myId();
		$password = Request::input('password');
		$users    = DB::table(config('crudbooster.USER_TABLE'))->where('id',$id)->first();

		if(\Hash::check($password,$users->password)) {
			Session::put('admin_lock',0);
			return redirect()->route('AdminControllerGetIndex');
		}else{
			echo "<script>alert('".trans('crudbooster.alert_password_wrong')."');history.go(-1);</script>";
		}
	}

	public function getHome()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	    {
	      $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	    {
	      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    else
	    {
	      $ip=$_SERVER['REMOTE_ADDR'];
	    }

	    // dump($ip);
	    
		return view('crudbooster::blank');
	}

	public function getLogin()
	{
		// $whitelistIPs = DB::table('whitelist_ips')->select('ip_address')->get();

		// if(count($whitelistIPs) > 0)
		// {
		// 	$whitelistIPList= [];

		// 	foreach($whitelistIPs as $key => $value)
		// 	{
		// 		$whitelistIPList[] = $value->ip_address;
		// 	}

	 // 		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		//     {
		//       $ip=$_SERVER['HTTP_CLIENT_IP'];
		//     }
		//     elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		//     {
		//       $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		//     }
		//     else
		//     {
		//       $ip=$_SERVER['REMOTE_ADDR'];
		//     }

		//     if($stringCut = strpos($ip, ":"))
		//     {
		//     	$ip = substr($ip, 0, $stringCut);
		//     }

	 // 		if(array_search($ip, $whitelistIPList) === false){
		// 		return redirect()->route('AdminControllerGetHome');
		// 	} else {
				return view('crudbooster::login');
		// 	}
		// }
		// else
		// {
		// 	return view('crudbooster::login');
		// }
	}

	public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback()
    {
		$googleUser = Socialite::driver('google')->stateless()->user();

		$cmsUser = DB::table(config('crudbooster.USER_TABLE'))->where("email",$googleUser->email)->first();

		if($cmsUser)
		{
			$success = $this->saveIntoSessionAndRedirect($cmsUser);

			if($success)
			{
				return redirect()->route('AdminControllerGetIndex');
			}
		}
		else
		{
			return view('crudbooster::login');
		}
    }

	public function postLogin() {

		$validator = Validator::make(Request::all(),
			[
			'email'=>'required|email|exists:'.config('crudbooster.USER_TABLE'),
			'password'=>'required'
			]
		);

		if ($validator->fails())
		{
			$message = $validator->errors()->all();
			return redirect()->back()->with(['message'=>implode(', ',$message),'message_type'=>'danger']);
		}

		$email 		= Request::input("email");
		$password 	= Request::input("password");
		$user 		= DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->first();

		if($user->status == 'Locked')
		{
			return redirect()->route('getLogin')->with('message', 'This account has been locked. Please contact the admin to unlock it.');
		}

		if(\Hash::check($password,$user->password)) {

			if(isset($user->password_updated_at))
			{
				$now              = Carbon::now();
				$passwordExpiryAt = Carbon::parse($user->password_updated_at)->addDays($user->password_expiry_days);

				if($passwordExpiryAt < $now)
				{
				    Session::flush();
			        return redirect()->route('getChangePassword')->with('message','Your Password has expired. Please change it.');
			    }
			}

			$success = $this->saveIntoSessionAndRedirect($user);

			if($success)
			{
				if($user->enable_google2fa)
				{
					if(isset($user->google2fa_secret))
					{
						$data['secret'] = $user->google2fa_secret;
				        $data['email']  = $user->email;

				        return view('crudbooster::2fa/validate', $data);
					}
					
					$data = $this->generateMultiFactorAuthenticationQRCode($user);

					return view('crudbooster::2fa/enableTwoFactor', $data);
				}
				else
				{
					return redirect()->route('AdminControllerGetIndex');
				}
			}

		}else{

			$loginAttempts = $user->failed_login_attempts;
			$loginCount    = $loginAttempts + 1;

			DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->update(['failed_login_attempts' => $loginCount]);

			if($loginCount >= 3)
			{
				DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->update(['status' => 'Locked']);
			}

			return redirect()->route('getLogin')->with('message', trans('crudbooster.alert_password_wrong'));
		}
	}

	public function saveIntoSessionAndRedirect($user)
	{
		$success = false;

		$priv = DB::table("cms_privileges")->where("id",$user->id_cms_privileges)->first();

		$roles = DB::table('cms_privileges_roles')
		->where('id_cms_privileges',$user->id_cms_privileges)
		->join('cms_moduls','cms_moduls.id','=','id_cms_moduls')
		->select('cms_moduls.name','cms_moduls.path','is_visible','is_create','is_read','is_edit','is_delete')
		->get();

		$photo = ($user->photo)?asset($user->photo):'https://www.gravatar.com/avatar/'.md5($user->email).'?s=100';

		try{

			Session::put('admin_id',$user->id);
			Session::put('admin_is_superadmin',$priv->is_superadmin);
			Session::put('admin_name',$user->name);
			Session::put('admin_photo',$photo);
			Session::put('admin_privileges_roles',$roles);
			Session::put("admin_privileges",$user->id_cms_privileges);
			Session::put('admin_privileges_name',$priv->name);
			Session::put('admin_lock',0);
			Session::put('theme_color',$priv->theme_color);
			Session::put("appname",CRUDBooster::getSetting('appname'));

			CRUDBooster::insertLog(trans("crudbooster.log_login",['email'=>$user->email,'ip'=>Request::server('REMOTE_ADDR')]));

			$cb_hook_session = new \App\Http\Controllers\CBHook;
			$cb_hook_session->afterLogin();

			$success = true;

		} catch(\Exception $e) {
			echo 'Message: ' .$e->getMessage();
		}

		return $success;
	}

	public function generateMultiFactorAuthenticationQRCode($user)
	{
		$googleAuthenticator = new \PHPGangsta_GoogleAuthenticator();
		$secret = $googleAuthenticator->createSecret();

		$qrCodeUrl = $googleAuthenticator->getQRCodeGoogleUrl('Nestle_SmartData_MFA', $secret);

		$data['secret'] = $secret;
		$data['qrCodeUrl'] = $qrCodeUrl;
		$data['user'] = $user;

		return $data;
	}

	public function getValidateToken($secret, $email)
    {
       $data['secret'] = $secret;
       $data['email']  = $email;

       return view('crudbooster::2fa/validate', $data);
    }

	public function postValidateToken()
    {
        $input = Request::all();

        $googleAuthenticator = new \PHPGangsta_GoogleAuthenticator();

        $cmsUser = DB::table(config('crudbooster.USER_TABLE'))->where("email",$input['email'])->first();
        $secret  = $input['google2fa_secret'];

        if(!isset($cmsUser->google2fa_secret))
        {
        	$data['google2fa_secret'] = $secret;
			DB::table(config('crudbooster.USER_TABLE'))->where("email",$cmsUser->email)->update($data);
        }

        $oneCode = $googleAuthenticator->getCode($secret);
		
	    if($oneCode == $input['totp'])
	    {
	    	return redirect()->route('AdminControllerGetIndex');
	    }
	    else
	    {
	    	Session::flush();
	    	return redirect()->route('getLogin')->with('message', 'Your One-Time Password is wrong');
	    }
    }

	public function getForgot() {
		return view('crudbooster::forgot');
	}

	public function postForgot() {

		$input = Request::all();
		$email = $input["email"];
		$validator = Validator::make($input,
			[
			'email'=>'required|email|exists:'.config('crudbooster.USER_TABLE')
			]
		);

		if ($validator->fails())
		{
			$message = $validator->errors()->all();
			return redirect()->back()->with(['message'=>implode(', ',$message),'message_type'=>'danger']);
		}

		// $rand_string = str_random(5);
		// $password    = \Hash::make($rand_string);
		// DB::table(config('crudbooster.USER_TABLE'))->where('email',Request::input('email'))->update(array('password'=>$password));

		$appname       = CRUDBooster::getSetting('appname');
		$user          = CRUDBooster::first(config('crudbooster.USER_TABLE'),['email'=>g('email')]);
		$identityToken = $this->generateRandomUID();

		$user->link = route('getResetPassword', ['email' => $email, 'token' => $identityToken]);

        Mail::to($user->email)->send(new ForgotPassword($user));
        DB::table(config('crudbooster.USER_TABLE'))->where('email',Request::input('email'))->update(array('password_reset_identity_token'=>$identityToken, 'password_reset_token' => null));

		CRUDBooster::insertLog(trans("crudbooster.log_forgot",['email'=>g('email'),'ip'=>Request::server('REMOTE_ADDR')]));

		return redirect()->route('getLogin')->with('message', trans("crudbooster.message_forgot_password"));
	}

	public function getResetPassword($email,$identityToken) {

		$user = DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->first();

		if(isset($user))
		{
			if(isset($user->password_reset_identity_token))
			{
				if($identityToken != $user->password_reset_identity_token)
				{
					return redirect()->route('getLogin')->with('message', "This password reset link is invalid.");
				}
				else
				{
					if(isset($user->password_reset_token))
					{
						return redirect()->route('getLogin')->with('message', "Your password has changed. Please login with your new password.");
					}
				}
			}
		}
		else
		{
			return redirect()->route('getLogin')->with('message', "This user doesn't exist.");
		}

		$data["email"] = $email;
		return view('crudbooster::reset_password', $data);
	}

	public function postResetPassword(\Illuminate\Http\Request $request) {

		$input = $request->all();
		$email = $input["email"];
		$passwordResetToken = $this->generateRandomUID();

		$user  = DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->first();

		$validator = Validator::make($input,
			[
				'new-password'         => ['required', 'min:16', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
				'confirm-new-password' => 'required',
			]
		);

		if ($validator->fails())
		{
			$message = $validator->errors()->all();
			return redirect()->back()->with(['message'=>implode(', ',$message),'message_type'=>'danger']);
		}

		if($input['new-password'] != $input['confirm-new-password'] )
		{
			return redirect()->back()->with("message", 'The confirm new password does not match.');
		}

		$passwordHistories = DB::table('password_histories')->where("user_id",$user->id)->get();

	    foreach($passwordHistories as $passwordHistory)
	    {
	        if (\Hash::check($input['new-password'], $passwordHistory->password)) 
	        {
	            return redirect()->back()->with("message","Your new password can not be same as any of your recent passwords. Please choose a new password.");
	        }
	    }

	    $data['password'] = \Hash::make($input['new-password']);
		$data['password_updated_at'] = date('Y-m-d H:i:s');
		$data['password_reset_token'] = $passwordResetToken;

		// dump(config('crudbooster.USER_TABLE'));
		// dd($data);

		DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->update($data);

		$passwordHistory = PasswordHistory::create([
            'user_id'  => $user->id,
            'password' => $data['password']
        ]);

		return redirect()->route('getLogin')->with('message', 'Password reset successfully. You can now login with your new password.');
	}

	public function getChangePassword() {

		$user = DB::table(config('crudbooster.USER_TABLE'))->where("id",$id)->first();

		$data['user'] = (array) $user;

		return view('crudbooster::change', $data);
	}

	public function postChangePassword(\Illuminate\Http\Request $request) {

		$input = $request->all();
		$email = $input['email'];
		$user  = DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->first();

		if(\Hash::check($input['current-password'],$user->password))
		{
			$validator = Validator::make($input, [
			    'email'                => 'required|email|exists:'.config('crudbooster.USER_TABLE'),
			    'current-password'     => 'required',
			    'new-password'         => ['required', 'min:16', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
			    'confirm-new-password' => 'required',
			]);

			if ($validator->fails())
			{
				$message     = $validator->messages();
				$message_all = $message->all();
				
				return redirect()->back()->with("errors",$message)->with(['message'=>trans('crudbooster.alert_validation_error',['error'=>implode(', ',$message_all)]),'message_type'=>'warning'])->withInput();
			}

			if(\Hash::check($input['new-password'],$user->password))
			{
				return redirect()->back()->with("message", 'New Password cannot be same as your current password. Please choose a different password.');
			}

			if($input['new-password'] != $input['confirm-new-password'] )
			{
				return redirect()->back()->with("message", 'The confirm new password does not match.');
			}

			$passwordHistories = DB::table('password_histories')->where("user_id",$user->id)->get();

		    foreach($passwordHistories as $passwordHistory)
		    {
		        if (\Hash::check($input['new-password'], $passwordHistory->password)) 
		        {
		            return redirect()->back()->with("message","Your new password can not be same as any of your recent passwords. Please choose a new password.");
		        }
		    }

			$data['password'] = \Hash::make($input['new-password']);
			$data['password_updated_at'] = date('Y-m-d H:i:s');

			unset($input["_token"]);
			unset($input["new-password"]);
			unset($input["confirm-new-password"]);

			DB::table(config('crudbooster.USER_TABLE'))->where("email",$email)->update($data);

			$passwordHistory = PasswordHistory::create([
	            'user_id'  => $user->id,
	            'password' => $data['password']
	        ]);

	        Session::flush();

			return redirect()->route('getLogin')->with('message','Password changed successfully. You can now login with your new password!');
		}
		else
		{
			return redirect()->back()->with("message", 'Your current password does not matches with the password you provided. Please try again.');
		}

	}

	public function generateRandomUID()
	{
		return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
        	   mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        	   mt_rand(0, 0xffff),
        	   mt_rand(0, 0x0fff) | 0x4000,
        	   mt_rand(0, 0x3fff) | 0x8000,
        	   mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}

	public function getLogout() {

		$me = CRUDBooster::me();
		CRUDBooster::insertLog(trans("crudbooster.log_logout",['email'=>$me->email]));

		Schema::dropIfExists('gigyacustomer');

		Session::flush();
		return redirect()->route('getLogin')->with('message',trans("crudbooster.message_after_logout"));
	}

}
