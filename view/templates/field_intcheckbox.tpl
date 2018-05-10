
	
	<div class='field checkbox'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<input type="checkbox" name='{{$field.0}}' id='id_{{$field.0}}' value="{{$field.3|escape:'html'}}" {{if $field.2}}checked="true"{{/if}} aria-describedby='{{$field.0}}_tip'>
		{{if $field.4}}
		<span class='field_help' role='tooltip' id='{{$field.0}}_tip'>{{$field.4}}</span>
		{{/if}}
	</div>
