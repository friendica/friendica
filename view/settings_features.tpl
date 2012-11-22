<h1>$title</h1>


<form action="settings/features" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='$form_security_token'>

{{ for $features as $f }}
<h3 class="settings-heading">$f.0</h3>

{{ for $f.1 as $fcat }}
	{{ inc $field_yesno with $field=$fcat }}{{endinc}}
{{ endfor }}
{{ endfor }}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-features-submit" value="$submit" />
</div>

</form>

