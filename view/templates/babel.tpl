<h2>Babel Diagnostic</h2>
<form action="babel" method="post" class="panel panel-default">
	<div class="panel-body">
		<div class="form-group">
			{{include file="field_textarea.tpl" field=$text}}
		</div>
		<div class="form-group">
			{{include file="field_radio.tpl" field=$type_bbcode}}
			{{include file="field_radio.tpl" field=$type_markdown}}
			{{include file="field_radio.tpl" field=$type_html}}
		</div>
		<p><button type="submit" class="btn btn-primary">Submit</button></p>
	</div>
</form>

{{if $results}}
<div class="babel-results">
	{{foreach $results as $result}}
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">{{$result.title}}</h3>
		</div>
		<div class="panel-body">
			{{$result.content}}
		</div>
	</div>
	{{/foreach}}
</div>
{{/if}}