
	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			$('#jot-coord').val(position.coords.latitude + ' ' + position.coords.longitude);
			$('#profile-nolocation-wrapper').show();
		});
	}

