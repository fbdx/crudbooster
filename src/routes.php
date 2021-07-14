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
	Route::get('reset-password/{email}/token/{token}', ['uses' => 'AdminController@getResetPassword', 'as' => 'getResetPassword']);
	Route::post('reset-password', ['uses' => 'AdminController@postResetPassword', 'as' => 'postResetPassword']);
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

			Route::group(['prefix' => '/v2', 'namespace' => 'Backend'], function(){
				
				Route::get('export-form', 'DataExport\DataExportController@showColumns')->name('backend.export.get-form');
				Route::post('export', 'DataExport\DataExportController@exportData')->name('backend.export.get-data');
				Route::post('wyeth-import', 'WyethImportScript@import')->name('backend.wyeth.import');
				Route::post('export-reports', 'PWAReportsController@export')->name('pwa.export-reports');

				Route::group(['prefix' => '/pwa'], function(){
					// PWA User CRUD
					Route::get('/user', 'PwaController@index')->name('pwa.user.index');
					Route::get('/user/create', 'PwaController@create')->name('pwa.user.create');
					Route::post('/user', 'PwaController@store')->name('pwa.user.store');
					// PWA Specific User
					Route::group(['prefix' => 'user/{user_id}'], function () {
						// PWA User
						Route::get('/', 'PwaController@show')->name('pwa.user.show');
						Route::get('edit', 'PwaController@edit')->name('pwa.user.edit');
						Route::post('/update', 'PwaController@update')->name('pwa.user.update');
						Route::get('/delete', 'PwaController@destroy')->name('pwa.user.destroy');
					});
					
					
				// PWA Recruitment
					Route::get('/recruitment', 'PwaRecruitmentController@index')->name('pwa.recruitment.index');
					Route::get('/recruitment/{recruitment_id}', 'PwaRecruitmentController@show')->name('pwa.recruitment.show');
					Route::get('/recruitment/generatepdf/{recruitment_id}', 'PwaRecruitmentController@generatepdf')->name('pwa.recruitment.generatepdf');
				// PWA Store CRUD
					Route::get('/store', 'PwaStoreController@index')->name('pwa.store.index');
					Route::get('/store/create', 'PwaStoreController@create')->name('pwa.store.create');
					Route::post('/store', 'PwaStoreController@store')->name('pwa.store.store');
					// PWA Specific Store
					Route::group(['prefix' => 'store/{store_id}'], function () {
						// PWA User
						Route::get('/', 'PwaStoreController@show')->name('pwa.store.show');
						Route::get('edit', 'PwaStoreController@edit')->name('pwa.store.edit');
						Route::post('/update', 'PwaStoreController@update')->name('pwa.store.update');
						Route::get('/delete', 'PwaStoreController@destroy')->name('pwa.store.destroy');
						});
					});
				});
				
				Route::group(['prefix' => '/v2', 'namespace' => 'NinBackend'], function(){

					Route::post('nin/export-reports', 'PwaNinReportsController@export')->name('pwa.nin.export-reports');

					Route::group(['prefix' => '/nin-pwa'], function(){
						// PWA User CRUD
						  Route::get('/user', 'PwaNinController@index')->name('pwa.nin.user.index');
						  Route::get('/user/create', 'PwaNinController@create')->name('pwa.nin.user.create');
						  Route::post('/user', 'PwaNinController@store')->name('pwa.nin.user.store');
						  // PWA Specific User
						  Route::group(['prefix' => 'user/{user_id}'], function () {
							  // PWA User
							  Route::get('/', 'PwaNinController@show')->name('pwa.nin.user.show');
							  Route::get('edit', 'PwaNinController@edit')->name('pwa.nin.user.edit');
							  Route::post('/update', 'PwaNinController@update')->name('pwa.nin.user.update');
							  Route::get('/delete', 'PwaNinController@destroy')->name('pwa.nin.user.destroy');
						  });
						  
						  
					  // PWA Recruitment
						  Route::get('/recruitment', 'PwaNinRecruitmentController@index')->name('pwa.nin.recruitment.index');
						  Route::get('/recruitment/{recruitment_id}', 'PwaNinRecruitmentController@show')->name('pwa.nin.recruitment.show');
						  Route::get('/recruitment/generatepdf/{recruitment_id}', 'PwaNinRecruitmentController@generatepdf')->name('pwa.nin.recruitment.generatepdf');
					  // PWA Store CRUD
						  Route::get('/store', 'PwaNinStoreController@index')->name('pwa.nin.store.index');
						  Route::get('/store/create', 'PwaNinStoreController@create')->name('pwa.nin.store.create');
						  Route::post('/store', 'PwaNinStoreController@store')->name('pwa.nin.store.store');
						  // PWA Specific Store
						  Route::group(['prefix' => 'store/{store_id}'], function () {
							  // PWA User
							  Route::get('/', 'PwaNinStoreController@show')->name('pwa.nin.store.show');
							  Route::get('edit', 'PwaNinStoreController@edit')->name('pwa.nin.store.edit');
							  Route::post('/update', 'PwaNinStoreController@update')->name('pwa.nin.store.update');
							  Route::get('/delete', 'PwaNinStoreController@destroy')->name('pwa.nin.store.destroy');
							  });
						  });

				});

			Route::get('/sms', 'SMSController@index')->name('sms.index');
			Route::get('/sms/create', 'SMSController@create')->name('sms.create');
			Route::post('/sms', 'SMSController@store')->name('sms.store');
			// Specific SMS group	
			Route::group(['prefix' => 'sms/{sms_id}'], function () {
				Route::get('/view', 'SMSController@show')->name('sms.show');
				Route::get('/showsegment', 'SMSController@showsegment')->name('sms.showsegment');
				Route::get('/edit', 'SMSController@edit')->name('sms.edit');
				Route::get('/editSegment', 'SMSController@editSegment')->name('sms.editSegment');
				Route::post('/fetchSegment', 'SMSController@fetchSegment')->name('sms.fetchSegment');
				Route::post('/updatesms', 'SMSController@updateSMS')->name('sms.updatesms');
				Route::post('/updatesegment', 'SMSController@updateSegment')->name('sms.updatesegment');
				Route::get('/delete', 'SMSController@destroy')->name('sms.destroy');
				Route::get('/sendsms', 'SMSController@sendsms')->name('sms.send');
				Route::get('/export', 'SMSController@export')->name('sms.export');

				});
					
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

