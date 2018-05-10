
	<div class="form-group field checkbox">
		<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.3|escape:'html'}}" {{if $field.2}}checked="checked"{{/if}} aria-checked="{{if $field.2}}true{{else}}false{{/if}}" aria-describedby="{{$field.0}}_tip">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		{{if $field.4}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.4}}</span>
		{{/if}}
	</div>
	<div class="clear"></div>
