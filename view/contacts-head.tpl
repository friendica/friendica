
<script src="$baseurl/library/jquery_ac/friendica.complete.js" ></script>

<script>
$(document).ready(function() { 
	var a; 
	a = $("#contacts-search").autocomplete({ 
		serviceUrl: '$base/acl',
		minChars: 2,
		width: 350,
	});
	a.setOptions({ params: { type: 'a' }});

}); 

</script>

