
	<div class="field checkbox" id="div_id_{{$field.0}}">
			<input type="hidden" name="{{$field.0}}" value="0">
			<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" value="1" {{if $field.2}}checked="checked"{{/if}} aria-checked="{{if $field.2}}true{{else}}false{{/if}}" aria-describedby="{{$field.0}}_tip">
			<label for="id_{{$field.0}}">
				{{$field.1}}
				<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3}}</span>
			</label>
	</div>