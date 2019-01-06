<div id='adminpage'>
    <h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/logs" method="post">
	    <input type='hidden' name='form_security_token' value="{{$form_security_token|escape:'html'}}">

	    {{include file="field_yesno.tpl" field=$debugging}}
	    {{include file="field_yesno.tpl" field=$profiling}}
	    {{include file="field_input.tpl" field=$devip}}

		<div class="submit"><input type="submit" name="page_logs" value="{{$submit|escape:'html'}}" /></div>

	<h2>{{$channels.0}}</h2>
	<p>{{$channels.1}}</p>
		{{foreach $channels.2 as $c}}
		<h3>{{$c.channel.2}}</h3>
			{{include file="field_select_multi.tpl" field=$c.handler}}
		{{/foreach}}
		<div class="submit"><input type="submit" name="page_log_channel" value="{{$submit|escape:'html'}}" /></div>

		<h2>{{$handler.0}}</h2>
		<p>{{$handler.1}}</p>
		{{foreach $handler.2 as $h}}
		<h3>{{$h.handler.2}}</h3>
		{{include file="field_checkbox.tpl" field=$h.enabled}}
		{{include file="field_textarea.tpl" field=$h.description}}
		{{include file="field_input.tpl" field=$h.logfile}}
		{{include file="field_select.tpl" field=$h.loglevel}}
		{{include file="field_checkbox.tpl" field=$h.errors}}
		{{/foreach}}
		<div class="submit"><input type="submit" name="page_log_handler" value="{{$submit|escape:'html'}}" /></div>

	</form>

</div>
