<div id='adminpage'>
	<h1>$title - $page</h1>
	
	<form action="$baseurl/admin/logs" method="post">
    <input type='hidden' name='form_security_token' value='$form_security_token'>

	{{ inc field_checkbox.tpl with $field=$debugging }}{{ endinc }}
	{{ inc field_input.tpl with $field=$logfile }}{{ endinc }}
	{{ inc field_select.tpl with $field=$loglevel }}{{ endinc }}
	
	<div class="submit"><input type="submit" name="page_logs" value="$submit" /></div>
	
	</form>
	
	<h3>$logname</h3>
	<div style="width:100%; height:400px; overflow: auto; "><pre>$data</pre></div>
<!--	<iframe src='$baseurl/$logname' style="width:100%; height:400px"></iframe> -->
	<!-- <div class="submit"><input type="submit" name="page_logs_clear_log" value="$clear" /></div> -->
</div>
