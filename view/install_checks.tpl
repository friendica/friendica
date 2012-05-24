<h1>$title</h1>
<h2>$pass</h2>
<form  action="$baseurl/index.php?q=install" method="post">
<table>
{{ for $checks as $check }}
	<tr><td>$check.title </td><td><span class="icon s22 {{if $check.status}}on{{else}}{{if$check.required}}off{{else}}yellow{{endif}}{{endif}}"></td><td>{{if $check.required}}(required){{endif}}</td></tr>
	{{if $check.help }}
	<tr><td colspan="3"><blockquote>$check.help</blockquote></td></tr>
	{{endif}}
{{ endfor }}
</table>

{{ if $phpath }}
	<input type="hidden" name="phpath" value="$phpath">
{{ endif }}

{{ if $passed }}
	<input type="hidden" name="pass" value="2">
	<input type="submit" value="$next">
{{ else }}
	<input type="hidden" name="pass" value="1">
	<input type="submit" value="$reload">
{{ endif }}
</form>
