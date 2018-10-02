<?php namespace crocodicstudio\crudbooster\controllers;

error_reporting(E_ALL ^ E_NOTICE);

use crocodicstudio\crudbooster\controllers\CBController;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\PDF;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use CRUDBooster;
use CB;
use Schema;
use JsValidator;
use GSSDK;
use GSRequest;

class GigyaCBController extends CBController {
	
	private $gigya_api_key;
	private $gigya_secret_key;
	private $gigya_user_key;

	public function __construct()
	{
		$this->gigya_api_key  = config('crudbooster.GIGYAAPIKEY');
		$this->gigya_secret_key = config('crudbooster.GIGYASECRETKEY');
		$this->gigya_user_key = config('crudbooster.GIGYAUSERKEY');
	}

	public function createTempTable()
	{
		$tableName = 'gigya_customer';

		$table = DB::insert(DB::raw("create table $tableName (
                                        id int NOT NULL AUTO_INCREMENT,
                                        UID varchar(255) NOT NULL,
                                        firstName varchar(255) NOT NULL,
                                        lastName varchar(255),
                                        email varchar(255) NOT NULL,
                                        isLite boolean NOT NULL,
                                        PRIMARY KEY (id)
                                    )"));
		return $table;
	}

	public function createChildTable()
	{
		$childTableName = 'gigya_child';

		$table = DB::insert(DB::raw("CREATE TABLE `gigya_child` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
  			`UID` varchar(255) DEFAULT NULL,
  			`firstName` varchar(255) DEFAULT NULL,
		  `birthDate` date DEFAULT NULL,
		  `birthDateReliability` int(11) NULL,
		  `feeding` varchar(255) DEFAULT NULL,
		  `customerid` int(11) NOT NULL,
		  `sex` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  KEY `customerid` (`customerid`),
		  CONSTRAINT `gigya_child_ibfk_1` FOREIGN KEY (`customerid`) REFERENCES `gigya_customer` (`id`)
			)"));

		// $table = DB::insert(DB::raw("create table $childTableName (
  //                                       id int NOT NULL AUTO_INCREMENT,
  //                                       UID varchar(255) NOT NULL,
  //                                       firstName varchar(255) NOT NULL,
  //                                       birthDate DATE,
  //                                       birthDateReliability INT,
  //                                       feeding varchar(255),
  //                                       customer_id
  //                                       PRIMARY KEY (id)
  //                                   )"));
		return $table;
	}

	public function createAreaInterestTable()
	{
		$areaInterestTable = 'gigya_area_interest';

		$table = DB::insert(DB::raw("CREATE TABLE `gigya_area_interest` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `interestCode` varchar(100) DEFAULT '',
				  `answerDetails` varchar(255) DEFAULT NULL,
				  `creationDate` datetime DEFAULT NULL,
				  `lastUpdateDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				  `UID` varchar(255) DEFAULT NULL,
				  `customerid` int(11) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  KEY `customerid` (`customerid`)
				)"));

		return $table;
	}

	private function getCustomer($offset=0,$limit=5000)
    {
    	$method = "accounts.search";
    	// $request = new GSRequest($apiKey,$secretKey,$method);
    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);

    	$request->setParam("query","select * from emailAccounts where data.child is not null START ".$offset." LIMIT ".$limit."");
    	// $request->setParam("openCursor",true);
    	$response = $request->send();

    	if($response->getErrorCode()==0)
	    {
	        // echo "Success";
            $response = $response->getResponseText();
            $response = json_decode($response, true);
            // return view('test', compact('response'));
	        return $response;

	    }
    	else
	    {
	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	        error_log($response->getLog());
	    }
    }

	public function getIndex() 
	{
		$this->cbLoader();

		$module = CRUDBooster::getCurrentModule();
		// $testPath = CRUDBooster::mainpath();

		if(!CRUDBooster::isView() && $this->global_privilege==FALSE) {
			CRUDBooster::insertLog(trans('crudbooster.log_try_view',['module'=>$module->name]));
			CRUDBooster::redirect(CRUDBooster::adminPath(),trans('crudbooster.denied_access'));
		}
		
		$data['table'] 	  = $this->table;
		$data['table_pk'] = CB::pk($this->table);
		$data['page_title']       = $module->name;
		$data['page_description'] = trans('crudbooster.default_module_description');
		$data['date_candidate']   = $this->date_candidate;
		$data['limit'] = $limit   = (Request::get('limit'))?Request::get('limit'):$this->limit;

		$tablePK = $data['table_pk'];
		$table_columns = CB::getTableColumns($this->table);
		$result = DB::table($this->table)->select(DB::raw($this->primary_key));

		//insert table
		$tableName = 'gigya_customer';
		$childTableName = 'gigya_child';
		$areaInterestTable = 'gigya_area_interest';

		$tableExist = Schema::hasTable($tableName);
		$childTableExist = Schema::hasTable($childTableName);
		$areaInterestTableExist = Schema::hasTable($areaInterestTable);

		if($tableExist !== true && childTableExist !== true && $areaInterestTableExist !== true){
			$tempTable = $this->createTempTable();
			$createChildTable = $this->createChildTable();
			$areaInterestTableExist = $this->createAreaInterestTable();
		}

		$alias            = array();
		$table            = $this->table;

		$columns_table    = $this->columns_table;

		foreach($columns_table as $index => $coltab) {

			$field = @$coltab['name'];
			if(!$field) die('Please make sure there is key `name` in each row of col');

			if(strpos($field, ' as ')!==FALSE) {
				$field = substr($field, strpos($field, ' as ')+4);
				$result->addselect(DB::raw($coltab['name']));
				$columns_table[$index]['type_data']   = 'varchar';
				$columns_table[$index]['field']       = $field;
				$columns_table[$index]['field_raw']   = $field;
				$columns_table[$index]['field_with']  = $field;
				$columns_table[$index]['is_subquery'] = true;
				continue;
			}
			// dd(strpos($field),'.');

			if(strpos($field,'.')!==FALSE) {
				// dd('1');
				$result->addselect($field);
			}else{
				// dd('2');
				$result->addselect($table.'.'.$field);
			}

			$field_array = explode('.', $field);

			if(isset($field_array[1])) {
				$field = $field_array[1];
				$table = $field_array[0];
			}

			$columns_table[$index]['type_data']	 = CRUDBooster::getFieldType($table,$field);
			$columns_table[$index]['field']      = $field;
			$columns_table[$index]['field_raw']  = $field;
			$columns_table[$index]['field_with'] = $table.'.'.$field;

			$f = $this->findNameFormType($field);
			if ($f!==FALSE)
			{
				$columns_table[$index]['type_form'] = $f["type"];
				if($f["type"]=='select')
				{
					if (array_key_exists('datatable',$f))
					{
						$farr = explode(",",$f["datatable"]);
						$columns_table[$index]['optionlist'] = DB::table($farr[0])->pluck($farr[1])->toArray();							
					}
					else if (array_key_exists('dataenum',$f))
					{
						$farr = explode(";",$f["dataenum"]);
						$columns_table[$index]['optionlist'] = $farr;
					}
				}
			}
		} //end foreach

		if(Request::get('q')) {
			$result->where(function($w) use ($columns_table, $request) {
				foreach($columns_table as $col) {
						if(!$col['field_with']) continue;
						if($col['is_subquery']) continue;
						$w->orwhere($col['field_with'],"like","%".Request::get("q")."%");
				}
			});
		}

		if(Request::get('where')) {
			foreach(Request::get('where') as $k=>$v) {
				$result->where($table.'.'.$k,$v);
			}
		}

		$filter_is_orderby = false;
		if(Request::get('filter_column')) {

			$filter_column = Request::get('filter_column');
			$result->where(function($w) use ($filter_column,$fc) {
				foreach($filter_column as $key=>$fc) {
					// dd($fc);
					$value = @$fc['value'];
					$type  = @$fc['type'];

					if($type == 'empty') {
						$w->whereNull($key)->orWhere($key,'');
						continue;
					}

					if($value == 'empty') {
						$w->whereNull($key)->orWhere($key,'');
					}

					if($value=='' || $type=='') continue;

					if($type == 'between') continue;

					switch($type) {
						default:
							if($key && $type && $value) $w->where($key,$type,$value);
						break;
						case 'like':
						case 'not like':
							$value = '%'.$value.'%';
							if($key && $type && $value) $w->where($key,$type,$value);
						break;
						case 'in':
						case 'not in':
							if($value) {
								$value = explode(',',$value);
								if($key && $value) $w->whereIn($key,$value);
							}
						break;
					}

				}
			});

			foreach($filter_column as $key=>$fc) {
				$value = @$fc['value'];
				$type  = @$fc['type'];
				$sorting = @$fc['sorting'];
				$type_data = @$fc['type_data'];
				$label_data = @$fc['label'];

				if($sorting!='') {
					if($key) {
						$result->orderby($key,$sorting);
						$filter_is_orderby = true;
					}
				}

				if ($type=='between') {
					
					if (($type_data == 'datetime')||((strpos(strtolower($key),"date")!==false)&&((strpos(strtolower($key),"time")!==false)||(strpos(strtolower($key),"create")!==false))))
					{	
							$value[0] .=" 00:00:00";
							$value[1] .=" 23:59:59";
							$result->whereBetween($key,$value);
					}
					else
						if($key && $value) $result->whereBetween($key,$value);
				}else{
					continue;
				}
			}
		}

		if($filter_is_orderby == true) {
			$data['result']  = $result->paginate($limit);

		}else{
			if($this->orderby) {
				if(is_array($this->orderby)) {
					foreach($this->orderby as $k=>$v) {
						if(strpos($k, '.')!==FALSE) {
							$orderby_table = explode(".",$k)[0];
						}else{
							$orderby_table = $table;
						}
						//$result->orderby($orderby_table.'.'.$k,$v);
						$result->orderby($k,$v);
					}
				}else{
					$this->orderby = explode(";",$this->orderby);
					foreach($this->orderby as $o) {
						$o = explode(",",$o);
						$k = $o[0];
						$v = $o[1];
						if(strpos($k, '.')!==FALSE) {
							$orderby_table = explode(".",$k)[0];
						}else{
							$orderby_table = $table;
						}
						//$result->orderby($orderby_table.'.'.$k,$v);
						$result->orderby($k,$v);
					}
				}
				$data['result'] = $result->paginate($limit);
			}else{
				$data['result'] = $result->orderby($this->table.'.'.$this->primary_key,'desc')->paginate($limit);
			}
		}

		$data['columns'] = $columns_table;

		if($this->index_return) return $data;

		//LISTING INDEX HTML
		$addaction     = $this->data['addaction'];

		if($this->sub_module) {
			foreach($this->sub_module as $s) {
				$table_parent = CRUDBooster::parseSqlTable($this->table)['table'];
				$addaction[] = [
					'label'=>$s['label'],
					'icon'=>$s['button_icon'],
					'url'=>CRUDBooster::adminPath($s['path']).'?parent_table='.$table_parent.'&parent_columns='.$s['parent_columns'].'&parent_id=[id]&return_url='.urlencode(Request::fullUrl()).'&foreign_key='.$s['foreign_key'].'&label='.urlencode($s['label']),
					'color'=>$s['button_color'],
                                        'showIf'=>$s['showIf']
				];
			}
		}
		$
		$mainpath      = CRUDBooster::mainpath();
		$orig_mainpath = $this->data['mainpath'];
		$title_field   = $this->title_field;
		$html_contents = array();
		$page = (Request::get('page'))?Request::get('page'):1; 
		$number = ($page-1)*$limit+1; 
		foreach($data['result'] as $row) {
			$html_content = array();

			if($this->button_bulk_action) {		

				$html_content[] = "<input type='checkbox' class='checkbox' name='checkbox[]' value='".$row->{$tablePK}."'/>";
			}

			if($this->show_numbering) {
				$html_content[] = $number.'. ';
				$number++;
			}

			foreach($columns_table as $col) {
		          if($col['visible']===FALSE) continue;		          

		          $value = @$row->{$col['field']};
		          $title = @$row->{$this->title_field};
		          $label = $col['label'];

		          if(isset($col['image'])) {
			            if($value=='') {			              
			              $value = "<a  data-lightbox='roadtrip' rel='group_{{$table}}' title='$label: $title' href='http://placehold.it/50x50&text=NO+IMAGE'><img width='40px' height='40px' src='http://placehold.it/50x50&text=NO+IMAGE'/></a>";
			            }else{
							$pic = (strpos($value,'http://')!==FALSE)?$value:asset($value);				            
				            $value = "<a data-lightbox='roadtrip'  rel='group_{{$table}}' title='$label: $title' href='".$pic."'><img width='40px' height='40px' src='".$pic."'/></a>";
			            }			            
		          }

		          if(@$col['download']) {
			            $url = (strpos($value,'http://')!==FALSE)?$value:asset($value).'?download=1';
			            if($value) {
			            	$value = "<a class='btn btn-xs btn-primary' href='$url' target='_blank' title='Download File'><i class='fa fa-download'></i> Download</a>";
			            }else{
			            	$value = " - ";
			            }
		          }

		            if($col['str_limit']) {
		            	$value = trim(strip_tags($value));
		            	$value = str_limit($value,$col['str_limit']);
		            }

		            if($col['nl2br']) {
		            	$value = nl2br($value);
		            }

		            if($col['callback_php']) {
		              foreach($row as $k=>$v) {
		              		$col['callback_php'] = str_replace("[".$k."]",$v,$col['callback_php']);
		              }
		              @eval("\$value = ".$col['callback_php'].";");
		            }

		            //New method for callback
			        if(isset($col['callback'])) {
			        	$value = call_user_func($col['callback'],$row);
			        }


		            $datavalue = @unserialize($value);
					if ($datavalue !== false) {
						if($datavalue) {
							$prevalue = [];
							foreach($datavalue as $d) {
								if($d['label']) {
									$prevalue[] = $d['label'];
								}
						    }
						    if(count($prevalue)) {
						    	$value = implode(", ",$prevalue);
						    }
						}
					}

		          $html_content[] = $value;
	        } //end foreach columns_table


	      if($this->button_table_action):

	      		$button_action_style = $this->button_action_style;
	      		$html_content[] = "<div class='button_action' style='text-align:right'>".view('crudbooster::components.action',compact('addaction','row','button_action_style','parent_field'))->render()."</div>";

          endif;//button_table_action


          foreach($html_content as $i=>$v) {
          	$this->hook_row_index($i,$v);
          	$html_content[$i] = $v;
          }

	      $html_contents[] = $html_content;
		} //end foreach data[result]


		//$customerlist = $this->getCustomer();

		// echo "<pre>".print_r($data,TRUE)."</pre><br>";

 		$html_contents = ['html'=>$html_contents,'data'=>$data['result']];

		$data['html_contents'] = $html_contents;
		$data['limit'] = $result->count();
		/*$itemSql = $result->toSql();
		$itemSql = str_replace("offset","start",$itemSql);
		echo $itemSql."<br>";*/


		return view("crudbooster::default.index",$data);
		
	} //last

	public function getEdit($id){
		$this->cbLoader();
		$row             = DB::table($this->table)->where($this->primary_key,$id)->first();
		$response = $this->getCustomerRecord($row->UID,$row->email);
		$results = $response['results'];
		
		// dd($results);
		$UID = $results[0]['UID'];
		if($row->UID == NULL){
			DB::table('gigya_customer')
                    ->where('email', $row->email)
                    ->update(['UID'=>$UID]);
		}
		$profile = $results[0]['profile'];
		$child = $results[0]['data']['child'];
		$interest = $results[0]['data']['areaOfInterest'];
		// $row->firstName = $profile['firstName'];
		// $row->email = $profile['email'];
		// $row->address = $profile['address'];
		// dd($profile['phones']['number']);
		$mobileNumber = $profile['phones']['number'];

		foreach ($profile as $key => $value) {
			if($key == 'phones'){
				$row->phones = $mobileNumber;
			} else{
				$row->$key = $profile[$key];
			}
		}

		DB::table('gigya_child')->where('customerid', '=', $id)->delete();
		if(isset($child)){
			try {			
				foreach($child as $child2)
				{
					$child2["customerid"] = $id;
					DB::table("gigya_child")->insert([
	                            $child2
	                ]);

				}
			}
			catch (\Exception $e)
			{
				$child["customerid"] = $id;
				DB::table("gigya_child")->insert([
                            $child
                ]);
			}
		}

		DB::table('gigya_area_interest')->where('customerid', '=', $id)->delete();

		if(isset($interest)){
			try {			
				foreach($interest as $interest2)
				{
					$interest2["customerid"] = $id;
					DB::table("gigya_area_interest")->insert([
	                            $interest2
	                ]);

				}
			}
			catch (\Exception $e)
			{
				$interest["customerid"] = $id;
				DB::table("gigya_area_interest")->insert([
                            $interest
                ]);
			}
		}


		if(!CRUDBooster::isRead() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			CRUDBooster::insertLog(trans("crudbooster.log_try_edit",['name'=>$row->{$this->title_field},'module'=>CRUDBooster::getCurrentModule()->name]));
			CRUDBooster::redirect(CRUDBooster::adminPath(),trans('crudbooster.denied_access'));
		}

		$page_menu       = Route::getCurrentRoute()->getActionName();
		$page_title 	 = trans("crudbooster.edit_data_page_title",['module'=>CRUDBooster::getCurrentModule()->name,'name'=>$row->{$this->title_field}]);
		$command 		 = 'edit';
		Session::put('current_row_id',$id);
		$option_id		 = $this->option_id;
		$option_fields	 = $this->option_fields;
		$table = $this->table;

		return view('crudbooster::default.form',compact('id','row','page_menu','page_title','command','option_id','option_fields','table'));
		
	}


	public function postEditSave($id) {

		$this->cbLoader();
		$row = DB::table($this->table)->where($this->primary_key,$id)->first();
		$UID = $row->UID;
		// dd($UID);

		if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE) {
			CRUDBooster::insertLog(trans("crudbooster.log_try_add",['name'=>$row->{$this->title_field},'module'=>CRUDBooster::getCurrentModule()->name]));
			CRUDBooster::redirect(CRUDBooster::adminPath(),trans('crudbooster.denied_access'));
		}

		$this->validation($id);
		$this->input_assignment($id);				

		if (Schema::hasColumn($this->table, 'updated_at'))
		{
		    $this->arr['updated_at'] = date('Y-m-d H:i:s');
		}
		

		$this->hook_before_edit($this->arr,$id);		
		DB::table($this->table)->where($this->primary_key,$id)->update($this->arr);		

		//Looping Data Input Again After Insert
		// dd($this->data_inputan);
		

		foreach($this->data_inputan as $ro) {

			$name = $ro['name'];
			
			$type = $ro['type'];

			if(!$name) continue;

			$inputdata = Request::get($name);
			$setInputData[$name] = $inputdata;

			//Insert Data Checkbox if Type Datatable
			if($ro['type'] == 'checkbox') {
				if($ro['relationship_table']) {
					$datatable = explode(",",$ro['datatable'])[0];					
					
					$foreignKey2 = CRUDBooster::getForeignKey($datatable,$ro['relationship_table']);
					$foreignKey = CRUDBooster::getForeignKey($this->table,$ro['relationship_table']);
					DB::table($ro['relationship_table'])->where($foreignKey,$id)->delete();

					if($inputdata) {
						foreach($inputdata as $input_id) {
							$relationship_table_pk = CB::pk($ro['relationship_table']);
							DB::table($ro['relationship_table'])->insert([
								$relationship_table_pk=>CRUDBooster::newId($ro['relationship_table']),
								$foreignKey=>$id,
								$foreignKey2=>$input_id
								]);
						}
					}
					

				}
			}			


			if($ro['type'] == 'select2') {
				if($ro['relationship_table']) {
					$datatable = explode(",",$ro['datatable'])[0];					
					
					$foreignKey2 = CRUDBooster::getForeignKey($datatable,$ro['relationship_table']);
					$foreignKey = CRUDBooster::getForeignKey($this->table,$ro['relationship_table']);
					DB::table($ro['relationship_table'])->where($foreignKey,$id)->delete();

					if($inputdata) {
						foreach($inputdata as $input_id) {
							$relationship_table_pk = CB::pk($ro['relationship_table']);
							DB::table($ro['relationship_table'])->insert([
								$relationship_table_pk=>CRUDBooster::newId($ro['relationship_table']),
								$foreignKey=>$id,
								$foreignKey2=>$input_id
								]);
						}
					}
					

				}
			}


			if($ro['type']=='child') {

				$tempId = array();
				$name = str_slug($ro['label'],'');
				$columns = $ro['columns'];
				if (!empty(Request::get($name.'-'.$columns[0]['name'])))
					$count_input_data = count(Request::get($name.'-'.$columns[0]['name']))-1;
				else
					$count_input_data = -1;
				$child_array = [];
				$childtable = CRUDBooster::parseSqlTable($ro['table'])['table'];				
				$fk = $ro['foreign_key'];
				$childtablePK = CB::pk($childtable);
				// dd($childtablePK);
				//dd($count_input_data);

				for($i=0;$i<=$count_input_data;$i++) {
					
					$column_data = [];
					// $column_data[$childtablePK] = $lastId;
					$column_data[$fk] = $id;
					foreach($columns as $col) {
						$colname = $col['name'];
						$column_data[$colname] = Request::get($name.'-'.$colname)[$i];
					}

					$child_array[] = $column_data;
					// dd($child_array);
					if($child_array[$i]['id'] == NULL){
						
						$customer_array[] = $row;

						$test = (array) $customer_array[$i];

						foreach($child_array as $key => $value)
						{
							$newArray = array_merge($child_array[$key],$test);
						}

						unset($child_array['id']);
						$lastId = CRUDBooster::newId($childtable);
						$child_array[$i]['id'] = $lastId;
						DB::table($childtable)->insert($child_array);
					}

					$tempId[] = $child_array[$i]['id'];
					//unset($child_array[$i]['id']);

					DB::table($childtable) 
					->where('id', $tempId[$i])
					->update($child_array[$i]);
	
				}


				$currentids = array_pluck($child_array,"id");
				
				$newids =  DB::table($childtable)->where($fk,'=',$id)->pluck('id')->toArray();
				

				$array3 = array_diff($newids,$currentids);

				if (isset($array3))
				{
					DB::table($childtable)
					->whereIn('id', $array3)
					->delete();
				}


			}

		}//end foreach
		// dd($UID,$setInputData);

		// $setInputData = array_diff($setInputData, ["UID","children", "sample_request", "careline_detail"]);
		// dd($setInputData);
		$removeKeys = array("UID","children", "sample_request", "careline_detail","area_of_interest");
		foreach ($removeKeys as $key) {
			unset($setInputData[$key]);
		}

		$childArray = $childData[0];
		unset($childArray['customerid']);

		$areaInterestData = $areaInterestData[0];
		unset($areaInterestData['customerid']);

		// dd($childArray);

		$this->updateCustomerRecord($UID,$setInputData,$childArray,$areaInterestData);

		$this->hook_after_edit($id);

		$this->return_url = ($this->return_url)?$this->return_url:Request::get('return_url');

		//insert log
		CRUDBooster::insertLog(trans("crudbooster.log_update",['name'=>$this->arr[$this->title_field],'module'=>CRUDBooster::getCurrentModule()->name]));

		if($this->return_url) {
			CRUDBooster::redirect($this->return_url,trans("crudbooster.alert_update_data_success"),'success');
		}else{
			if(Request::get('submit') == trans('crudbooster.button_save_more')) {
				CRUDBooster::redirect(CRUDBooster::mainpath('add'),trans("crudbooster.alert_update_data_success"),'success');
			}else{
				CRUDBooster::redirect(CRUDBooster::mainpath(),trans("crudbooster.alert_update_data_success"),'success');
			}
		}
	}

	// public function updateCustomerRecord($UID, $setInputData)
 //    {
            	
 //    	$method2 = "accounts.setAccountInfo";
 //    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method2,null,true,$this->gigya_user_key);
 //    	$request->setParam("UID",$UID);
 //    	$request->setParam("profile",json_encode($setInputData));

 //    	$response = $request->send();
 //    	// dd($response);
 //    	if($response->getErrorCode()==0)
 //    	{
 //    	    // echo "Success";
 //    	    $response = $response->getResponseText();
 //    	    $response = json_decode($response, true);

 //    	    Log::info(print_r($response,TRUE));
 //    	    //echo "<pre>".print_r($response,TRUE)."</pre>\n";
 //    	    //echo $reg['email'];
 //    	}
 //    	else
 //    	{	
 //    		Log::error("Uh-oh, we got the following error: " . $response->getErrorMessage());
 //    	    //echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
 //    	    //error_log($response->getLog());
 //    	}

 //    }

    public function getCustomerRecord($UID,$email)
    {
    	$method = "accounts.search";
    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);
    	// $request->setParam("UID",$UID);
    	// $request->setParam("include","profile,data");
    	$request->setParam("query","SELECT * FROM emailAccounts WHERE UID='$UID' OR profile.email ='$email'");

	    $response = $request->send();

    	if($response->getErrorCode()==0)
    	{
    	    // echo "Success";
    	    $response = $response->getResponseText();
    	    $response = json_decode($response, true);
    	    return $response;
    	    Log::info(print_r($response,TRUE));
    	    //echo "<pre>".print_r($response,TRUE)."</pre>\n";
    	    //echo $reg['email'];
    	}
    	else
    	{	
    		Log::error("Uh-oh, we got the following error: " . $response->getErrorMessage());
    	    //echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
    	    //error_log($response->getLog());
    	}
    }

	public function updateCustomerRecord($UID, $setInputData, $childArray, $areaInterestData)
    {
    	//$method = "accounts.search";
    	$method = "accounts.initRegistration";

    	// $request = new GSRequest($apiKey,$secretKey,$method);
    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);

    	//$request->setParam("query","select * from accounts LIMIT 50");
    	$request->setParam("isLite",true);
    	$request->setParam("callback","testcall");
    	// $request->setParam("openCursor",true);

    	$response = $request->send();

    	$regtoken="";
    	
    	if($response->getErrorCode()==0)
	    {
	        // echo "Success";
	        $response = $response->getResponseText();
	        $response = json_decode($response, true);

	        echo "<pre>".print_r($response,TRUE)."</pre>\n";
	        $regtoken = $response["regToken"];
	    }
    	else
	    {
	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	        error_log($response->getLog());
	    }

	    if ($regtoken!="")
        {
        	// dd($childArray);
	    	// dd($UID, $setInputData);
	    	$setInputData['phones'] = array("number"=>$setInputData['phones']);

	  //   	$childData['child']{'firstName'} = $childArray['firstName'];
	  //   	$childData['child']{'birthDate'} = $childArray['birthDate'];
			// $childData['child']{'birthDateReliability'} = childArray['birthDateReliability'];
	  //   	$childData['child']{'feeding'} = childArray['feeding'];


	    	

			$itemParent = DB::table('gigya_customer')->where('UID',$UID)->first();

			if (isset($itemParent))
				$parentid = $itemParent->id;

			//dd($parentid);

			$childItems = DB::table('gigya_child')->where('customerid',$parentid)->get();
	    	
	    	$childData = [];
	    	$ci = 0;
			foreach ($childItems as $childItem) {
			    $childData[$ci]['firstName'] = $childItem->firstName;
		    	$childData[$ci]['birthDate'] = $childItem->birthDate;
		    	$childData[$ci]['birthDateReliability'] = $childItem->birthDateReliability;
		    	$childData[$ci]['feeding'] = $childItem->feeding;
		    	$ci++;
			}

			$interestItems = DB::table('gigya_area_interest')->where('customerid',$parentid)->get();


	    	$child["child"] = $childData;
	    	//dd(json_encode($child));


	    	$interestData=[];
	    	$ci = 0;

	    	foreach ($interestItems as $interestItem) {
			    $interestData[$ci]['interestCode'] = $interestItem->interestCode;
		    	$interestData[$ci]['answerDetails'] = $interestItem->answerDetails;
		    	$ci++;
			}

			//dd($interestData);
			$child["areaOfInterest"] = $interestData;
	    	/*foreach ($areaInterestData as $key => $value) {
	    		$interestItem['areaOfInterest.'.$key] = $value;
 	    	}*/


	    	$method = "accounts.search";
	    	// $method = "accounts.getAccountInfo";
	    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);
	    	$request->setParam("query","SELECT * FROM emailAccounts WHERE UID='$UID'");
	    	// $request->setParam("UID",$UID);
	    	$response = $request->send();

	    	if($response->getErrorCode()==0)
		   	{
	            $response = $response->getResponseText();
	            $response = json_decode($response, true);
	            
	            	$method2 = "accounts.setAccountInfo";
	            	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method2,null,true,$this->gigya_user_key);
	            	$request->setParam("regToken",$regtoken);
					$request->setparam("data", json_encode($child));
					//dd(json_encode($child));
					//$request->setparam("data", json_encode($interestItem));
	            	$request->setParam("profile",json_encode($setInputData));

	            	$response = $request->send();
	            	if($response->getErrorCode()==0)
	            	{
	            	    // echo "Success";
	            	    $response = $response->getResponseText();
	            	    $response = json_decode($response, true);
	            	    echo "<pre>".print_r($response,TRUE)."</pre>\n";
	            	    echo $reg['email'];

	            	}
	            	else
	            	{	
	            	    echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	            	    error_log($response->getLog());
	            	}

	        }
	            // dd($response);
		        // return $response;
		}
    	else
	    {

	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	        error_log($response->getLog());
	    }
	}

	public function postAddSave() {
		
		$this->cbLoader();
		if(!CRUDBooster::isCreate() && $this->global_privilege==FALSE) {
			CRUDBooster::insertLog(trans('crudbooster.log_try_add_save',['name'=>Request::input($this->title_field),'module'=>CRUDBooster::getCurrentModule()->name ]));
			CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
		}

		$this->validation();
		$this->input_assignment();		

		if(Schema::hasColumn($this->table, 'created_at'))
		{
		    $this->arr['created_at'] = date('Y-m-d H:i:s');
		}

		$this->hook_before_add($this->arr);

		$this->arr[$this->primary_key] = $id = CRUDBooster::newId($this->table);	
		DB::table($this->table)->insert($this->arr);


		//Looping Data Input Again After Insert
		foreach($this->data_inputan as $ro) {
			$name = $ro['name'];
			if(!$name) continue;

			$inputdata = Request::get($name);
			$setInputData[$name] = $inputdata;

			//Insert Data Checkbox if Type Datatable
			if($ro['type'] == 'checkbox') {
				if($ro['relationship_table']) {
					$datatable = explode(",",$ro['datatable'])[0];
					$foreignKey2 = CRUDBooster::getForeignKey($datatable,$ro['relationship_table']);
					$foreignKey = CRUDBooster::getForeignKey($this->table,$ro['relationship_table']);
					DB::table($ro['relationship_table'])->where($foreignKey,$id)->delete();

					if($inputdata) {
						$relationship_table_pk = CB::pk($ro['relationship_table']);
						foreach($inputdata as $input_id) {
							DB::table($ro['relationship_table'])->insert([
								$relationship_table_pk=>CRUDBooster::newId($ro['relationship_table']),
								$foreignKey=>$id,
								$foreignKey2=>$input_id
								]);
						}
					}

				}
			}


			if($ro['type'] == 'select2') {
				if($ro['relationship_table']) {
					$datatable = explode(",",$ro['datatable'])[0];
					$foreignKey2 = CRUDBooster::getForeignKey($datatable,$ro['relationship_table']);
					$foreignKey = CRUDBooster::getForeignKey($this->table,$ro['relationship_table']);
					DB::table($ro['relationship_table'])->where($foreignKey,$id)->delete();

					if($inputdata) {
						foreach($inputdata as $input_id) {
							$relationship_table_pk = CB::pk($row['relationship_table']);
							DB::table($ro['relationship_table'])->insert([
								$relationship_table_pk=>CRUDBooster::newId($ro['relationship_table']),
								$foreignKey=>$id,
								$foreignKey2=>$input_id
								]);
						}
					}

				}
			}

			if($ro['type']=='child') {
				$name = str_slug($ro['label'],'');
				$columns = $ro['columns'];				
				$count_input_data = count(Request::get($name.'-'.$columns[0]['name']))-1;
				$child_array = [];

				for($i=0;$i<=$count_input_data;$i++) {
					$fk = $ro['foreign_key'];
					$column_data = [];
					$column_data[$fk] = $id;
					foreach($columns as $col) {
						$colname = $col['name'];
						$column_data[$colname] = Request::get($name.'-'.$colname)[$i];
					}
					$child_array[] = $column_data;
				}	
				
				$childtable = CRUDBooster::parseSqlTable($ro['table'])['table'];
				// DB::table($childtable)->insert($child_array);
				
				if($childtable == 'gigya_child'){
					$childData = $child_array;
				}


				// if($childtable=='gigya_area_interest'){
				// 	$areaInterestData = $child_array;
				// }
			}
			
		}

		$this->createCustomerRecord($setInputData);

		$this->hook_after_add($this->arr[$this->primary_key]);

		$this->return_url = ($this->return_url)?$this->return_url:Request::get('return_url');

		//insert log
		CRUDBooster::insertLog(trans("crudbooster.log_add",['name'=>$this->arr[$this->title_field],'module'=>CRUDBooster::getCurrentModule()->name]));

		if($this->return_url) {
			if(Request::get('submit') == trans('crudbooster.button_save_more')) {
				CRUDBooster::redirect(Request::server('HTTP_REFERER'),trans("crudbooster.alert_add_data_success"),'success');
			}else{
				CRUDBooster::redirect($this->return_url,trans("crudbooster.alert_add_data_success"),'success');
			}

		}else{
			if(Request::get('submit') == trans('crudbooster.button_save_more')) {
				CRUDBooster::redirect(CRUDBooster::mainpath('add'),trans("crudbooster.alert_add_data_success"),'success');
			}else{
				CRUDBooster::redirect(CRUDBooster::mainpath(),trans("crudbooster.alert_add_data_success"),'success');
			}
		}
	}

	public function createCustomerRecord($setInputData)
    {
    	$removeKeys = array("UID","children", "sample_request", "careline_detail","area_of_interest");
		foreach ($removeKeys as $key) {
			unset($setInputData[$key]);
		}

		// $removeKeys = array("id","customerid");

		// foreach ($removeKeys as $key) {
		// 	unset($childData[0][$key]);
		// }

		// dd($setInputData);

    	// dd($setInputData, $childData[0]);

    	//$method = "accounts.search";
    	$method = "accounts.initRegistration";

    	// $request = new GSRequest($apiKey,$secretKey,$method);
    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);

    	//$request->setParam("query","select * from accounts LIMIT 50");
    	$request->setParam("isLite",true);
    	$request->setParam("callback","testcall");
    	// $request->setParam("openCursor",true);

    	$response = $request->send();

    	$regtoken="";
    	
    	if($response->getErrorCode()==0)
	    {
	        // echo "Success";
	        $response = $response->getResponseText();
	        $response = json_decode($response, true);

	        echo "<pre>".print_r($response,TRUE)."</pre>\n";
	        $regtoken = $response["regToken"];
	    }
    	else
	    {
	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	        error_log($response->getLog());
	    }

	    if ($regtoken!="")
        {
        	// dd($childArray);
	    	// dd($UID, $setInputData);
	    	$setInputData['phones'] = array("number"=>$setInputData['phones']);

	    	// $childData['child.firstName'] = $childArray['firstName'];
	    	// $childData['child.birthDate'] = $childArray['birthDate'];
	    	// $childData['child.birthDateReliability'] = $childArray['birthDateReliability'];
	    	// $childData['child.feeding'] = $childArray['feeding'];

	    	// $child = $childData;
	    	// $interestitem=[];
	    	// foreach ($areaInterestData as $key => $value) {
	    	// 	$interestItem['areaOfInterest.'.$key] = $value;
 	    // 	}

 	    	$email = $setInputData['email'];

	    	$method = "accounts.search";
	    	// $method = "accounts.getAccountInfo";
	    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);
	    	$request->setParam("query","SELECT * FROM emailAccounts WHERE profile.email='$email'");
	    	// $request->setParam("UID",$UID);
	    	$response = $request->send();


	    	if($response->getErrorCode()==0)
		   	{
	            $response = $response->getResponseText();
	            $response = json_decode($response, true);

	            if (empty($response['results'])) {

	            	$method2 = "accounts.setAccountInfo";
	            	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method2,null,true,$this->gigya_user_key);
	            	$request->setParam("regToken",$regtoken);
					// $request->setparam("data", json_encode($child));
					// $request->setparam("data", json_encode($interestItem));
	            	$request->setParam("profile",json_encode($setInputData));

	            	$response = $request->send();
	            	if($response->getErrorCode()==0)
	            	{
	            	    // echo "Success";
	            	    $response = $response->getResponseText();
	            	    $response = json_decode($response, true);
	            	    echo "<pre>".print_r($response,TRUE)."</pre>\n";
	            	    echo $reg['email'];

	            	}
	            	else
	            	{	
	            	    echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	            	    error_log($response->getLog());
	            	}
	            }
	            else
	            {
	            	dd('email already exists in gigya database');
	            }

	        }
	            // dd($response);
		        // return $response;
		}
    	else
	    {

	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	        error_log($response->getLog());
	    }
	}

	public function hook_before_addscreen() {

	}

}


