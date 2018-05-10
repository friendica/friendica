
<div class="form-group field select">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<select name="{{$field.0}}" id="id_{{$field.0}}" class="form-control" aria-describedby="{{$field.0}}_tip">
			{{foreach $field.4 as $opt=>$val}}<option value="{{$opt|escape:'html'}}" {{if $opt==$field.2}}selected="selected"{{/if}}>{{$val}}</option>{{/foreach}}
		</select>
		{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3}}</span>
		{{/if}}
</div>
