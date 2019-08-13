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
use Redirect;

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

	public function cbLoader() {
		$this->cbInit();

		$this->checkHideForm();

		$this->primary_key 					 = CB::pk($this->table);
		$this->columns_table                 = $this->col;
		$this->data_inputan                  = $this->form;
		$this->data['pk']				     = $this->primary_key;
		$this->data['forms']                 = $this->data_inputan;
		$this->data['hide_form'] 			 = $this->hide_form;
		$this->data['addaction']             = ($this->show_addaction)?$this->addaction:NULL;
		$this->data['table']                 = $this->table;
		$this->data['title_field']           = $this->title_field;
		$this->data['appname']               = CRUDBooster::getSetting('appname');
		$this->data['alerts']                = $this->alert;
		$this->data['index_button']          = $this->index_button;
		$this->data['show_numbering']	     = $this->show_numbering;
		$this->data['button_detail']         = $this->button_detail;
		$this->data['button_edit']           = $this->button_edit;
		$this->data['button_show']           = $this->button_show;
		$this->data['button_add']            = $this->button_add;
		$this->data['button_delete']         = $this->button_delete;
		$this->data['button_filter']         = $this->button_filter;
		$this->data['button_export']         = $this->button_export;
		$this->data['button_addmore']        = $this->button_addmore;
		$this->data['button_cancel']         = $this->button_cancel;
		$this->data['button_save']           = $this->button_save;
		$this->data['button_table_action']   = $this->button_table_action;
		$this->data['button_bulk_action']    = $this->button_bulk_action;
		$this->data['button_import']         = $this->button_import;
		$this->data['button_action_width']   = $this->button_action_width;
		$this->data['button_selected']       = $this->button_selected;
		$this->data['index_statistic']       = $this->index_statistic;
		$this->data['index_additional_view'] = $this->index_additional_view;
		$this->data['table_row_color']       = $this->table_row_color;
		$this->data['pre_index_html']        = $this->pre_index_html;
		$this->data['post_index_html']       = $this->post_index_html;
		$this->data['load_js']               = $this->load_js;
		$this->data['load_css']              = $this->load_css;
		$this->data['script_js']             = $this->script_js;
		$this->data['style_css']             = $this->style_css;
		$this->data['sub_module']            = $this->sub_module;
		$this->data['parent_field'] 		 = (g('parent_field'))?:$this->parent_field;
		$this->data['parent_id'] 		 	 = (g('parent_id'))?:$this->parent_id;

		if(CRUDBooster::getCurrentMethod() == 'getProfile') {
			Session::put('current_row_id',CRUDBooster::myId());
			$this->data['return_url'] = Request::fullUrl();			
		}		

        view()->share($this->data);
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
                                        isLite boolean DEFAULT 1,
                                        PRIMARY KEY (id)
                                    )"));
		return $table;
	}

/*
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
		  `interestCode` varchar(255) DEFAULT NULL,
		  `answerDetails` varchar(255) DEFAULT NULL,
		  `customerid` int(11) NOT NULL,
		  `sex` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  KEY `customerid` (`customerid`),
		  CONSTRAINT `gigya_child_ibfk_1` FOREIGN KEY (`customerid`) REFERENCES `gigya_customer` (`id`)
			)"));

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
	*/

	private function getCustomer($offset=0,$limit=10)
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

		if(Request::get('parent_table')) {
			$parentTablePK = CB::pk(g('parent_table'));
			$data['parent_table'] = DB::table(Request::get('parent_table'))->where($parentTablePK,Request::get('parent_id'))->first();
			if(Request::get('foreign_key')) {
				$data['parent_field'] = Request::get('foreign_key');
			}else{
				$data['parent_field'] = CB::getTableForeignKey(g('parent_table'),$this->table);	
			}

			if($parent_field) {
				foreach($this->columns_table as $i=>$col) {
					if($col['name'] == $parent_field) {
						unset($this->columns_table[$i]);
					}
				}
			}
		}
		
		$data['table'] 	  = $this->table;
		$data['table_pk'] = CB::pk($this->table);
		$data['page_title']       = $module->name;
		$data['page_description'] = trans('crudbooster.default_module_description');
		$data['date_candidate']   = $this->date_candidate;
		$data['limit'] = $limit   = (Request::get('limit'))?Request::get('limit'):$this->limit;

		$tablePK = $data['table_pk'];
		$table_columns = CB::getTableColumns($this->table);
		// $result = DB::table($this->table)->select(DB::raw($this->primary_key));
		$result = DB::table($this->table)->select(DB::raw($this->table.".".$this->primary_key));
		// dd($result);
		if(Request::get('parent_id')) {
			$table_parent = $this->table;
			$table_parent = CRUDBooster::parseSqlTable($table_parent)['table'];
			$result->where($table_parent.'.'.Request::get('foreign_key'),Request::get('parent_id'));
		}

		$this->hook_query_index($result);
		if(in_array('deleted_at', $table_columns)) {
			$result->where($this->table.'.deleted_at',NULL);
		}

		//insert table
		$tableName = 'gigya_customer';
		// $childTableName = 'gigya_child';
		$areaInterestTable = 'gigya_area_interest';

		$tableExist = Schema::hasTable($tableName);
		$childTableExist = Schema::hasTable($childTableName);
		$areaInterestTableExist = Schema::hasTable($areaInterestTable);

		if($tableExist !== true && $childTableExist !== true && $areaInterestTableExist !== true){
			$tempTable = $this->createTempTable();
			$createChildTable = $this->createChildTable();
			$areaInterestTableExist = $this->createAreaInterestTable();
		}

		$alias            = array();
		$join_alias_count = 0;
		$join_table_temp  = array();
		$table            = $this->table;
		$columns_table    = $this->columns_table;

		foreach($columns_table as $index => $coltab) {

			$join = @$coltab['join'];
			$join_where = @$coltab['join_where'];
			$join_id = @$coltab['join_id'];
			$field = @$coltab['name'];
			$join_table_temp[] = $table;

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
				$result->addselect($field);
			}else{
				$result->addselect($table.'.'.$field);
			}

			$field_array = explode('.', $field);

			if(isset($field_array[1])) {
				$field = $field_array[1];
				$table = $field_array[0];
			}

			// $columns_table[$index]['type_data']	 = CRUDBooster::getFieldType($table,$field);
			// $columns_table[$index]['field']      = $field;
			// $columns_table[$index]['field_raw']  = $field;
			// $columns_table[$index]['field_with'] = $table.'.'.$field;

			if($join) {

				$join_exp     = explode(',', $join);

				$join_table  = $join_exp[0];
				$joinTablePK = CB::pk($join_table);
				$join_column = $join_exp[1];
				$join_alias  = str_replace(".", "_", $join_table);

				if(in_array($join_table, $join_table_temp)) {
					$join_alias_count += 1;
					$join_alias = $join_table.$join_alias_count;
				}
				$join_table_temp[] = $join_table;

				$result->leftjoin($join_table.' as '.$join_alias,$join_alias.(($join_id)? '.'.$join_id:'.'.$joinTablePK),'=',DB::raw($table.'.'.$field. (($join_where) ? ' AND '.$join_where.' ':'') ) );
				$result->addselect($join_alias.'.'.$join_column.' as '.$join_alias.'_'.$join_column);

				$join_table_columns = CRUDBooster::getTableColumns($join_table);
				if($join_table_columns) {
					foreach($join_table_columns as $jtc) {
						$result->addselect($join_alias.'.'.$jtc.' as '.$join_alias.'_'.$jtc);
					}
				}

				$alias[] = $join_alias;
				$columns_table[$index]['type_data']	 = CRUDBooster::getFieldType($join_table,$join_column);
				$columns_table[$index]['field']      = $join_alias.'_'.$join_column;
				$columns_table[$index]['field_with'] = $join_alias.'.'.$join_column;
				$columns_table[$index]['field_raw']  = $join_column;

				@$join_table1  = $join_exp[2];
				@$joinTable1PK = CB::pk($join_table1);
				@$join_column1 = $join_exp[3];
				@$join_alias1  = $join_table1;

				if($join_table1 && $join_column1) {

					if(in_array($join_table1, $join_table_temp)) {
						$join_alias_count += 1;
						$join_alias1 = $join_table1.$join_alias_count;
					}

					$join_table_temp[] = $join_table1;

					$result->leftjoin($join_table1.' as '.$join_alias1,$join_alias1.'.'.$joinTable1PK,'=',$join_alias.'.'.$join_column);
					$result->addselect($join_alias1.'.'.$join_column1.' as '.$join_column1.'_'.$join_alias1);
					$alias[] = $join_alias1;
					$columns_table[$index]['type_data']	 = CRUDBooster::getFieldType($join_table1,$join_column1);
					$columns_table[$index]['field']      = $join_column1.'_'.$join_alias1;
					$columns_table[$index]['field_with'] = $join_alias1.'.'.$join_column1;
					$columns_table[$index]['field_raw']  = $join_column1;
				}

			}else{

				$result->addselect($table.'.'.$field);
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
		$response = $this->searchGigya($row->UID,$row->email);
		$results = $response['results'];
		// dd($row->email);
		$UID = $results[0]['UID'];
		$email = $row->email;
		$profile = $results[0]['profile'];
		// $child = $results[0]['data']['child'];
		// $interest = $results[0]['data']['areaOfInterest'];
		$mobileNumber = $profile['phones']['number'];
		foreach ($profile as $key => $value) {
			if($key == 'phones'){
				$row->phones = $mobileNumber;
			} else{
				$row->$key = $profile[$key];
			}
		}
/*
		DB::table('gigya_child')->where('customerid', '=', $id)->delete();

		if(!is_null($child)){
			try {			

				foreach($child as $child2)
				{
					
					if (!isset($child2['customerid'])) {
					      $child2["customerid"] = intval($id);
					};
					
					//dd($child2);
					if (isset($child2['areaOfInterest'])) {
						$child2['interestCode'] = $child2['areaOfInterest']['interestCode'];
						$child2['answerDetails'] = $child2['areaOfInterest']['answerDetails'];
						unset($child2['areaOfInterest']);
						// dump($child2);
					}
					
					DB::table("gigya_child")->insert([
	                            $child2
	                ]);

				}
			}
			catch (\Exception $e)
			{
				if (!isset($child['customerid'])) {
					$child["customerid"] = $id;
				}
				DB::table("gigya_child")->insert([
                            $child
                ]);
			}
		}

		DB::table('gigya_area_interest')->where('customerid', '=', $id)->delete();

		if(!is_null($interest)){
			try {			
				foreach($interest as $interest2)
				{

					if (!isset($interest2['customerid']))
						$interest2["customerid"] = $id;
					
					DB::table("gigya_area_interest")->insert([
	                            $interest2
	                ]);

				}
			}
			catch (\Exception $e)
			{
				if (!isset($interest['customerid']))
					$interest["customerid"] = $id;
				DB::table("gigya_area_interest")->insert([
                            $interest
                ]);
			}
		}
*/

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

	public function getDetail($id)	{
		$this->cbLoader();
		$row        = DB::table($this->table)->where($this->primary_key,$id)->first();
		$response = $this->search($row->UID,$row->email);
		$results = $response['results'];
		$UID = $results[0]['UID'];
		$email = $row->email;
		$profile = $results[0]['profile'];
		$mobileNumber = $profile['phones']['number'];
		foreach ($profile as $key => $value) {
			if($key == 'phones'){
				$row->phones = $mobileNumber;
			} else{
				$row->$key = $profile[$key];
			}
		}
		if(!CRUDBooster::isRead() && $this->global_privilege==FALSE || $this->button_detail==FALSE) {
			CRUDBooster::insertLog(trans("crudbooster.log_try_view",['name'=>$row->{$this->title_field},'module'=>CRUDBooster::getCurrentModule()->name]));
			CRUDBooster::redirect(CRUDBooster::adminPath(),trans('crudbooster.denied_access'));
		}

		$module     = CRUDBooster::getCurrentModule();

		$page_menu  = Route::getCurrentRoute()->getActionName();
		$page_title = trans("crudbooster.detail_data_page_title",['module'=>$module->name,'name'=>$row->{$this->title_field}]);
		$command    = 'detail';

		Session::put('current_row_id',$id);

		return view('crudbooster::default.form',compact('row','page_menu','page_title','command','id'));
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
				$count_input_data = count(Request::get($name.'-'.$columns[0]['name']))-1;
				$child_array = [];
				$childtable = CRUDBooster::parseSqlTable($ro['table'])['table'];				
				$fk = $ro['foreign_key'];
				$childtablePK = CB::pk($childtable);

				$colMatch = array();
				$row2 = (array) $row;
				foreach ($this->col as $key => $value) {
					$val = $value['name'];
					$colMatch[] = $val;

				}

				$matchRow = [];
				foreach ($colMatch as $key => $field) {
					if(array_key_exists($field, $row2)){
						$matchRow[$field] = $row2[$field];
					}
				}

				// dd($newArray2,$colMatch,$row2);

				for($i=0;$i<=$count_input_data;$i++) {
					
					$column_data = [];
					// $column_data[$childtablePK] = $lastId;
					$column_data[$fk] = $id;
					foreach($columns as $col) {
						$colname = $col['name'];
						$column_data[$colname] = Request::get($name.'-'.$colname)[$i];
					}

					$child_array[] = $column_data;

					if($child_array[$i]['id'] == NULL){
						
						if($childtable == 'mainmerge') {
						$customer_array[] = $matchRow;
						$test = (array) $customer_array[$i];
						foreach($child_array as $key => $value)
						{
							$newArray = array_merge($child_array[$key],$test);
						}
						// dd($newArray);
						unset($newArray['id']);
						$newArray['mobileno'] = $newArray['phones'];
						$newArray['postcode'] = $newArray['zip'];
						$remove_array = ['phones','zip','careline_max_datecreated','careline_callstatus','careline_currentstatus','careline_telecomaction','mainmerge_max_mdate'];
						$newArray = array_diff_key($newArray, array_flip($remove_array));
						// dd($newArray);
						$lastId = CRUDBooster::newId($childtable);
						$newArray['id'] = $lastId;
						date_default_timezone_set("Asia/Kuala_Lumpur");
						$date = date('Y-m-d H:i:s');
						$newArray['m_date'] = $date;

						DB::table($childtable)->insert($newArray);
						}
						else {
							// dd($child_array);
							unset($child_array['id']);
							$lastId = CRUDBooster::newId($childtable);
							$child_array[$i]['id'] = $lastId;
							DB::table($childtable)->insert($child_array);
						}

					}
					// dd($child_array);
					$tempId[] = $child_array[$i]['id'];
					unset($child_array[$i]['id']);

					DB::table($childtable) 
					->where('id', $tempId[$i])
					->update($child_array[$i]);

				}
				// dump($tempId);	

			}

		}//end foreach
		$removeKeys = array("UID","children", "sample_request", "careline_detail","area_of_interest","pregnant","pregnantstage","pregnancyweek","pregnantremarks","currentlybreastfeeding","maternalmilkbrand","userid","mother");
		foreach ($removeKeys as $key) {
			unset($setInputData[$key]);
		}

		// $childArray = $childData[0];
		// unset($childArray['customerid']);

		// $areaInterestData = $areaInterestData[0];
		// unset($areaInterestData['customerid']);

		// dd($childArray);
		// dd($UID,$setInputData,$childArray,$areaInterestData);

		if(strpos(CRUDBooster::mainpath(), 'admin/gigyacustomer') !== false){
			$mainmergeDate = DB::table('mainmerge')->where('customer_id',$id)->max('m_date');
			$carelineDateCreated = DB::table('careline')->where('customer_id',$id)->max('date_created'); 
			$carelineData = DB::table('careline')->select('callstatus','currentstatus','date_created','telecomaction')->where('customer_id',$id)->where('date_created','=',$carelineDateCreated)->first();
			$this->arr['careline_max_datecreated'] = $carelineData->date_created;
			$this->arr['careline_callstatus'] = $carelineData->callstatus;
			$this->arr['careline_currentstatus'] = $carelineData->currentstatus;
			$this->arr['careline_telecomaction'] = $carelineData->telecomaction;
			$this->arr['mainmerge_max_mdate'] = $mainmergeDate;
		}

		DB::table($this->table)->where($this->primary_key,$id)->update($this->arr);		
		
		$this->updateCustomerRecord($UID,$setInputData);

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

    public function searchGigya($UID,$email)
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

	public function updateCustomerRecord($UID, $setInputData)
    {
    	$state = DB::table('state')->select('name')->where('id','=',$setInputData['state'])->first();
    	$state = (array) $state;
    	$setInputData['state'] = $state['name'];

    	$method = "accounts.initRegistration";

    	// $request = new GSRequest($apiKey,$secretKey,$method);
    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);

    	//$request->setParam("query","select * from accounts LIMIT 50");
    	$request->setParam("isLite",true);
    	$request->setParam("callback","testcall");
    	// $request->setParam("openCursor",true);

    	$response = $request->send();
    	// dump($response);
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

			/*

			$childItems = DB::table('gigya_child')->where('customerid',$parentid)->get();

	    	
	    	$childData = [];
	    	$ci = 0;
			foreach ($childItems as $childItem) {
			    $childData[$ci]['firstName'] = $childItem->firstName;
		    	$childData[$ci]['birthDate'] = $childItem->birthDate;
		    	$childData[$ci]['birthDateReliability'] = $childItem->birthDateReliability;
		    	if($childData[$ci]['birthDateReliability'] == 4){
		    		$childData[$ci]['feeding'] = $childItem->feeding;
		    	}
		    	elseif($childData[$ci]['birthDateReliability'] == 0){
		    		$childData[$ci]['areaOfInterest']['interestCode'] = $childItem->interestCode;
		    		$childData[$ci]['areaOfInterest']['answerDetails'] = $childItem->answerDetails;
		    	}
		    	$ci++;
			}

			$interestItems = DB::table('gigya_area_interest')->where('customerid',$parentid)->get();


	    	$child["child"] = $childData;



	    	$interestData=[];
	    	$ci = 0;

	    	foreach ($interestItems as $interestItem) {
			    $interestData[$ci]['interestCode'] = $interestItem->interestCode;
		    	$interestData[$ci]['answerDetails'] = $interestItem->answerDetails;
		    	$ci++;
			}

			$child["areaOfInterest"] = $interestData;*/


	    	$method = "accounts.search";
	    	// $method = "accounts.getAccountInfo";
	    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);
	    	$request->setParam("query","SELECT * FROM emailAccounts WHERE UID='$UID'");
	    	// $request->setParam("UID",$UID);
	    	$response = $request->send();
	    	// dump($response);
	    	if($response->getErrorCode()==0)
		   	{
	            $response = $response->getResponseText();
	            $response = json_decode($response, true);
	            
	            	$method2 = "accounts.setAccountInfo";
	            	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method2,null,true,$this->gigya_user_key);
	            	$request->setParam("regToken",$regtoken);
					// $request->setparam("data", json_encode($child));
					//dd(json_encode($child));
					//$request->setparam("data", json_encode($interestItem));
	            	$request->setParam("profile",json_encode($setInputData));

	            	$response = $request->send();
	            	// dump($response);	
	            	if($response->getErrorCode()==0)
	            	{
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

		$searchEmail = $this->searchEmailAccount($this->arr['email']);
		if($searchEmail == true) {

			// dd('email no exist');
			// dd($this->table);
			$this->arr[$this->primary_key] = $id = CRUDBooster::newId($this->table);
			// dd($this->arr);
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
						if($child_array[$i]['id'] == NULL) {
							
							$customer_array[] = $row;

							$test = (array) $customer_array[$i];

							foreach($child_array as $key => $value)
							{
								$newArray = array_merge($child_array[$key],$test);
							}

							unset($child_array['id']);
							$lastId = CRUDBooster::newId($childtable);
							// dump($lastId);
							$child_array[$i]['id'] = $lastId;

							if($childtable == 'gigya_child'){
								foreach ($child_array as $key) {
					                if(strpos($key['birthDateReliability'], 'Pregnant') !== false){
					                	$child_array[$i]['birthDateReliability'] = 4;
					                } elseif ($key['birthDateReliability'] == 'Child is Born') {
					                	$child_array[$i]['birthDateReliability'] = 0;
					                }
					            }
					        }
					        // dump($child_array[$i]);
							if($childtable == 'gigya_child'){
					        	$childArray[$i] = $child_array[$i];
							}
							DB::table($childtable)->insert($child_array[$i]);
						} 
						
					}

					// dump($childArray);
					$currentids = array_pluck($child_array,"id");
					
					$newids =  DB::table($childtable)->where($fk,'=',$id)->pluck('id')->toArray();
					
					$array3 = array_diff($newids,$currentids);

					if (isset($array3))
					{
						DB::table($childtable)
						->whereIn('id', $array3)
						->delete();
					}}
				
			}
			// die();
			// dd($childArray);
			$this->createCustomerRecord($setInputData,$childArray,$areaInterestData);

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

		} //searchemail
		else {
			return back()->with('status', 'Email '.$this->arr['email'].' already exists in Gigya');
		}
	}

	public function createCustomerRecord($setInputData,$childArray,$areaInterestData)
    {
    	$email = $setInputData['email'];
    	$state = DB::table('state')->select('name')->where('id','=',$setInputData['state'])->first();
    	$state = (array) $state;
    	$setInputData['state'] = $state['name'];

    	$removeKeys = array("UID","children", "sample_request", "careline_detail","area_of_interest");
		foreach ($removeKeys as $key) {
			unset($setInputData[$key]);
		}

    	$method = "accounts.initRegistration";

    	// $request = new GSRequest($apiKey,$secretKey,$method);
    	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);

    	//$request->setParam("query","select * from accounts LIMIT 50");
    	$request->setParam("isLite",true);
    	$request->setParam("callback","testcall");
    	// $request->setParam("openCursor",true);

    	$response = $request->send();
    	// dump($response);
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

	    	$setInputData['phones'] = array("number"=>$setInputData['phones']);

	    	if($UID != NULL)
				$itemParent = DB::table('gigya_customer')->where('UID',$UID)->first();
			else
				$itemParent = DB::table('gigya_customer')->where('email',$email)->first();
			
			if (isset($itemParent))
				$parentid = $itemParent->id;

			$childItems = DB::table('gigya_child')->where('customerid',$parentid)->get();

	    	$childData = [];
	    	$ci = 0;
			foreach ($childItems as $childItem) {
			    $childData[$ci]['firstName'] = $childItem->firstName;
		    	$childData[$ci]['birthDate'] = $childItem->birthDate;
		    	$childData[$ci]['birthDateReliability'] = $childItem->birthDateReliability;
		    	if($childData[$ci]['birthDateReliability'] == 4){
		    		$childData[$ci]['feeding'] = $childItem->feeding;
		    	}
		    	elseif($childData[$ci]['birthDateReliability'] == 0){
		    		$childData[$ci]['areaOfInterest']['interestCode'] = $childItem->interestCode;
		    		$childData[$ci]['areaOfInterest']['answerDetails'] = $childItem->answerDetails;
		    	}
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


			$child["areaOfInterest"] = $interestData;

        	$method2 = "accounts.setAccountInfo";
        	$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method2,null,true,$this->gigya_user_key);
        	$request->setParam("regToken",$regtoken);
			$request->setparam("data", json_encode($child));
        	$request->setParam("profile",json_encode($setInputData));

        	$response = $request->send();
        	// dump($response);
        	usleep(500000);
        	if($response->getErrorCode()==0)
        	{
        	    // echo "Success";
        	    $response = $response->getResponseText();
        	    $response = json_decode($response, true);
        	    $method = "accounts.search";
        	    // $method = "accounts.getAccountInfo";
        	    $request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);
        	    $request->setParam("query","SELECT UID FROM emailAccounts WHERE profile.email='$email'");

        	    $response = $request->send();
        	    // dd($response);
        	    if($response->getErrorCode()==0)
        	    {
        	        // echo "Success";
        	        $response = $response->getResponseText();
        	        $response = json_decode($response, true);
        	        
        	        $UID = $response['results'][0]['UID'];
        	        // dd($UID);
        	        DB::table('gigya_customer')
		                ->where('email', $email)
		                ->update(['UID' => $UID]);
        	        // echo "<pre>".print_r($response,TRUE)."</pre>\n";
        	        // echo $reg['email'];

        	    }
        	    else
        	    {	
        	    	if($response->getErrorCode()==500001)
        	    		abort(500, 'General Server Error');
        	    	
        	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
        	        error_log($response->getLog());
        	    }


        	    // echo "<pre>".print_r($response,TRUE)."</pre>\n";
        	    // echo $reg['email'];

        	}
        	else
        	{	
        		if($response->getErrorCode()==500001)
        			abort(500, 'General Server Error');
        		
        	    echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
        	    error_log($response->getLog());
        	}
    	}
	}

	public function searchEmailAccount($email)
	{
		$method = "accounts.search";
		$request = new GSRequest($this->gigya_api_key,$this->gigya_secret_key,$method,null,true,$this->gigya_user_key);
		$request->setParam("query","SELECT * FROM emailAccounts WHERE profile.email='$email'");
		$response = $request->send();
		if($response->getErrorCode()==0)
	    {
	    	$response = $response->getResponseText();
	    	$response = json_decode($response, true);

	    	if (!empty($response['results'])) {
	    		return false;
	    	} else {
	    		return true;	
	    	}
	    	
	    }
	    else
	    {
	        echo ("Uh-oh, we got the following error: " . $response->getErrorMessage());
	        error_log($response->getLog());
	    }
	}

	public function hook_before_addscreen() {

	}

	public function hook_child_query($child_array) {

	}

}


