
<h1><img width="32" height="32" src="{{$baseurl}}/images/friendica.svg"> {{$title}}</h1>
<h2>{{$pass}}</h2>
<form  action="{{$baseurl}}/index.php?pagename=install" method="post">
<table>
{{foreach $checks as $check}}
	<tr><td>{{$check.title nofilter}} </td><td>
	{{if $check.status}}
		<img src="{{$baseurl}}/view/install/green.png" alt="{{$ok}}">
	{{else}}
		{{if $check.required}}
			<img src="{{$baseurl}}/view/install/red.png" alt="{{$requirement_not_satisfied}}">
		{{else}}
			<img src="{{$baseurl}}/view/install/yellow.png" alt="{{$optional_requirement_not_satisfied}}">
		{{/if}}
	{{/if}}
	</td><td>{{if $check.required}}{{$required}}{{/if}}</td></tr>
	{{if $check.help}}
	<tr><td class="help" colspan="3">
		<blockquote>{{$check.help nofilter}}</blockquote>
		{{if $check.error_msg}}
		<div class="error_header"><b>{{$check.error_msg.head}}<br><a href="{{$check.error_msg.url}}">{{$check.error_msg.url}}</a></b></div>
		<blockquote>{{$check.error_msg.msg}}</blockquote>
		{{/if}}
	</td></tr>
	{{/if}}
{{/foreach}}
</table>

{{if $phpath}}
	<input type="hidden" name="config-php_path" value="{{$php_path}}">
{{/if}}

{{if $passed}}
	<input type="hidden" name="pass" value="2">
	<input type="submit" value="{{$next}}">
{{else}}
	<input type="hidden" name="pass" value="1">
	<input type="submit" value="{{$reload}}">
{{/if}}
</form>
