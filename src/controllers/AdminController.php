<?php namespace crocodicstudio\crudbooster\controllers;

use crocodicstudio\crudbooster\controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\PasswordHistory;
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
	    
		return view('crudbooster::main');
	}

	public function getLogin()
	{
		$whitelistIP = ['211.25.211.2','121.123.162.90','210.19.137.50','121.122.44.126','210.19.32.54','210.19.164.146','96.9.161.226','211.25.211.154', '211.25.211.2', '103.118.20.198','14.140.116.135','14.140.116.145','14.140.116.156','59.144.18.118', '103.118.21.114','211.24.79.202','192.168.10.1', '111.223.97.240','111.223.97.242','2001:d08:d8:4148:24f8:4a3b:5549:e41d', '121.122.106.200', '121.122.86.227', '103.118.21.118','10.0.2.2', '121.122.71.16', '121.122.86.66','127.0.0.1', '103.118.20.58', '103.118.21.106', '121.122.106.85', '115.135.52.191', '219.74.70.52'];


		$ilumaIPs = ['111.223.97.241',
					'111.223.97.242',
					'111.223.97.243',
					'111.223.97.244',
					'111.223.97.245',
					'111.223.97.246',
					'111.223.97.247',
					'111.223.97.248',
					'111.223.97.249',
					'111.223.97.250',
					'111.223.97.251',
					'111.223.97.252',
					'111.223.97.253',
					'111.223.97.254'
				];

	    foreach($ilumaIPs as $ilumaIP)
	    {
	    	array_push($whitelistIP, $ilumaIP);
	    }

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

 		if(array_search($ip, $whitelistIP) === false){
			return redirect()->route('AdminControllerGetHome');
		} else {
			return view('crudbooster::login');
		}
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
				return redirect()->route('AdminControllerGetIndex');
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

	public function getForgot() {
		return view('crudbooster::forgot');
	}

	public function postForgot() {
		$validator = Validator::make(Request::all(),
			[
			'email'=>'required|email|exists:'.config('crudbooster.USER_TABLE')
			]
		);

		if ($validator->fails())
		{
			$message = $validator->errors()->all();
			return redirect()->back()->with(['message'=>implode(', ',$message),'message_type'=>'danger']);
		}

		$rand_string = str_random(5);
		$password = \Hash::make($rand_string);

		DB::table(config('crudbooster.USER_TABLE'))->where('email',Request::input('email'))->update(array('password'=>$password));

		$appname = CRUDBooster::getSetting('appname');
		$user = CRUDBooster::first(config('crudbooster.USER_TABLE'),['email'=>g('email')]);
		$user->password = $rand_string;
		CRUDBooster::sendEmail(['to'=>$user->email,'data'=>$user,'template'=>'forgot_password_backend']);

		CRUDBooster::insertLog(trans("crudbooster.log_forgot",['email'=>g('email'),'ip'=>Request::server('REMOTE_ADDR')]));

		return redirect()->route('getLogin')->with('message', trans("crudbooster.message_forgot_password"));
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

	public function getLogout() {

		$me = CRUDBooster::me();
		CRUDBooster::insertLog(trans("crudbooster.log_logout",['email'=>$me->email]));

		Schema::dropIfExists('gigyacustomer');

		Session::flush();
		return redirect()->route('getLogin')->with('message',trans("crudbooster.message_after_logout"));
	}

}
