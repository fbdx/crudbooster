<?php 

\Validator::extend('alpha_spaces', function ($attribute, $value) {
    // This will only accept alpha and spaces. 
    // If you want to accept hyphens use: /^[\pL\s-]+$/u.
    return preg_match('/^[\pL\s]+$/u', $value); 
},'The :attribute should be letters only');

\Validator::extend('passwordregex', function ($attribute, $value) {
    // This will only accept alpha and spaces. 
    // If you want to accept hyphens use: /^[\pL\s-]+$/u.
    return preg_match('/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8,}$/', $value); 
},'The :attribute should have an uppercase, lowercase, a number and a symbol');

\Validator::extend('passwordhistory', function ($attribute, $value, $parameters) {
	$userpass = DB::table('password_histories')->where('user_id', $parameters[0])->orderBy('id', 'desc')->limit(5)->pluck('password');	
	
	foreach ($userpass as $name => $pass) {
		if (Hash::check($value, $pass)) {
			return false;
		}		
	}
			
	return true;
    // This will only accept alpha and spaces. 
    // If you want to accept hyphens use: /^[\pL\s-]+$/u.    
},'The :attribute was used as a password before, please use a password that wasn\'t used 5 times ago');

?>