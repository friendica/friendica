	
	<div class='field checkbox'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<input type="checkbox" name='{{$field.0}}' id='id_{{$field.0}}' value="{{$field.3}}" {{if $field.2}}checked="true"{{/if}}>
		<span class='field_help'>{{$field.4}}</span>
	</div>
