
	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			var lat = position.coords.latitude.toFixed(4);
			var lon = position.coords.longitude.toFixed(4);

			$j('#jot-coord').val(lat + ', ' + lon);
			$j('#profile-nolocation-wrapper').show();
		});
	}

