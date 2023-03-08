<h1><img width="32" height="32" src="{{$baseurl}}/images/friendica.svg"> {{$title}}</h1>
<h2>{{$pass}}</h2>

<p>
	{{$info_01}}<br>
	{{$info_02}}<br>
	{{$info_03}}
</p>

<form id="install-form" action="{{$baseurl}}/index.php?pagename=install" method="post">

	<input type="hidden" name="config-php_path" value="{{$php_path}}" />
	<input type="hidden" name="pass" value="3" />

	{{include file="field_input.tpl" field=$basepath}}
	<br />
	{{include file="field_input.tpl" field=$system_url}}

	<input id="install-submit" type="submit" name="submit" value="{{$submit}}" />

</form>
