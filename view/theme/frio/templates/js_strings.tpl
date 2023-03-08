
{{* Strings which are needed for some js functions (e.g. translation or the interval for page update)
They are loaded into the html <head> so that js functions can use them *}}
<script type="text/javascript">
	var updateInterval = {{$update_interval}};

	var localUser = {{if $local_user}}{{$local_user}}{{else}}false{{/if}};
	var aStr = {
		'delitem'      : "{{$delitem|escape:'javascript' nofilter}}",
		'blockAuthor'  : "{{$blockAuthor|escape:'javascript' nofilter}}",
		'ignoreAuthor' : "{{$ignoreAuthor|escape:'javascript' nofilter}}",
	};
</script>
