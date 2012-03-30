<?php
	$color = get_pconfig(local_user(), "quattro","color");
	
	if ($color===false) $color="dark";
		
	if (file_exists("$THEMEPATH/$color/style.css")){
		echo file_get_contents("$THEMEPATH/$color/style.css");
	}

