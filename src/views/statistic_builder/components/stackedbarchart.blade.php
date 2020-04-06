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
    	<a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Stacked Bar Chart' class='btn-edit-component'><i class='fa fa-pencil'></i></a> &nbsp;
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
			<label>Bar Chart Configuration</label>
			<textarea name='config[sql]' required rows="4" class='form-control'>{{@$config->sql}}</textarea>
			<div class="block-help"></div>
		</div>

		<div class="form-group">
			<label>Bar Area Name</label>
			<input class="form-control" required name='config[area_name]' type='text' value='{{@$config->area_name}}'/>
			<!-- <div class="block-help">You can name each line area. Write name separate with ;</div> -->
		</div>
	</form>
@elseif($command=='showFunction')
	@if($key == 'sql')
<?php 
$rawData = (json_decode($value));

$statsData = $rawData[0];
$labels = $rawData[1]->label;
$data = $rawData[2];
$result = []; $index = 0;
foreach($statsData as $k => $statSQL)
{
	$stats[$index][] = $k;
	$stats[$index][] = strlen($statSQL) > '' ? DB::select($statSQL)[0]->count : 0;

	$index++;
}
$index = 0;
foreach($data as $k => $val)
{
	$result[$index][] = ucwords(str_replace('_', ' ',$k));
	$sqls = explode(';', $val);
	$result[$index][] = strlen($sqls[0]) > '' ? DB::select($sqls[0])[0]->count : 0; 
	$result[$index][] = strlen($sqls[1]) > '' ? DB::select($sqls[1])[0]->count : 0;
	$index++;
}

$stats = json_encode($stats);
$barChartdata = json_encode($result);
?>      
		<div id="container" style="width:100%;">
		    <div id="stat">
				<div class="border-shadow" style='width:28%'>
					<table>
						<tr>
							<td style='padding:2px'><span id="label-1"></span></td>
							<td style='padding:2px'>&nbsp;&nbsp;<strong id="label-1-result"></strong></td>
							<!-- <span style='color:green'><i class="fa fa-level-up" aria-hidden="true"></i>&nbsp;0.25%</span> -->
						</tr>
						<tr>
							<td style='padding:2px;text-align:left'><span id="label-2"></span></td>
							<td style='padding:2px;text-align:right'>&nbsp;&nbsp;<strong><span id="label-2-result"></span></strong></td>
							<!-- <span style='color:red'><i class="fa fa-level-down" style='' aria-hidden="true"></i>&nbsp;0.15%</span> -->
						</tr>
					</table>
					<!-- <span>Actual Total: </span><strong style='font-size:18px'>600000&nbsp;&nbsp;<span style='color:green'><i class="fa fa-level-up" aria-hidden="true"></i>&nbsp;0.25%</span></strong> -->
					<!-- <span>Total Contactable Base: </span><strong style='font-size:18px'>350000&nbsp;&nbsp;<span style='color:red'><i class="fa fa-level-down" style='' aria-hidden="true"></i>&nbsp;0.15%</span></strong> -->
				</div>
				<!-- <span>Actual Total: <strong>600000&nbsp;&nbsp;<span style='color:green'><i class="fa fa-level-up" aria-hidden="true"></i>&nbsp;0.25%</span></strong></span><br />
				<span>Total Contactable Base: <strong>350000&nbsp;&nbsp;<span style='color:red'><i class="fa fa-level-down" style='' aria-hidden="true"></i>&nbsp;0.15%</span></strong></span> -->
			</div>
			<div id="chart_div"></div>
    	</div>

<script type="text/javascript">
		var barChartData = $.parseJSON('{!!$barChartdata!!}');
		var labels = "{{$labels}}";
		var stats = $.parseJSON('{!!$stats!!}');
		$('#label-1').text(stats[0][0]);
		$('#label-1-result').text(stats[0][1]);
		$('#label-2').text(stats[1][0]);
		$('#label-2-result').text(stats[1][1]);

		var labelArray = labels.split(";");	
		labelArray[0] = (labelArray[0].replace('_',' ')).toLowerCase().replace(/\b[a-z]/g, function(letter) {return letter.toUpperCase();});;
		labelArray[1] = (labelArray[1].replace('_',' ')).toLowerCase().replace(/\b[a-z]/g, function(letter){return letter.toUpperCase();});

        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);
        function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Registrations');
		data.addColumn('number', labelArray[0]);
		data.addColumn('number', labelArray[1]);
		data.addRows(barChartData);
		var view = new google.visualization.DataView(data);
		view.setColumns([0,
			1, {
				calc: function (dt, row) {
					return dt.getValue(row, 1);
				},
			type: "number",
			role: "annotation"
			},
			2, {
				calc: function (dt, row) {
					return dt.getValue(row, 2);
				},
			type: "number",
			role: "annotation"
			}
		]);
        var options = {
            // width: 1139,
            height: 300,
            legend: { position: 'top', maxLines: 3 },
            bar: { groupWidth: '60%' },
			isStacked: true,
        };
        var chart = new google.visualization.ColumnChart(
            document.getElementById('chart_div'));
        chart.draw(view, options);
        }
</script>
	@else
		{!! $value !!}
	@endif
@endif	