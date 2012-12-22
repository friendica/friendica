{{if $classtoday}}
<script>
	$(document).ready(function() {{$lbr}}
		$('#events-reminder').addClass($.trim('{{$classtoday}}'));
	{{$rbr}});
</script>
{{/if}}

