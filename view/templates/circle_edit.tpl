
<h2>{{$title}}</h2>


{{if $editable == 1}}
<div id="circle-edit-wrapper">
	<form action="circle/{{$gid}}" id="circle-edit-form" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		{{include file="field_input.tpl" field=$gname}}
		{{if $drop}}{{$drop nofilter}}{{/if}}
		<div id="circle-edit-submit-wrapper">
			<input type="submit" name="submit" value="{{$submit}}">
		</div>
		<div id="circle-edit-select-end"></div>
	</form>
</div>
{{/if}}


{{if $circle_editor}}
	<div id="circle-update-wrapper">
		{{include file="circle_editor.tpl"}}
	</div>
{{/if}}
{{if $desc}}<div class="clear" id="circle-edit-desc">{{$desc nofilter}}</div>{{/if}}
