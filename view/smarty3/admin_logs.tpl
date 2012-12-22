<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/logs" method="post">
    <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="file:{{$field_checkbox}}" field=$debugging}}
	{{include file="file:{{$field_input}}" field=$logfile}}
	{{include file="file:{{$field_select}}" field=$loglevel}}
	
	<div class="submit"><input type="submit" name="page_logs" value="{{$submit}}" /></div>
	
	</form>
	
	<h3>{{$logname}}</h3>
	<div style="width:100%; height:400px; overflow: auto; "><pre>{{$data}}</pre></div>
<!--	<iframe src='{{$baseurl}}/{{$logname}}' style="width:100%; height:400px"></iframe> -->
	<!-- <div class="submit"><input type="submit" name="page_logs_clear_log" value="{{$clear}}" /></div> -->
</div>
