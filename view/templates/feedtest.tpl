<h2>Feed Test</h2>
<form action="feedtest" method="get" class="panel panel-default">
	<div class="panel-body">
		<div class="form-group">
			{{include file="field_input.tpl" field=$url}}
		</div>
		<p><button type="submit" class="btn btn-primary">Submit</button></p>
	</div>
</form>

{{if $result}}
<div class="feedtest-result">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Output Items</h3>
		</div>
		<div class="panel-body">
			<pre>{{$result.output}}</pre>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Input Feed XML</h3>
		</div>
		<div class="panel-body">
			{{$result.input}}
		</div>
	</div>
</div>
{{/if}}