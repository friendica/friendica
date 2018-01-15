<!--<script src="{{$baseurl}}/library/Chart.js-1.0.2/Chart.min.js"></script>-->
<script src="{{$baseurl}}/js/lib/Chart-js/dist/Chart.min.js"></script>
<canvas id="FederationChart" class="federation-graph" width="400" height="400"></canvas>
<div id="adminpage">
    <h1>{{$title}} - {{$page}}</h1>
    <p>{{$intro}}</p>
    {{if not $autoactive}}
	<p class="error-message">{{$hint}}</p>
    {{/if}}
    <p>{{$legendtext}}
    <ul>
    {{foreach $counts as $c}}
	{{if $c[0]['total'] > 0}}
	    <li>{{$c[0]['platform']}} ({{$c[0]['total']}}/{{$c[0]['users']}})</li>
	{{/if}}
    {{/foreach}}
    </ul>
    </p>
</div>
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
	    <th><strong>{{$c[0]['total']}}</strong></td>
	    <td>{{$c[0]['network']}}</td>
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
	<li>{{if ($c[0]['platform']==='Friendica' and  $version===$v['version']) }}<span class="version-match">{{$v['version']}}</span>{{else}}{{$v['version']}}{{/if}} ({{$v['total']}})</li>
    {{/foreach}}
    </ul>
    </td>
    </tr>
    {{/if}}
{{/foreach}}
</table>