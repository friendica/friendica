
{{if $field.5=="preview"}}<script>$(document).ready(function(){ previewTheme($("#id_{{$field.0}}")[0]); });</script>{{/if}}
<div class="form-group field select">
	<label for="id_{{$field.0}}">{{$field.1}}</label>
	<select class="form-control" name="{{$field.0}}" id="id_{{$field.0}}" {{if $field.5=="preview"}}onchange="previewTheme(this);"{{/if}} aria-describedby="{{$field.0}}_tip" >
		{{foreach $field.4 as $opt=>$val}}<option value="{{$opt}}" {{if $opt==$field.2}}selected="selected"{{/if}}>{{$val}}</option>{{/foreach}}
	</select>
	<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3}}</span>
	{{if $field.5=="preview"}}<div id="theme-preview"></div>{{/if}}
</div>