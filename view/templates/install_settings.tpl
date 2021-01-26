<h1><img src="{{$baseurl}}/images/friendica-32.png"> {{$title}}</h1>
<h2>{{$pass}}</h2>


<form id="install-form" action="{{$baseurl}}/install" method="post">

	<input type="hidden" name="config-php_path" value="{{$php_path}}" />
	<input type="hidden" name="config-hostname" value="{{$hostname}}" />
	<input type="hidden" name="system-ssl_policy" value="{{$ssl_policy}}" />
	<input type="hidden" name="system-basepath" value="{{$basepath}}" />
	<input type="hidden" name="system-urlpath" value="{{$urlpath}}" />
	<input type="hidden" name="database-hostname" value="{{$dbhost}}" />
	<input type="hidden" name="database-username" value="{{$dbuser}}" />
	<input type="hidden" name="database-password" value="{{$dbpass}}" />
	<input type="hidden" name="database-database" value="{{$dbdata}}" />
	<input type="hidden" name="pass" value="5" />

	{{include file="field_input.tpl" field=$adminmail}} <br />
	{{$timezone nofilter}} <br />
	{{include file="field_select.tpl" field=$language}} <br />
	{{include file="field_checkbox.tpl" field=$api_enable}}


	<input id="install-submit" type="submit" name="submit" value="{{$submit}}" />

</form>
