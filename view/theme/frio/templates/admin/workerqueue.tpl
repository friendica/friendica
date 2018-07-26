<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>
	
	<p>{{$info}}</p>
	<table class="table">
		<tr>
			<th>{{$id_header}}</th>
			<th>{{$param_header}}</th>
			<th>{{$created_header}}</th>
			<th>{{$prio_header}}</th>
		</tr>
		{{foreach $entries as $e}}
		<tr>
			<td>{{$e.id}}</td>
			<td>{{$e.parameter}}</td>
			<td>{{$e.created}}</td>
			<td>{{$e.priority}}</td>
		</tr>
		{{/foreach}}
	</table>
</div>
