
	
	<div class='field richtext'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<textarea name='{{$field.0}}' id='id_{{$field.0}}' class="fieldRichtext" aria-describedby='{{$field.0}}_tip'>{{$field.2}}</textarea>
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3}}</span>
		{{/if}}
	</div>
