@extends('crudbooster::admin_template')
@section('content')


            @if($button_show_data || $button_reload_data || $button_new_data || $button_delete_data || $index_button || $columns)
            <div id='box-actionmenu' class='box'>
              <div class='box-body'>
                 @include("crudbooster::default.actionmenu")
              </div>
            </div>
            @endif


            @if(Request::get('file') && Request::get('import'))

            <ul class='nav nav-tabs'>
                    <li style="background:#eeeeee"><a style="color:#111" onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'" href='javascript:;'><i class='fa fa-download'></i> Upload a File &raquo;</a></li>
                    <li style="background:#eeeeee" ><a style="color:#111" href='#'><i class='fa fa-cogs'></i> Adjustment &raquo;</a></li>
                    <li style="background:#ffffff"  class='active'><a style="color:#111" href='#'><i class='fa fa-cloud-download'></i> Importing &raquo;</a></li>
            </ul>

            <!-- Box -->
            <div id='box_main' class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Importing</h3>
                    <div class="box-tools">                      
                    </div>
                </div>
                    
                <div class="box-body">
                    
                    <p style='font-weight: bold' id='status-import'><i class='fa fa-spin fa-spinner'></i> Please wait importing...</p>
                    <div class="progress">
                      <div id='progress-import' class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="40" 
                      aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        <span class="sr-only">40% Complete (success)</span>
                      </div>
                    </div>                    

                    <script type="text/javascript">
                      $(function() {
                        var total = {{ intval(Session::get('total_data_import')) }};
                        
                        var int_prog = setInterval(function() {

                          $.post("{{ CRUDBooster::mainpath('do-import-chunk?file='.Request::get('file')) }}",{resume:1},function(resp) {                                       
                              console.log(resp.progress);
                              $('#progress-import').css('width',resp.progress+'%');
                              $('#status-import').html("<i class='fa fa-spin fa-spinner'></i> Please wait importing... ("+resp.progress+"%)");
                              $('#progress-import').attr('aria-valuenow',resp.progress);
                              if(resp.progress >= 100) {
                                $('#status-import').addClass('text-success').html("<i class='fa fa-check-square-o'></i> Import Data Completed !");
                                clearInterval(int_prog);
                              }
                          })
                        },2500);

                        $.post("{{ CRUDBooster::mainpath('do-import-chunk').'?file='.Request::get('file') }}",function(resp) {
                            if(resp.status==true) {
                              $('#progress-import').css('width','100%');
                              $('#progress-import').attr('aria-valuenow',100);
                              $('#status-import').addClass('text-success').html("<i class='fa fa-check-square-o'></i> Import Data Completed !");
                              clearInterval(int_prog);
                              $('#upload-footer').show();
                              console.log('Import Success');
                            }
                        })

                      })

                    </script>

                </div><!-- /.box-body -->
        
                <div class="box-footer" id='upload-footer' style="display:none">  
                  <div class='pull-right'>                            
                      <a href='{{ CRUDBooster::mainpath("import-data") }}' class='btn btn-default'><i class='fa fa-upload'></i> Upload Other File</a> 
                      <a href='{{CRUDBooster::mainpath()}}' class='btn btn-success'>Finish</a>                                
                  </div>
                </div><!-- /.box-footer-->
                
            </div><!-- /.box -->
            @endif

            @if(Request::get('file') && !Request::get('import'))

            <ul class='nav nav-tabs'>
                    <li style="background:#eeeeee"><a style="color:#111" onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'" href='javascript:;'><i class='fa fa-download'></i> Upload a File &raquo;</a></li>
                    <li style="background:#ffffff"  class='active'><a style="color:#111" href='#'><i class='fa fa-cogs'></i> Adjustment &raquo;</a></li>
                    <li style="background:#eeeeee"><a style="color:#111" href='#'><i class='fa fa-cloud-download'></i> Importing &raquo;</a></li>
            </ul>

            <!-- Box -->
            <div id='box_main' class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Adjustment</h3>
                    <div class="box-tools">
                                          
                    </div>
                </div>
        
                  <?php           
                    if($data_sub_module) {
                      $action_path = Route($data_sub_module->controller."GetIndex");
                    }else{
                      $action_path = CRUDBooster::mainpath();
                    }            

                    $action = $action_path."/done-import?file=".Request::get('file').'&import=1';
                  ?>

                <form method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">             
                        <div class="box-body table-responsive no-padding">
                              <div class='callout callout-info' style="font-size: 16px;">
                                @if(CRUDBooster::myPrivilegeId()==2 || CRUDBooster::myPrivilegeId()==6)
                                 * This feature is only for updating consignment numbers <br>
                                 * Please match ALL column dropdowns with their respective headers. Otherwise, the import will fail <br>
                                @else
                                  * Just ignore the column where you are not sure the data is suit with the column or not.<br/>
                                  * Warning !, Unfortunately at this time, the system can't import column that contains image or photo url.
                                @endif
                              </div>
                              <style type="text/css">
                                th, td {
                                    white-space: nowrap;
                                }                                
                              </style>
                              <table class='table table-bordered' style="width:130%">
                                  <thead>
                                      <tr class='info'>
                                          @foreach($table_columns as $k=>$column)
                                            <?php
                                              $help = ''; 
                                              if($column == 'id' || $column == 'created_at' || $column == 'updated_at' || $column == 'deleted_at') continue;
                                              if(substr($column,0,3) == 'id_') {
                                                $relational_table = substr($column, 3);
                                                $help = "<a href='#' title='This is foreign key, so the System will be inserting new data to table `$relational_table` if doesn`t exists'><strong>(?)</strong></a>";
                                              }
                                            ?>
                                            @if(CRUDBooster::myPrivilegeId()==1)
                                              {{-- <th data-no-column='{{$k}}'>{{ $column }} {!! $help !!}</th> --}}
                                              @if( $column == 'firstname' || $column == 'lastname' || $column == 'mail_address' || $column == 'tracking_number' || $column == 'email' || $column == 'mobileno' || $column == 'm_product' || $column == 'childdob' || $column == 'childname' || $column == 'm_date') 
                                                @if($column == "firstname")
                                                  <th data-no-column='{{$k}}'>First Name{!! $help !!}</th>
                                                @elseif($column == "lastname")
                                                  <th data-no-column='{{$k}}'>Last Name{!! $help !!}</th>
                                                @elseif($column == "mail_address")
                                                  <th data-no-column='{{$k}}'>Mail Address{!! $help !!}</th>
                                                @elseif($column == "tracking_number")
                                                  <th data-no-column='{{$k}}'>Tracking Number{!! $help !!}</th>
                                                @elseif($column == "m_product")
                                                  <th data-no-column='{{$k}}'>Product Name{!! $help !!}</th>
                                                @elseif($column == "m_date")
                                                  <th data-no-column='{{$k}}'>Date Request{!! $help !!}</th>
                                                @elseif($column == "email")
                                                  <th data-no-column='{{$k}}'>Email{!! $help !!}</th>
                                                @elseif($column == "mobileno")
                                                  <th data-no-column='{{$k}}'>Mobile Number{!! $help !!}</th>
                                                @elseif($column == "childname")
                                                  <th data-no-column='{{$k}}'>Child Name{!! $help !!}</th>
                                                @elseif($column == "childdob")
                                                  <th data-no-column='{{$k}}'>Child DOB{!! $help !!}</th>
                                                @endif
                                              @endif
                                            @elseif(CRUDBooster::myPrivilegeId()==2)
                                              @if( $column == 'm_product' || $column == 'firstname' || $column == 'lastname' || $column == 'email' || $column == 'mail_address' || $column == 'tracking_number'|| $column == 'address1' || $column == 'address2' || $column == 'postcode' || $column == 'city' || $column =='state' || $column == 'mobileno' || $column == 'childname' || $column == 'childdob' || $column == 'maternalmilkbrand' || $column == 'm_source')
                                                @if($column == "m_product")
                                                  <th data-no-column='{{$k}}'>Product Name{!! $help !!}</th> 
                                                @elseif($column == "firstname")
                                                  <th data-no-column='{{$k}}'>First Name{!! $help !!}</th>
                                                @elseif($column == "lastname")
                                                  <th data-no-column='{{$k}}'>Last Name{!! $help !!}</th>
                                                @elseif($column == "email")
                                                  <th data-no-column='{{$k}}'>Email{!! $help !!}</th>
                                                @elseif($column == "mail_address")
                                                  <th data-no-column='{{$k}}'>Mail Address{!! $help !!}</th>
                                                @elseif($column == "tracking_number")
                                                  <th data-no-column='{{$k}}'>Tracking Number{!! $help !!}</th>
                                                @elseif($column == "address1")
                                                  <th data-no-column='{{$k}}'>Address 1{!! $help !!}</th>
                                                @elseif($column == "address2")
                                                  <th data-no-column='{{$k}}'>Address 2{!! $help !!}</th>
                                                @elseif($column == "postcode")
                                                  <th data-no-column='{{$k}}'>Postcode{!! $help !!}</th>
                                                @elseif($column == "city")
                                                  <th data-no-column='{{$k}}'>City{!! $help !!}</th>
                                                @elseif($column == "state")
                                                  <th data-no-column='{{$k}}'>State{!! $help !!}</th>
                                                @elseif($column == "mobileno")
                                                  <th data-no-column='{{$k}}'>Mobile Number{!! $help !!}</th>
                                                @elseif($column == "childname")
                                                  <th data-no-column='{{$k}}'>Child Name{!! $help !!}</th>
                                                @elseif($column == "childdob")
                                                  <th data-no-column='{{$k}}'>Child DOB{!! $help !!}</th>
                                                @elseif($column == "maternalmilkbrand")
                                                  <th data-no-column='{{$k}}'>Maternal Milk Brand{!! $help !!}</th>
                                                @elseif($column == "m_source")
                                                  <th data-no-column='{{$k}}'>Source{!! $help !!}</th>
                                                @endif
                                              @endif
                                            @else
                                              @if( $column == 'firstname' || $column == 'lastname' || $column == 'mail_address' || $column == 'tracking_number' || $column == 'email' || $column == 'mobileno' || $column == 'address1' || $column == 'brand_source' || $column == 'm_product' || $column == 'childdob' || $column == 'childname' || $column == 'm_date' || $column == 'consigmentno') 
                                                @if($column == "m_product")
                                                  <th data-no-column='{{$k}}'>Product Name{!! $help !!}</th>
                                                @elseif($column == "m_date")
                                                  <th data-no-column='{{$k}}'>Date Request{!! $help !!}</th>
                                                @elseif($column == "email")
                                                  <th data-no-column='{{$k}}'>Email{!! $help !!}</th>
                                                @elseif($column == "mobileno")
                                                  <th data-no-column='{{$k}}'>Mobile Number{!! $help !!}</th>
                                                @elseif($column == "childname")
                                                  <th data-no-column='{{$k}}'>Child Name{!! $help !!}</th>
                                                @elseif($column == "childdob")
                                                  <th data-no-column='{{$k}}'>Child DOB{!! $help !!}</th>
                                                @elseif($column == "consigmentno")
                                                  <th data-no-column='{{$k}}'>Consignment Number{!! $help !!}</th>
                                                @elseif($column == "firstname")
                                                  <th data-no-column='{{$k}}'>First Name{!! $help !!}</th>
                                                @elseif($column == "lastname")
                                                  <th data-no-column='{{$k}}'>Last Name{!! $help !!}</th>
                                                @elseif($column == "mail_address")
                                                  <th data-no-column='{{$k}}'>Mail Address{!! $help !!}</th>
                                                @elseif($column == "tracking_number")
                                                  <th data-no-column='{{$k}}'>Tracking Number{!! $help !!}</th>
                                                @elseif($column == "address1")
                                                  <th data-no-column='{{$k}}'>Address{!! $help !!}</th>
                                                @elseif($column == "brand_source")
                                                  <th data-no-column='{{$k}}'>Brand Source{!! $help !!}</th>
                                                @elseif($column == "mobileno")
                                                  <th data-no-column='{{$k}}'>Mobile Number{!! $help !!}</th>
                                                @endif
                                              @endif
                                            @endif
                                          @endforeach
                                      </tr>                                      
                                  </thead>
                                  <tbody>
                                        <tr>
                                        @foreach($table_columns as $k=>$column)
                                            <?php if($column == 'id' || $column == 'created_at' || $column == 'updated_at' || $column == 'deleted_at') continue;?>

                                            @if(CRUDBooster::myPrivilegeId()==1)
                                              @if( $column == 'firstname' || $column == 'lastname' || $column == 'mail_address' || $column == 'tracking_number' || $column == 'email' || $column == 'mobileno' || $column == 'm_product' || $column == 'childdob' || $column == 'childname' || $column == 'm_date') 
                                                <td data-no-column='{{$k}}'>
                                                    <select style='width:120px' class='form-control select_column' name='select_column[{{$k}}]'>
                                                        <option value=''>** Set Column for {{$column}}</option>
                                                        @foreach($data_import_column as $import_column)
                                                        <option value='{{$import_column}}'>{{$import_column}}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                              @endif
                                            @elseif(CRUDBooster::myPrivilegeId()==2)
                                             @if( $column == 'm_product' || $column == 'firstname' || $column == 'lastname' || $column == 'email' || $column == 'mail_address' || $column == 'tracking_number'|| $column == 'address1' || $column == 'address2' || $column == 'postcode' || $column == 'city' || $column =='state' || $column == 'mobileno' || $column == 'childname' || $column == 'childdob' || $column == 'maternalmilkbrand' || $column == 'm_source')
                                                <td data-no-column='{{$k}}'>
                                                    <select style='width:120px' class='form-control select_column' name='select_column[{{$k}}]'>
                                                        <option value=''>** Set Column for {{$column}}</option>
                                                        @foreach($data_import_column as $import_column)
                                                        <option value='{{$import_column}}'>{{$import_column}}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                              @endif
                                            @else
                                              @if( $column == 'firstname' || $column == 'lastname' || $column == 'mail_address' || $column == 'tracking_number' || $column == 'email' || $column == 'mobileno' || $column == 'm_product' || $column == 'childdob' || $column == 'childname' || $column == 'm_date' || $column == 'consigmentno' || $column == 'address1' || $column == 'brand_source') 
                                                <td data-no-column='{{$k}}'>
                                                    <select style='width:120px' class='form-control select_column' name='select_column[{{$k}}]'>
                                                        <option value=''>** Set Column for {{$column}}</option>
                                                        @foreach($data_import_column as $import_column)
                                                        <option value='{{$import_column}}'>{{$import_column}}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                              @endif
                                            @endif
                                        @endforeach
                                        </tr>
                                  </tbody>
                              </table>


                        </div><!-- /.box-body -->

                        <script type="text/javascript">
                          $(function(){    
                              var total_selected_column = 0;                      
                              setInterval(function() {
                                  total_selected_column = 0;
                                  $('.select_column').each(function() {
                                      var n = $(this).val();
                                      if(n) total_selected_column = total_selected_column + 1;
                                  })
                              },200);                              
                          })
                          function check_selected_column() {
                              var total_selected_column = 0;
                              $('.select_column').each(function() {
                                  var n = $(this).val();
                                  if(n) total_selected_column = total_selected_column + 1;
                              })
                              // if(total_selected_column < 7) {
                              //   swal("Oops...", "Please fill up all the columns", "error");
                              //   return false;
                              // }else{
                              //   return true;
                              // }

                              return true;
                          }
                        </script>
                
                        <div class="box-footer">  
                          <div class='pull-right'>                            
                              <a onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'" href='javascript:;' class='btn btn-default'>Cancel</a>  
                              <input type='submit' class='btn btn-primary' name='submit' onclick='return check_selected_column()' value='Import Data'/>   
                          </div>
                        </div><!-- /.box-footer-->
                </form>
            </div><!-- /.box -->


            @endif

            @if(!Request::get('file'))
            <ul class='nav nav-tabs'>
                    <li style="background:#ffffff" class='active'><a style="color:#111" onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'" href='javascript:;'><i class='fa fa-download'></i> Upload a File &raquo;</a></li>
                    <li style="background:#eeeeee"><a style="color:#111" href='#'><i class='fa fa-cogs'></i> Adjustment &raquo;</a></li>
                    <li style="background:#eeeeee"><a style="color:#111" href='#'><i class='fa fa-cloud-download'></i> Importing &raquo;</a></li>
            </ul>

            <!-- Box -->
            <div id='box_main' class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Upload a File</h3>
                    <div class="box-tools">
                                          
                    </div>
                </div>
        
                  <?php           
                    if($data_sub_module) {
                      $action_path = Route($data_sub_module->controller."GetIndex");
                    }else{
                      $action_path = CRUDBooster::mainpath();
                    }            

                    $action = $action_path."/do-upload-import-data";
                  ?>

                <form method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">             
                        <div class="box-body">

                            <div class='callout callout-success' style="font-size: 16px;">
                                  @if(CRUDBooster::myPrivilegeId()==6)
                                    <h4>Welcome to the Consignment No. Bulk Update Tool</h4>
                                    Please read the instructions below before using this tool: <br/>
                                    * File format should be : <b>csv</b><br/>
                                    * Please limit file sizes to a maximum of 5MB. Split large files into smaller parts of necessary<br/>
                                    * Please only use this tool after 7pm on Wednesday and Friday only to maintain data integrity<br/>
                                    * Please follow the template found <a href="{{'/public/photos/upload-example.png'}}" target="_blank">here</a><br>
                                    * Make sure the date/time format follows the correct format as shown in the template <br>
                                    * Please only include customers who are already inside the 'Offline' tab in Smart Data for this process
                                  @else
                                    <h4>Welcome to Data Importer Tool</h4>
                                    Before uploading a file, please read the instructions below.<br/>
                                    * When filling in data, you should use the template previously provided.<br/>
                                    * Due to timeout concerns, please split your data into a maximum of 1000 rows each per upload.<br/>
                                    * Please be sure to map the data correctly once the file has been attached and you have clicked the 'Upload' button.<br/>                                    
                                  @endif
                              </div>

                            <div class='form-group'>
                                <label>File CSV</label>
                                <input type='file' name='userfile' class='form-control' required />
                                <div class='help-block'>File type supported only : CSV</div>
                            </div>
                        </div><!-- /.box-body -->
                
                        <div class="box-footer">  
                          <div class='pull-right'>                            
                              <a href='{{ CRUDBooster::mainpath() }}' class='btn btn-default'>Cancel</a>  
                              <input type='submit' class='btn btn-primary' name='submit' value='Upload'/>   
                          </div>
                        </div><!-- /.box-footer-->
                </form>
            </div><!-- /.box -->


             @endif
        </div><!-- /.col -->


    </div><!-- /.row -->

@endsection