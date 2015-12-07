
<h1><img src="{{$baseurl}}/images/friendica-32.png"> {{$title}}</h1>
<h2>{{$pass}}</h2>
<form  action="{{$baseurl}}/index.php?q=install" method="post">
<table>
{{foreach $checks as $check}}
	<tr><td>{{$check.title}} </td><td>
	{{if $check.status}}
		<img src="{{$baseurl}}/view/install/green.png" alt="Ok">
	{{else}}
		{{if $check.required}}
			<img src="{{$baseurl}}/view/install/red.png" alt="Requirement not satisfied">
		{{else}}
			<img src="{{$baseurl}}/view/install/yellow.png" alt="Optional requirement not satisfied">
		{{/if}}
	{{/if}}
	</td><td>{{if $check.required}}(required){{/if}}</td></tr>
	{{if $check.help}}
	<tr><td class="help" colspan="3"><blockquote>{{$check.help}}</blockquote></td></tr>
	{{/if}}
{{/foreach}}
</table>

{{if $phpath}}
	<input type="hidden" name="phpath" value="{{$phpath|escape:'html'}}">
{{/if}}

{{if $passed}}
	<input type="hidden" name="pass" value="2">
	<input type="submit" value="{{$next|escape:'html'}}">
{{else}}
	<input type="hidden" name="pass" value="1">
	<input type="submit" value="{{$reload|escape:'html'}}">
{{/if}}
</form>
