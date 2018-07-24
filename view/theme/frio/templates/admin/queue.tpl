<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>
	
	<p>{{$info}}</p>
	<table class="table">
		<tr>
			<th>{{$id_header}}</th>
			<th>{{$to_header}}</th>
			<th>{{$url_header}}</th>
			<th>{{$network_header}}</th>
			<th>{{$created_header}}</th>
			<th>{{$last_header}}</th>
		</tr>
		{{foreach $entries as $e}}
		<tr>
			<td>{{$e.id}}</td>
			<td>{{$e.name}}</td>
			<td><a href="{{$e.nurl}}">{{$e.nurl}}</a></td>
			<td>{{$e.network}}</td>
			<td>{{$e.created}}</td>
			<td>{{$e.last}}</td>
		</tr>
		{{/foreach}}
	</table>
</div>
