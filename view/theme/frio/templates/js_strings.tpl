

{{* Strings which are needed for some js functions (e.g. translation or the interval for page update)
They are loaded into the html <head> so that js functions can use them *}}
<script>
	var updateInterval = {{$update_interval}};

	var localUser = {{if $local_user}}{{$local_user}}{{else}}false{{/if}};
	var aStr = {
		'delitem'     : "{{$delitem}}",
	};

	{{* Create an object with the data which is needed for infinite scroll.
	For the relevant js part look at function loadContent() in main.js. *}}
	{{if $infinite_scroll}}
	var infinite_scroll = {
				'pageno'	: {{$infinite_scroll.pageno}},
				'reload_uri'	: "{{$infinite_scroll.reload_uri}}"
				}
	{{/if}}
</script>
