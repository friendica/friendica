<script src="$baseurl/library/jquery_ac/friendica.complete.js" ></script>

<script>$(document).ready(function() { 
	var a; 
	a = $("#recip").autocomplete({ 
		serviceUrl: '$base/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#recip-complete").val(data);
		}			
	});

}); 

</script>

