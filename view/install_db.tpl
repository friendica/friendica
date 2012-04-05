
<h1>$title</h1>
<h2>$pass</h2>


<p>
$info_01<br>
$info_02<br>
$info_03
</p>

{{ if $status }}
<h3 class="error-message">$status</h3>
{{ endif }}

<form id="install-form" action="$baseurl/install" method="post">

<input type="hidden" name="phpath" value="$phpath" />
<input type="hidden" name="pass" value="3" />

{{ inc field_input.tpl with $field=$dbhost }}{{endinc}}
{{ inc field_input.tpl with $field=$dbuser }}{{endinc}}
{{ inc field_password.tpl with $field=$dbpass }}{{endinc}}
{{ inc field_input.tpl with $field=$dbdata }}{{endinc}}


<input id="install-submit" type="submit" name="submit" value="$submit" /> 

</form>

