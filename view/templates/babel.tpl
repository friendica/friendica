<div id="babel" class="generic-page-wrapper">
	<h2>{{$title}}</h2>
	<form action="babel" method="post" class="panel panel-default">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<div class="panel-body">
			<div class="form-group">
				{{include file="field_textarea.tpl" field=$text}}
			</div>
			<div class="form-group">
				{{include file="field_radio.tpl" field=$type_bbcode}}
				{{include file="field_radio.tpl" field=$type_diaspora}}
				{{include file="field_radio.tpl" field=$type_markdown}}
				{{include file="field_radio.tpl" field=$type_html}}
			{{if $flag_twitter}}
				{{include file="field_radio.tpl" field=$type_twitter}}
			{{/if}}
			</div>
			<p><button type="submit" class="btn btn-primary">{{$submit}}</button></p>
		</div>
	</form>

	{{if $results}}
	<div class="babel-results">
		{{foreach $results as $result}}
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$result.title}}</h3>
			</div>
			<div class="panel-body">{{$result.content nofilter}}</div>
		</div>
		{{/foreach}}
	</div>
</div>
{{/if}}
