@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>            	                		           
	<div class="panel panel-default">
      <div class="panel-heading">
        [name]
      </div>
      <div class="panel-body">
        [sql]
      </div>
    </div>
	<div class='action pull-right'>
    	<a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Customized Table' class='btn-edit-component'><i class='fa fa-pencil'></i></a> &nbsp;
    	<a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i class='fa fa-trash'></i></a>
    </div>
</div>
@elseif($command=='configuration')
	<form method='post'>
		<input type='hidden' name='_token' value='{{csrf_token()}}'/>
		<input type='hidden' name='componentid' value='{{$componentID}}'/>
		<div class="form-group">
			<label>Name</label>
			<input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}'/>
		</div>

		<div class="form-group">
			<label>Configuration</label>
			<textarea name='config[sql]' required rows="4" class='form-control'>{{@$config->sql}}</textarea>
			<div class="block-help"></div>
		</div>

		<div class="form-group">
			<label>Bar Area Name</label>
			<input class="form-control" required name='config[area_name]' type='text' value='{{@$config->area_name}}'/>
			<div class="block-help">You can name each line area. Write name separate with ;</div>
		</div>
	</form>
@elseif($command=='showFunction')
  @if($key == 'sql')
<?php 
$data = json_decode($value, TRUE);
$topLabels = $data[0]['top'];
$data = $data[1];
$leftLabels = array_keys($data);

?>
		<div id="container" style="width:100%;">
      <table class="table">
        <thead>
          <tr>
            <th scope="col" style="border:1px solid white;border-bottom: 2px solid #e0e4e8;width:2%">&nbsp;&nbsp;</th>
@foreach($topLabels as $k => $label)
<?php 
$cssStyle = '';
switch($k){
  case 1: $cssStyle = 'background-color:#f6f6ff';break;
  case 2: $cssStyle = 'background-color:#e9e9ff;';break;
  case 3: $cssStyle = 'background-color:#d7d7ff;';break;
  case 4: $cssStyle = 'background-color:#bbbbff;color: #f6f6f6;';break;
  case 5: $cssStyle = 'background-color:#a6a6ff;color: #f6f6f6;';break;
}?>
            <th scope="col" style="text-align:center;{{$cssStyle}};width:16%">{{$label}}</th>
@endforeach
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row" style="border-top: 1px solid white;border: 1px solid white;border-right: 2px solid black;border-style: dashed;text-align: center;width:2%">{{ucwords(str_replace("_", ' ',$leftLabels[0]))}}</th>
            <td style='text-align:center;border: 1px solid white;width:16%'>
              <div class="border-shadow" style="background-color:#ffedd6;">
                  <span><strong>{{$data[ $leftLabels[0] ][0]['label']}}</strong></span><br />
                  <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[0] ][0]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[0] ][0]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($data[ $leftLabels[0] ][0]['previous_month_stats'])[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-1" style='text-align:center;border: #f6f6ff;width:16%'>
              <div class="border-shadow" style="background-color:#ffedd6;">
                <span><strong>{{$data[ $leftLabels[0] ][1]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[0] ][1]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[0] ][1]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($data[ $leftLabels[0] ][1]['previous_month_stats'])[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-2" style='text-align:center;border: #e9e9ff;width:16%'>
              <div class="border-shadow" style="background-color:#ffedd6;">
                <span><strong>{{$data[ $leftLabels[0] ][2]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[0] ][2]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[0] ][2]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-3" style='text-align:center;border: #d7d7ff;width:16%'>
              <div class="border-shadow" style="background-color:#ffedd6;">
                <span><strong>{{$data[ $leftLabels[0] ][3]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[0] ][3]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[0] ][3]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-4" style='text-align:center;border: #bbbbff;width:16%'>
              <div class="border-shadow" style="background-color:#ffedd6;">
                <span><strong>{{$data[ $leftLabels[0] ][4]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[0] ][4]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[0] ][4]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-5" style="border:#a6a6ff;width:16%"></td>
          </tr>
          <tr>
            <th scope="row" style="border: 1px solid white;border-right: 2px solid black;border-style: dashed;width:2%;text-align: center;"></th>
            <td style='width:16%;border: 1px solid white;text-align:center'><i class="fa fa-angle-double-down fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-1" style='border: #f6f6ff;text-align:center;width:16%'><i class="fa fa-angle-double-down fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-2" style='border: #e9e9ff;text-align:center;width:16%'><i class="fa fa-angle-double-down fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-3" style='border: #d7d7ff;text-align:center;width:16%'><i class="fa fa-angle-double-down fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-4" style='border: #bbbbff;text-align:center;width:16%'><i class="fa fa-angle-double-down fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-5" style='border:#a6a6ff;width:16%'></td>
          </tr>
          <tr>
            <th scope="row" style="border: 1px solid white;border-right: 2px solid black;border-style: dashed;width:2%;text-align: center;">{{ucwords(str_replace("_", ' ',$leftLabels[1]))}}</th>
            <td style='text-align:center;border: 1px solid white;width:16%;'>
              <div class="border-shadow" style='background-color:#f8f8f8'>
                <span><strong>{{$data[ $leftLabels[1] ][0]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[1] ][0]['stats'])[0]->count,0)}}</strong></span><br />
<?php  
  $sql = $data[ $leftLabels[1] ][0]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-1" style='width:16%;text-align:center;border: #f6f6ff;'>
              <div class="border-shadow" style='background-color:#f8f8f8'>
                <span><strong>{{$data[ $leftLabels[1] ][1]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[1] ][1]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[1] ][1]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-2" style='width:16%;text-align:center;border:#e9e9ff;'>
              <div class="border-shadow" style='background-color:#f8f8f8'>
                <span><strong>{{$data[ $leftLabels[1] ][2]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[1] ][2]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[1] ][2]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-3" style='width:16%;text-align:center;border: #d7d7ff;'>
              <div class="border-shadow" style='background-color:#f8f8f8'>
                <span><strong>{{$data[ $leftLabels[1] ][3]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[1] ][3]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[1] ][3]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-4" style='width:16%;text-align:center;border: #bbbbff;'>
              <div class="border-shadow" style='background-color:#f8f8f8'>
                <span><strong>{{$data[ $leftLabels[1] ][4]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[1] ][4]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[1] ][4]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td class="blue-dark-5" style='width:16%;text-align:center;border:#a6a6ff;'>
              <div class="border-shadow" style='background-color:#f8f8f8'>
                <span><strong>{{$data[ $leftLabels[1] ][5]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[1] ][5]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[1] ][5]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
          </tr>
          <tr>
            <th scope="row" style="border: 1px solid white;border-right: 2px solid black;border-style: dashed;width:2%;text-align: center;"></th>
            <td style='width:16%;text-align:left;transform:scale(-1);border-right:1px solid white;border-left: 1px solid white;'><i class="fa fa-reply fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-1" style='width:16%;text-align:left;transform:scale(-1);border:#f6f6ff;'><i class="fa fa-reply fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-2" style='width:16%;text-align:left;transform:scale(-1);border:#e9e9ff;'><i class="fa fa-reply fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-3" style='width:16%;text-align:left;transform:scale(-1);border: #d7d7ff;'><i class="fa fa-reply fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-4" style='width:16%;text-align:left;transform:scale(-1);border: #bbbbff;'><i class="fa fa-reply fa-2x" aria-hidden="true"></i></td>
            <td class="blue-dark-5" style="width:16%;border:#a6a6ff;"></td>
          </tr>
          <tr style='text-align:center'>
            <th scope="row" style="border: 1px solid white;border-right: 2px solid black;border-style: dashed;width:2%;text-align: center;">{{ucwords(str_replace("_", ' ',$leftLabels[2]))}}</th>
            <td style='width:16%;border:white'></td>
            <td style='width:14%;border:#f6f6ff' class="blue-dark-1">
              <div class="border-shadow" style="background-color:#e3faee;">
                <span><strong>{{$data[ $leftLabels[2] ][0]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[2] ][0]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[2] ][0]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td style='width:16%;border:#e9e9ff' class="blue-dark-2">
              <div class="border-shadow" style="background-color:#e3faee;">
                <span><strong>{{$data[ $leftLabels[2] ][1]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[2] ][1]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[2] ][1]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td style='width:16%;border:#d7d7ff' class="blue-dark-3">
              <div class="border-shadow" style="background-color:#e3faee;">
                <span><strong>{{$data[ $leftLabels[2] ][2]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[2] ][2]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[2] ][2]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td style='width:16%;border:#d7d7ff' class="blue-dark-4">
              <div class="border-shadow" style="background-color:#e3faee;">
                <span><strong>{{$data[ $leftLabels[2] ][3]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[2] ][3]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[2] ][3]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
            <td style='width:16%;border:#a6a6ff' class="blue-dark-5">
              <div class="border-shadow" style="background-color:#e3faee;">
                <span><strong>{{$data[ $leftLabels[2] ][4]['label']}}</strong></span><br />
                <span><strong style="font-size: 22px;">{{number_format($thisMonthCount=DB::select($data[ $leftLabels[2] ][4]['stats'])[0]->count,0)}}</strong></span><br />
<?php 
  $sql = $data[ $leftLabels[2] ][4]['previous_month_stats'];
  if($sql > ''){
    $previousMonthCount = DB::select($sql)[0]->count;
    $riseThisMonth = $thisMonthCount > $previousMonthCount ? true : false;
    $previousMonthCount = $previousMonthCount < 1 ? 1 : $previousMonthCount;
    $percentageRiseFall = number_format(($thisMonthCount/$previousMonthCount) * 100, 2);
  }
?>
              @if($riseThisMonth)
                  <span><strong style="color: green;"><i class="fa fa-arrow-up fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @elseif(!$riseThisMonth && $percentageRiseFall > 0)
                  <span><strong style="color: red;"><i class="fa fa-arrow-down fa-1x" aria-hidden="true"></i>&nbsp;{{$percentageRiseFall}}&nbsp;%</strong></span>
              @else
                  <span><strong style="color: orange;"><i class="fa fa-arrows-h" aria-hidden="true"></i></strong></span>
              @endif
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
	@else
		{!! $value !!}
	@endif
@endif	
