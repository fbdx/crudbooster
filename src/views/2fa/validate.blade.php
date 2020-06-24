<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.page_title_login")}} : {{CRUDBooster::getSetting('appname')}}</title>
    <meta name='generator' content='CRUDBooster'/>
    <meta name='robots' content='noindex,nofollow'/>
    <link rel="shortcut icon" href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">

    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.3.2 -->
    <link href="{{asset('vendor/crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="{{asset('vendor/crudbooster/assets/adminlte/dist/css/AdminLTE.min.css')}}" rel="stylesheet" type="text/css" />

    <!-- support rtl-->
    @if (App::getLocale() == 'ar')
      <link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/v3.3.4/dist/css/bootstrap-rtl.min.css">
      <link href="{{ asset("vendor/crudbooster/assets/rtl.css")}}" rel="stylesheet" type="text/css" />
    @endif

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    <link rel='stylesheet' href='{{asset("vendor/crudbooster/assets/css/main.css")}}'/>
    <style type="text/css">
      .login-page, .register-page {
          background: {{ CRUDBooster::getSetting("login_background_color")?:'#dddddd'}} url('{{ CRUDBooster::getSetting("login_background_image")?asset(CRUDBooster::getSetting("login_background_image")):asset('vendor/crudbooster/assets/bg_blur3.jpg') }}');
          color: {{ CRUDBooster::getSetting("login_font_color")?:'#ffffff' }} !important;
          background-repeat: no-repeat;
          background-position: center;
          background-size: cover;
      }
      .login-box, .register-box {
        margin: 2% auto;
      }
      .login-box-body {
        box-shadow: 0px 0px 50px rgba(0,0,0,0.8);              
        background: rgba(255,255,255,0.9);
        color: {{ CRUDBooster::getSetting("login_font_color")?:'#666666' }} !important;
      }
      html,body {
        overflow: hidden;
      }
    </style>
  </head>

  <body class="login-page">

    <div class="login-box">
      <div class="login-logo">
        <a href="{{url('/')}}">
            <img title='{!!(CRUDBooster::getSetting('appname') == 'CRUDBooster')?"<b>CRUD</b>Booster":CRUDBooster::getSetting('appname')!!}' src='{{ CRUDBooster::getSetting("logo")?asset(CRUDBooster::getSetting('logo')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}' style='max-width: 100%;max-height:170px'/>
        </a>
      </div><!-- /.login-logo -->      
      <div class="login-box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Google 2FA</div>

                    <div class="panel-body">
                        <form class="form-horizontal" role="form" method="POST" action="{{route('postValidateToken')}}">
                            {!! csrf_field() !!}

                            <div class="form-group">
                                <label class="col-md-12">One-Time Password</label>
                                <div class="col-md-12">
                                    <input type="number" class="form-control" name="totp" required>
                                    <input type="hidden" name="google2fa_secret" value="{{$secret}}">
                                    <input type="hidden" name="email" value="{{$email}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-btn fa-mobile">&nbsp;</i>Validate
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
      </div><!-- /.login-box-body -->

    </div><!-- /.login-box -->



    <!-- jQuery 3.5.0 -->
    <script src="{{asset('vendor/crudbooster/assets/adminlte/plugins/jQuery/jQuery-3.5.0.min.js')}}"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="{{asset('vendor/crudbooster/assets/adminlte/bootstrap/js/bootstrap.min.js')}}" type="text/javascript"></script> 
  </body>
</html>