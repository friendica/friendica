<?php
	$color = false;
	if (local_user()) {
		$color = get_pconfig(local_user(), "quattro","color");
		$quattro_align = get_pconfig(local_user(), 'quattro', 'align' );
	}
	
	if ($color===false) $color="dark";
		
	if (file_exists("$THEMEPATH/$color/style.css")){
		echo file_get_contents("$THEMEPATH/$color/style.css");
	}


	if($quattro_align=="center"){
		echo "
			html { width: 100%; margin:0px; padding:0px; }
			body {
				margin: 50px auto;
				width: 900px;
			}
		";
	}
