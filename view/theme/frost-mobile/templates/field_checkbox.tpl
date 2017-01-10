

	<div class="field checkbox" id="div_id_{{$field.0}}">
		<label id="label_id_{{$field.0}}" for="id_{{$field.0}}">{{$field.1}}</label>
		<input type="hidden" name="{{$field.0}}" value="0">
		<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" value="1" {{if $field.2}}checked="checked"{{/if}}><br />
		<span class="field_help" id="help_id_{{$field.0}}">{{$field.3}}</span>
	</div>
