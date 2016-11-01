
	<div class="field checkbox" id="div_id_{{$field.0}}">
			<input type="hidden" name="{{$field.0}}" value="0">
			<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" value="1" {{if $field.2}}checked="checked"{{/if}}>
			<label for="id_{{$field.0}}">
			{{$field.1}}
			<p class="help-block">{{$field.3}}</p>
			</label>
	</div>