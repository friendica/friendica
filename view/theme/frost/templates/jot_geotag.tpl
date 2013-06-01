
	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			var lat = position.coords.latitude.toFixed(4);
			var lon = position.coords.longitude.toFixed(4);

			$('#jot-coord').val(lat + ', ' + lon);
			$('#profile-nolocation-wrapper').show();
		});
	}

