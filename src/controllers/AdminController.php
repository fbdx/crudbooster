<?php namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Adldap;

class AdminController extends CBController
{
    function getIndex()
    {
        $data = [];
        $data['page_title'] = '<strong>Dashboard</strong>';

        return view('crudbooster::home', $data);
    }

    public function getLockscreen()
    {

        if (! CRUDBooster::myId()) {
            Session::flush();

            return redirect()->route('getLogin')->with('message', trans('crudbooster.alert_session_expired'));
        }

        Session::put('admin_lock', 1);

        return view('crudbooster::lockscreen');
    }

    public function postUnlockScreen()
    {
        $id = CRUDBooster::myId();
        $password = Request::input('password');
        $users = DB::table(config('crudbooster.USER_TABLE'))->where('id', $id)->first();

        if (\Hash::check($password, $users->password)) {
            Session::put('admin_lock', 0);

            return redirect(CRUDBooster::adminPath());
        } else {
            echo "<script>alert('".trans('crudbooster.alert_password_wrong')."');history.go(-1);</script>";
        }
    }

    public function getLogin()
    {

        if (CRUDBooster::myId()) {
            return redirect(CRUDBooster::adminPath());
        }

        return view('crudbooster::login');
    }

    public function postLogin()
    {
        $isldap = config("crudbooster.LDAP_AUTH");

        $email = Request::input("email");
        $password = Request::input("password");

        if (($isldap=='BOTH')||($isldap=='YES'))
        {
            $ad = new Adldap\Adldap();    
            $config = [
                // Mandatory Configuration Options
                'hosts'            => explode(' ', config("crudbooster.LDAP_HOST")),
				'use_ssl'          => true,
				'port' => 636,
				'custom_options'   => [
					// See: http://php.net/ldap_set_option
					LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER
				]
            ];

            $ad->addProvider($config);            

            try {
                
                $provider = $ad->connect();
                try {                                       
                    if ($provider->auth()->attempt(config("crudbooster.LDAP_DOMAIN")."\\".$email, $password)) {                        
                        $users = DB::table(config('crudbooster.USER_TABLE'))->where("name", $email)->first();
                        if ($users!=null)
                        {
                             $priv = DB::table("cms_privileges")->where("id", $users->id_cms_privileges)->first();

                            $roles = DB::table('cms_privileges_roles')->where('id_cms_privileges', $users->id_cms_privileges)->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')->get();

                            $photo = ($users->photo) ? asset($users->photo) : asset('vendor/crudbooster/avatar.jpg');
                            Session::put('admin_id', $users->id);
                            Session::put('admin_is_superadmin', $priv->is_superadmin);
                            Session::put('admin_name', $users->name);
                            Session::put('admin_photo', $photo);
                            Session::put('admin_privileges_roles', $roles);
                            Session::put("admin_privileges", $users->id_cms_privileges);
                            Session::put('admin_privileges_name', $priv->name);
                            Session::put('admin_lock', 0);
                            Session::put('theme_color', $priv->theme_color);
                            Session::put("appname", CRUDBooster::getSetting('appname'));

                            CRUDBooster::insertLog(trans("crudbooster.log_login", ['email' => $users->email, 'ip' => Request::server('REMOTE_ADDR')]));

                            $cb_hook_session = new \App\Http\Controllers\CBHook;
                            $cb_hook_session->afterLogin();
                        }
                        else
                        {
                            if ($isldap=='YES')
                                return redirect()->route('getLogin')->with('message', "Failed authentication");
                            //echo "Failed authentication<br>";
                        }

                    } else {
                        // Failed.
                        if ($isldap=='YES')
                            return redirect()->route('getLogin')->with('message', "Failed authentication");
                    }
                } catch (Adldap\Auth\UsernameRequiredException $e) {
                    if ($isldap=='YES')
                            return redirect()->route('getLogin')->with('message', "Failed authentication");
                } catch (Adldap\Auth\PasswordRequiredException $e) {
                    if ($isldap=='YES')
                            return redirect()->route('getLogin')->with('message', "Failed authentication");
                }
            } catch (\BindException $e) {
                if ($isldap=='YES')
                   return redirect()->route('getLogin')->with('message', "Error connecting to authentication server, please contact admin");
            }

        }

        if (($isldap!='YES'))
        {
            $validator = Validator::make(Request::all(), [
                'email' => 'required|email|exists:'.config('crudbooster.USER_TABLE'),
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $validator->errors()->all();

                return redirect()->route('getLogin')->with('message', trans('crudbooster.alert_password_wrong'));
            }

            //$email = Request::input("email");
            //$password = Request::input("password");
            $users = DB::table(config('crudbooster.USER_TABLE'))->where("email", $email)->first();

            if (\Hash::check($password, $users->password)) {
                $priv = DB::table("cms_privileges")->where("id", $users->id_cms_privileges)->first();

                $roles = DB::table('cms_privileges_roles')->where('id_cms_privileges', $users->id_cms_privileges)->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')->get();

                $photo = ($users->photo) ? asset($users->photo) : asset('vendor/crudbooster/avatar.jpg');
                Session::put('admin_id', $users->id);
                Session::put('admin_is_superadmin', $priv->is_superadmin);
                Session::put('admin_name', $users->name);
                Session::put('admin_photo', $photo);
                Session::put('admin_privileges_roles', $roles);
                Session::put("admin_privileges", $users->id_cms_privileges);
                Session::put('admin_privileges_name', $priv->name);
                Session::put('admin_lock', 0);
                Session::put('theme_color', $priv->theme_color);
                Session::put("appname", CRUDBooster::getSetting('appname'));

                CRUDBooster::insertLog(trans("crudbooster.log_login", ['email' => $users->email, 'ip' => Request::server('REMOTE_ADDR')]));

                $cb_hook_session = new \App\Http\Controllers\CBHook;
                $cb_hook_session->afterLogin();

                return redirect(CRUDBooster::adminPath());
            } else {
                return redirect()->route('getLogin')->with('message', trans('crudbooster.alert_password_wrong'));
            }
        }
    }

    public function getForgot()
    {
        if (CRUDBooster::myId()) {
            return redirect(CRUDBooster::adminPath());
        }

        return view('crudbooster::forgot');
    }

    public function postForgot()
    {
        $validator = Validator::make(Request::all(), [
            'email' => 'required|email|exists:'.config('crudbooster.USER_TABLE'),
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->all();

            return redirect()->back()->with(['message' => implode(', ', $message), 'message_type' => 'danger']);
        }

        $rand_string = str_random(5);
        $password = \Hash::make($rand_string);

        DB::table(config('crudbooster.USER_TABLE'))->where('email', Request::input('email'))->update(['password' => $password]);

        $appname = CRUDBooster::getSetting('appname');
        $user = CRUDBooster::first(config('crudbooster.USER_TABLE'), ['email' => g('email')]);
        $user->password = $rand_string;
        CRUDBooster::sendEmail(['to' => $user->email, 'data' => $user, 'template' => 'forgot_password_backend']);

        CRUDBooster::insertLog(trans("crudbooster.log_forgot", ['email' => g('email'), 'ip' => Request::server('REMOTE_ADDR')]));

        return redirect()->route('getLogin')->with('message', trans("crudbooster.message_forgot_password"));
    }

    public function getLogout()
    {

        $me = CRUDBooster::me();
        CRUDBooster::insertLog(trans("crudbooster.log_logout", ['email' => $me->email]));

        Session::flush();

        return redirect()->route('getLogin')->with('message', trans("crudbooster.message_after_logout"));
    }
}
