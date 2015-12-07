

<h1><img src="{{$baseurl}}/images/friendica-32.png"> {{$title}}</h1>
<h2>{{$pass}}</h2>


{{if $status}}
<h3 class="error-message">{{$status}}</h3>
{{/if}}

<form id="install-form" action="{{$baseurl}}/install" method="post">

<input type="hidden" name="phpath" value="{{$phpath|escape:'html'}}" />
<input type="hidden" name="dbhost" value="{{$dbhost|escape:'html'}}" />
<input type="hidden" name="dbuser" value="{{$dbuser|escape:'html'}}" />
<input type="hidden" name="dbpass" value="{{$dbpass|escape:'html'}}" />
<input type="hidden" name="dbdata" value="{{$dbdata|escape:'html'}}" />
<input type="hidden" name="pass" value="4" />

{{include file="field_input.tpl" field=$adminmail}}
{{$timezone}}

<input id="install-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" /> 

</form>

