

<h1><img width="32" height="32" src="{{$baseurl}}/images/friendica.svg"> {{$title}}</h1>
<h2>{{$pass}}</h2>


<form id="install-form" action="{{$baseurl}}/index.php?pagename=install" method="post">

<input type="hidden" name="config-php_path" value="{{$php_path}}" />
<input type="hidden" name="system-basepath" value="{{$basepath}}" />
<input type="hidden" name="system-url" value="{{$system_url}}" />
<input type="hidden" name="database-hostname" value="{{$dbhost}}" />
<input type="hidden" name="database-username" value="{{$dbuser}}" />
<input type="hidden" name="database-password" value="{{$dbpass}}" />
<input type="hidden" name="database-database" value="{{$dbdata}}" />
<input type="hidden" name="pass" value="5" />

{{include file="field_input.tpl" field=$adminmail}} <br />
{{$timezone nofilter}} <br />
{{include file="field_select.tpl" field=$language}}

<input id="install-submit" type="submit" name="submit" value="{{$submit}}" />

</form>

