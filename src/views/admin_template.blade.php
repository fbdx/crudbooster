<!DOCTYPE html>
<html>
<head>

    <?php header("Access-Control-Allow-Origin: *"); ?>
    <meta charset="UTF-8">
    <title>{{ ($page_title)?CRUDBooster::getSetting('appname').': '.strip_tags($page_title):"Admin Area" }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name='generator' content='CRUDBooster 5.3'/>
    <meta name='robots' content='noindex,nofollow'/>
    <link rel="shortcut icon" href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    @include('crudbooster::admin_template_plugins')

    <!-- Theme style -->
    <link href="{{ asset("vendor/crudbooster/assets/adminlte/dist/css/AdminLTE.min.css")}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset("vendor/crudbooster/assets/adminlte/dist/css/skins/_all-skins.min.css")}}" rel="stylesheet" type="text/css" />

    <!-- support rtl-->
    @if (App::getLocale() == 'ar')
        <link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/v3.3.4/dist/css/bootstrap-rtl.min.css">
        <link href="{{ asset("vendor/crudbooster/assets/rtl.css")}}" rel="stylesheet" type="text/css" />
    @endif

    <!-- load css -->
    <style type="text/css">
        @if($style_css)
            {!! $style_css !!}
        @endif
    </style>
    @if($load_css)
        @foreach($load_css as $css)
            <link href="{{$css}}" rel="stylesheet" type="text/css" />
        @endforeach
    @endif

    <!-- load js -->
    <script type="text/javascript">
      var site_url = "{{url('/')}}" ;
      @if($script_js)
        {!! $script_js !!}
      @endif
    </script>
    @if($load_js)
      @foreach($load_js as $js)
        <script src="{{$js}}"></script>
      @endforeach
    @endif
    <style type="text/css">
        .dropdown-menu-action {left:-130%;}
        .btn-group-action .btn-action {cursor: default}
        #box-header-module {box-shadow:10px 10px 10px #dddddd;}
        .sub-module-tab li {background: #F9F9F9;cursor:pointer;}
        .sub-module-tab li.active {background: #ffffff;box-shadow: 0px -5px 10px #cccccc}
        .nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover {border:none;}
        .nav-tabs>li>a {border:none;}
        .breadcrumb {
            margin:0 0 0 0;
            padding:0 0 0 0;
        }
        .form-group > label:first-child {display: block}

    </style>
</head>
<body class="@php echo (Session::get('theme_color'))?:'skin-blue'; echo config('crudbooster.ADMIN_LAYOUT') @endphp">
<div id='app' class="wrapper">

    <!-- Header -->
    @include('crudbooster::header')

    <!-- Sidebar -->
    @include('crudbooster::sidebar')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">

        <section class="content-header">
          <?php
            $module = CRUDBooster::getCurrentModule();
            $path = CRUDBooster::mainpath();
          ?>
          @if(Request::get('parent_id'))
            <?php 
              $parentId = Request::get('parent_id');
            ?>
          @endif
          @if($module)
          <h1>
            <i class='{{$module->icon}}'></i>  {{($page_title)?:$module->name}} &nbsp;&nbsp;

            <!--START BUTTON -->

            @if(CRUDBooster::getCurrentMethod() == 'getIndex')
            @if($button_show)
            <a href="{{ CRUDBooster::mainpath().'?'.http_build_query(Request::all()) }}" id='btn_show_data' class="btn btn-sm btn-primary" title="{{trans('crudbooster.action_show_data')}}">
              <i class="fa fa-table"></i> {{trans('crudbooster.action_show_data')}}
            </a>
            @endif

            @if($button_add && CRUDBooster::isCreate())
            <a href="{{ CRUDBooster::mainpath('add').'?return_url='.urlencode(Request::fullUrl()).'&parent_id='.g('parent_id').'&parent_field='.$parent_field }}" id='btn_add_new_data' class="btn btn-sm btn-success" title="{{trans('crudbooster.action_add_data')}}">
              <i class="fa fa-plus-circle"></i> {{trans('crudbooster.action_add_data')}}
            </a>
            @endif
            @endif

            @if($button_export && CRUDBooster::getCurrentMethod() == 'getIndex')
            <a href="javascript:void(0)" id='btn_export_data' data-url-parameter='{{$build_query}}' title='Export Data' class="btn btn-sm btn-primary btn-export-data">
              <i class="fa fa-upload"></i> {{trans("crudbooster.button_export")}}
            </a> 
            @endif

            @if($button_export_v2 && CRUDBooster::getCurrentMethod() == 'getIndex')
            <a href="javascript:void(0)" id='export_data_v2' data-url-parameter='' title='Export Data' class="btn btn-sm btn-primary btn-export-data-v2">
              <i class="fa fa-upload"></i>&nbsp;Export CSV (PPI hashed)
            </a>
            @endif


            @if($button_import && CRUDBooster::getCurrentMethod() == 'getIndex')
            <?php 
              $route = CRUDBooster::mainpath('import-data'); 
              if(isset($parentId))
              {
                $route = $route.'/'.$parentId.'?back_url='.urlencode(Request::fullUrl());
              }
            ?>
            <a href="{{ $route }}" id='btn_import_data' data-url-parameter='{{$build_query}}' title='Import Data' class="btn btn-sm btn-primary btn-import-data">
              <i class="fa fa-download"></i> {{trans("crudbooster.button_import")}}
            </a>
            @endif


@if($btn_imp_wyeth_cust_child && CRUDBooster::getCurrentMethod() == 'getIndex')
<button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#myModal"><i class="fa fa-upload"></i>&nbsp;Upload CSV</button>
  <div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
<form method="POST" action="{{route('backend.wyeth.import')}}" enctype="multipart/form-data">
{{csrf_field()}}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Import CSV File of WYETH Customers/Child</h4>
        </div>
        <div class="modal-body">
          <div class="form-content">
            <small>
              <div class="form-group">
                <input type="file" class="form-control-file" id="import_file" name="import_file">
              </div>
            </small>
          </div>
          <br />
          <div class="form-content">
              <div>
                <label for="a">
                  <small>
                    <input id="a" type="radio" name="import_to" checked value="TO_SAMPLE_REQUEST">&nbsp;Import as Sample Request
                  </small>
                </label>
              </div>
              <div>
                <label for="b">
                  <small>
                    <input id="b" type="radio" name="import_to" value="TO_CUSTOMER_CHILD">&nbsp;Import as Customers & Children
                  </small>
                </label>
              </div>
          </div>
        </div>
        <div class="modal-footer" id="uploading" style='display:none;text-align: center;'>
          <i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><br/>
          <small><strong style='color:#b51e1e'>IMPORTANT: Uploading in process. Please do not close or refresh the browser</strong></small>
        </div>
        <div class="modal-footer form-content" id="footer">
          <input type="submit" class="btn btn-success" value="Upload" onclick="$('.form-content').hide();$('#uploading').show()">
          <button type="button" class="btn btn-default" data-dismiss="modal">close</button>
        </div>
</form>
    </div>
  </div>
</div>
@endif
            <!--ADD ACTIon-->
             @if(count($index_button))

                    @foreach($index_button as $ib)
                     <a href='{{$ib["url"]}}' id='{{str_slug($ib["label"])}}' class='btn {{($ib['color'])?'btn-'.$ib['color']:'btn-primary'}} btn-sm'
                      @if($ib['onClick']) onClick='return {{$ib["onClick"]}}' @endif
                      @if($ib['onMouseOever']) onMouseOever='return {{$ib["onMouseOever"]}}' @endif
                      @if($ib['onMoueseOut']) onMoueseOut='return {{$ib["onMoueseOut"]}}' @endif
                      @if($ib['onKeyDown']) onKeyDown='return {{$ib["onKeyDown"]}}' @endif
                      @if($ib['onLoad']) onLoad='return {{$ib["onLoad"]}}' @endif
                      >
                        <i class='{{$ib["icon"]}}'></i> {{$ib["label"]}}
                      </a>
                    @endforeach
            @endif
            <!-- END BUTTON -->
          </h1>


          <ol class="breadcrumb">
            <li><a href="{{CRUDBooster::adminPath()}}"><i class="fa fa-dashboard"></i> {{ trans('crudbooster.home') }}</a></li>
            <li class="active">{{$module->name}}</li>
          </ol>
          @else
          <h1>{{CRUDBooster::getSetting('appname')}} <small>Information</small></h1>
          @endif
        </section>


        <!-- Main content -->
        <section id='content_section' class="content">

          @if(@$alerts)
            @foreach(@$alerts as $alert)
              <div class='callout callout-{{$alert[type]}}'>
                  {!! $alert['message'] !!}
              </div>
            @endforeach
          @endif


      @if (Session::get('message')!='')
      <div class='alert alert-{{ Session::get("message_type") }}'>
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><i class="icon fa fa-info"></i> {{ trans("crudbooster.alert_".Session::get("message_type")) }}</h4>
        {!!Session::get('message')!!}
      </div>
      @endif



            <!-- Your Page Content Here -->
            @yield('content')
        </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <!-- Footer -->
    @include('crudbooster::footer')

</div><!-- ./wrapper -->

<!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience -->
</body>
</html>
