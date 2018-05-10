
	{{if $field.5}}<script>$(function(){ previewTheme($("#id_{{$field.0}}")[0]); });</script>{{/if}}
	<div class='field select'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<select name='{{$field.0}}' id='id_{{$field.0}}' {{if $field.5}}onchange="previewTheme(this);"{{/if}} aria-describedby='{{$field.0}}_tip'>
			{{foreach $field.4 as $opt=>$val}}<option value="{{$opt|escape:'html'}}" {{if $opt==$field.2}}selected="selected"{{/if}}>{{$val}}</option>{{/foreach}}
		</select>
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3}}</span>
		{{/if}}
		{{if $field.5}}<div id="theme-preview"></div>{{/if}}
	</div>
