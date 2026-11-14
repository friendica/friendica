{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
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

	function initFederationChart() {
		var ctx = document.getElementById("FederationChart").getContext("2d");
		new Chart(ctx, {
			type: 'doughnut',
			data: FedData,
			options: {
			    legend: {display: false},
			    animation: {animateRotate: false},
			    responsive: false
			}
		});
	}

	window.onDocumentReady('#FederationChart', initFederationChart);

	{{foreach $counts as $c}}
		{{if $c[0]['total'] > 0}}
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

	function init{{$c[2]}}Chart() {
		var ctx = document.getElementById("{{$c[2]}}Chart").getContext("2d");
		new Chart(ctx, {
			type: 'doughnut',
			data: {{$c[2]}}data,
			options: {
			    legend: {display: false},
			    animation: {animateRotate: false},
			    responsive: false
			}
		});
	}

	window.onDocumentReady('#{{$c[2]}}Chart', init{{$c[2]}}Chart);
		{{/if}}
	{{/foreach}}
</script>
