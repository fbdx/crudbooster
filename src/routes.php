<?php

/* ROUTER FOR API GENERATOR */
$namespace = '\crocodicstudio\crudbooster\controllers';

Route::group(['middleware'=>['api','\crocodicstudio\crudbooster\middlewares\CBAuthAPI'],'namespace'=>'App\Http\Controllers'], function () {
	//Router for custom api defeault	

	$dir       = scandir(base_path("app/Http/Controllers"));		
	foreach($dir as $v) {		
		$v     = str_replace('.php','',$v);
		$names = array_filter(preg_split('/(?=[A-Z])/',str_replace('Controller','',$v)));		
		$names = strtolower(implode('_',$names));	
		
		if(substr($names,0,4)=='api_') {	
			$names = str_replace('api_','',$names);	
			Route::any('api/'.$names,$v.'@execute_api');
		}
	}

});

/* ROUTER FOR UPLOADS */
Route::group(['middleware'=>['web'],'namespace'=>$namespace],function() {		
	Route::get('api-documentation', ['uses'=>'ApiCustomController@apiDocumentation','as'=>'apiDocumentation']);	
	Route::get('download-documentation-postman', ['uses'=>'ApiCustomController@getDownloadPostman','as'=>'downloadDocumentationPostman']);		
	Route::get('thumbnail/{folder}/{filename}', ['uses'=>'ThumbnailController@getFile','as'=>'thumbnailController']);		
});

/* ROUTER FOR WEB */
Route::group(['middleware'=>['web'],'prefix'=>config('crudbooster.ADMIN_PATH'),'namespace'=>$namespace], function () {
		
	Route::get('home', ['uses'=>'AdminController@getHome','as'=>'home']);
	Route::post('unlock-screen', ['uses'=>'AdminController@postUnlockScreen','as'=>'postUnlockScreen']);
	Route::get('lock-screen', ['uses'=>'AdminController@getLockscreen','as'=>'getLockScreen']);	
	Route::post('forgot',['uses'=>'AdminController@postForgot','as'=>'postForgot']);
	Route::get('forgot',['uses'=>'AdminController@getForgot','as'=>'getForgot']);
	Route::post('register', ['uses'=>'AdminController@postRegister','as'=>'postRegister']);
	Route::get('register', ['uses'=>'AdminController@getRegister','as'=>'getRegister']);
	Route::get('logout', ['uses'=>'AdminController@getLogout','as'=>'getLogout']);				
	Route::get('login/google', ['uses'=>'AdminController@redirectToProvider', 'as'=>'googleLogin']);	
	Route::get('login/callback', ['uses'=>'AdminController@handleProviderCallback', 'as'=>'googleCallback']);
	Route::get('login', ['uses'=>'AdminController@getLogin','as'=>'getLogin']);	
	Route::post('login', ['uses'=>'AdminController@postLogin','as'=>'postLogin']);
	Route::get('change-password', ['uses' => 'AdminController@getChangePassword', 'as' => 'getChangePassword']);
	Route::post('change-password', ['uses' => 'AdminController@postChangePassword', 'as' => 'postChangePassword']);
	Route::get('/2fa/validate/{secret}/user/{email}', ['uses' => 'AdminController@getValidateToken', 'as' => 'getValidateToken']);
	Route::post('/2fa/validate', ['uses' => 'AdminController@postValidateToken', 'as' => 'postValidateToken']);
});

// ROUTER FOR OWN CONTROLLER FROM CB
Route::group(['middleware'=>['web','\crocodicstudio\crudbooster\middlewares\CBBackend'],'prefix'=>config('crudbooster.ADMIN_PATH'),'namespace'=>'App\Http\Controllers'], function () {
				
		try {
			$moduls = DB::table('cms_moduls')
			->where('path','!=','')
			->where('controller','!=','')
			->where('is_protected',0)->get();			
			foreach($moduls as $v) {						
				CRUDBooster::routeController($v->path,$v->controller);						
			}			
		} catch (Exception $e) {
			
		}			
});


/* ROUTER FOR BACKEND CRUDBOOSTER */
Route::group(['middleware'=>['web','\crocodicstudio\crudbooster\middlewares\CBBackend'],'prefix'=>config('crudbooster.ADMIN_PATH'),'namespace'=>$namespace], function () {

	/* DO NOT EDIT THESE BELLOW LINES */
	CRUDBooster::routeController('/','AdminController',$namespace='\crocodicstudio\crudbooster\controllers');	
	CRUDBooster::routeController('api_generator','ApiCustomController',$namespace='\crocodicstudio\crudbooster\controllers');	
	
	try{
		$master_controller = glob(__DIR__.'/controllers/*.php');
		foreach($master_controller as &$m) $m = str_replace('.php','',basename($m));		
		
		$moduls = DB::table('cms_moduls')->whereIn('controller',$master_controller)->get();	
		
		foreach($moduls as $v) {
			if(@$v->path && @$v->controller) {		
				CRUDBooster::routeController($v->path,$v->controller,$namespace='\crocodicstudio\crudbooster\controllers');
			}
		}
	}catch(Exception $e) {
		
	}	
});

