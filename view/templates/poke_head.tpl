<script src="{{$baseurl}}/library/jquery_ac/friendica.complete.js" ></script>

<script>$(document).ready(function() {
	var a;
	a = $("#poke-recip").autocomplete({
		serviceUrl: '{{$base}}/acl',
		minChars: 2,
		width: 350,
		onSelect: function(value,data) {
			$("#poke-recip-complete").val(data);
		}
	});
	a.setOptions({ params: { type: 'a' }});


});

</script>