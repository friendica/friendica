<div id='adminpage'>
    <h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/logs" method="post">
	    <input type='hidden' name='form_security_token' value="{{$form_security_token|escape:'html'}}">

	    {{include file="field_checkbox.tpl" field=$debugging}}
	    {{include file="field_input.tpl" field=$logfile}}
	    {{include file="field_select.tpl" field=$loglevel}}
	
	    <div class="submit"><input type="submit" name="page_logs" value="{{$submit|escape:'html'}}" /></div>
	
	</form>

	<h2>{{$phpheader}}</h2>
	<div>
		<p>{{$phplogenabled}}<p>
		<p>{{$phphint}}</p>
		<pre>{{$phplogcode}}</pre>
	</div>
	
</div>
