<h2>$title</h2>


<div id="group-edit-wrapper" >
	<form action="group/$gid" id="group-edit-form" method="post" >
		<input type='hidden' name='form_security_token' value='$form_security_token'>
		
		{{ inc field_input.tpl with $field=$gname }}{{ endinc }}
		{{ if $drop }}$drop{{ endif }}
		<div id="group-edit-submit-wrapper" >
			<input type="submit" name="submit" value="$submit" >
		</div>
		<div id="group-edit-select-end" ></div>
	</form>
</div>


{{ if $groupeditor }}
	<div id="group-update-wrapper">
		{{ inc groupeditor.tpl }}{{ endinc }}
	</div>
{{ endif }}
{{ if $desc }}<div id="group-edit-desc">$desc</div>{{ endif }}
