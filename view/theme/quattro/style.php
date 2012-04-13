<?php
	$color=false;
	$quattro_align=false;
	$site_color = get_config("quattro","color");
	$site_quattro_align = get_config("quattro", "align" );
	
	if (local_user()) {
		$color = get_pconfig(local_user(), "quattro","color");
		$quattro_align = get_pconfig(local_user(), 'quattro', 'align' );
	}
	
	if ($color===false) $color=$site_color;
	if ($color===false) $color="dark";
	if ($quattro_align===false) $quattro_align=$site_quattro_align;
	
		
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
