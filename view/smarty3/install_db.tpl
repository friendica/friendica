
<h1>{{$title}}</h1>
<h2>{{$pass}}</h2>


<p>
{{$info_01}}<br>
{{$info_02}}<br>
{{$info_03}}
</p>

{{if $status}}
<h3 class="error-message">{{$status}}</h3>
{{/if}}

<form id="install-form" action="{{$baseurl}}/install" method="post">

<input type="hidden" name="phpath" value="{{$phpath}}" />
<input type="hidden" name="pass" value="3" />

{{include file="file:{{$field_input}}" field=$dbhost}}
{{include file="file:{{$field_input}}" field=$dbuser}}
{{include file="file:{{$field_password}}" field=$dbpass}}
{{include file="file:{{$field_input}}" field=$dbdata}}


<input id="install-submit" type="submit" name="submit" value="{{$submit}}" /> 

</form>

