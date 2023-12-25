<script src="{{$baseurl}}/view/asset/Chart-js/dist/Chart.min.js?v={{constant('\Friendica\App::VERSION')}}"></script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<canvas id="FederationChart" class="federation-graph" width="320" height="320"></canvas>
	<p>{{$intro}}</p>

	<p>{{$legendtext}}</p>

	<ul>
		{{foreach $counts as $c}}
			{{if $c[0]['total'] > 0}}
			<li>{{$c[0]['platform']}} ({{$c[0]['total']}}/{{$c[0]['users']}})</li>
			{{/if}}
		{{/foreach}}
	</ul>

	<script>
	var FedData = {
		datasets: [{
			data: [
				{{foreach $counts as $c}}
					{{$c[0]['total']}},
				{{/foreach}}
			],
			backgroundColor: [
				{{foreach $counts as $c}}
					'{{$c[3]}}',
				{{/foreach}}
				],
			hoverBackgroundColor: [
				{{foreach $counts as $c}}
					'#EE90A1',
				{{/foreach}}
			]
		}],
		labels: [
			{{foreach $counts as $c}}
				"{{$c[0]['platform']}}",
			{{/foreach}}
		]
	};
	var ctx = document.getElementById("FederationChart").getContext("2d");
	var myDoughnutChart = new Chart(ctx, {
		type: 'doughnut',
		data: FedData,
		options: {
		    legend: {display: false},
		    animation: {animateRotate: false},
		    responsive: false
		}
	});
	</script>

	<table id="federation-stats">
	{{foreach $counts as $c}}
		{{if $c[0]['total'] > 0}}
		<tr>
			<th>{{$c[0]['platform']}}</th>
			<th><strong>{{$c[0]['total']}}</strong></th>
			<td>{{$c[0]['network']}}</td>
		</tr>
		<tr>
			<td colspan="3" class="federation-summary">
				<ul>
					{{if $c[0]['total']}}<li>{{$c[0]['totallbl']}}</li>{{/if}}
					{{if $c[0]['month']}}<li>{{$c[0]['monthlbl']}}</li>{{/if}}
					{{if $c[0]['halfyear']}}<li>{{$c[0]['halfyearlbl']}}</li>{{/if}}
					{{if $c[0]['users']}}<li>{{$c[0]['userslbl']}}</li>{{/if}}
					{{if $c[0]['posts']}}<li>{{$c[0]['postslbl']}}</li>{{/if}}
					{{if $c[0]['postsuserlbl']}}<li>{{$c[0]['postsuserlbl']}}</li>{{/if}}
					{{if $c[0]['userssystemlbl']}}<li>{{$c[0]['userssystemlbl']}}</li>{{/if}}
				</ul>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="federation-data">
				<canvas id="{{$c[2]}}Chart" class="federation-network-graph" width="240" height="240"></canvas>
				<script>
					var {{$c[2]}}data = {
						datasets: [{
							data: [
							{{foreach $c[1] as $v}}
								{{$v['total']}},
							{{/foreach}}
							],
							backgroundColor: [
							{{foreach $c[1] as $v}}
								'{{$c[3]}}',
							{{/foreach}}
							],
							hoverBackgroundColor: [
							{{foreach $c[1] as $v}}
								'#EE90A1',
							{{/foreach}}
							]
						}],
						labels: [
							{{foreach $c[1] as $v}}
								'{{$v['version']}}',
							{{/foreach}}
						]
					};
					var ctx = document.getElementById("{{$c[2]}}Chart").getContext("2d");
					var my{{$c[2]}}DoughnutChart = new Chart(ctx, {
						type: 'doughnut',
						data: {{$c[2]}}data,
						options: {
						legend: {display: false},
							animation: {animateRotate: false},
							responsive: false
						}
					});
				</script>
				<ul class="federation-stats">
				{{foreach $c[1] as $v}}
					<li>
						{{if ($c[0]['platform']==='Friendica' and  $version===$v['version']) }}
						<span class="version-match">{{$v['version']}}</span>
						{{else}}
						{{$v['version']}}
						{{/if}}
						({{$v['total']}})
					</li>
				{{/foreach}}
				</ul>
			</td>
		</tr>
		{{/if}}
	{{/foreach}}
	</table>
</div>
