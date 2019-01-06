
	
	<div class='field select'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<select name='{{$field.0}}[]' id='id_{{$field.0}}' aria-describedby='{{$field.0}}_tip' multiple>
			{{html_options values=$field.4 output=$field.4 selected=$field.2}}
		</select>
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3}}</span>
		{{/if}}
	</div>
