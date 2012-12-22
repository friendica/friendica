	
	<div class='field radio'>
		<label for='id_{{$field.0}}_{{$field.2}}'>{{$field.1}}</label>
		<input type="radio" name='{{$field.0}}' id='id_{{$field.0}}_{{$field.2}}' value="{{$field.2}}" {{if $field.4}}checked="true"{{/if}}>
		<span class='field_help'>{{$field.3}}</span>
	</div>
