<h1><img width="32" height="32" src="{{$baseurl}}/images/friendica.svg"> {{$title}}</h1>
<h2>{{$pass}}</h2>

<p>
	{{$info_01}}<br>
	{{$info_02}}<br>
	{{$info_03}}
</p>

<table>
	{{foreach $checks as $check}}
	<tr>
		<td>{{$check.title}} </td>
		<td>
			{{if ! $check.status}}
			<img src="{{$baseurl}}/view/install/red.png" alt="{{$requirement_not_satisfied}}">
			{{/if}}
		</td>
	{{/foreach}}
</table>

<form id="install-form" action="{{$baseurl}}/index.php?pagename=install" method="post">

	<input type="hidden" name="config-php_path" value="{{$php_path}}" />
	<input type="hidden" name="system-basepath" value="{{$basepath}}" />
	<input type="hidden" name="system-url" value="{{$system_url}}" />
	<input type="hidden" name="pass" value="4" />

	{{include file="field_input.tpl" field=$dbhost}}
	{{include file="field_input.tpl" field=$dbuser}}
	{{include file="field_password.tpl" field=$dbpass}}
	{{include file="field_input.tpl" field=$dbdata}}

	<input id="install-submit" type="submit" name="submit" value="{{$submit}}" />

</form>
