<?php
	$color=false;
	$cleanzero_font_size=false;

	$site_color = get_config("cleanzero","color");
	$site_cleanzero_font_size = get_config("cleanzero", "font_size" );

	
	if (local_user()) {
		$color = get_pconfig(local_user(), "cleanzero","color");
		$cleanzero_font_size = get_pconfig(local_user(), "cleanzero", "font_size");
	
	}
	
	if ($color===false) $color=$site_color;
	if ($color===false) $color="cleanzero";
	if ($cleanzero_font_size===false) $cleanzero_font_size=$site_cleanzero_font_size;

	
		
	if (file_exists("$THEMEPATH/$color/style.css")){
		echo file_get_contents("$THEMEPATH/$color/style.css");
	}



	if($cleanzero_font_size == "16"){
		echo "
			.wall-item-content-wrapper {
  					font-size: 16px;
  					}
  					
			.wall-item-content-wrapper.comment {
  					font-size: 16px;
  					}
		";  
       }
       if($cleanzero_font_size == "14"){
		echo "
			.wall-item-content-wrapper {
  					font-size: 14px;
  					}
  					
			.wall-item-content-wrapper.comment {
  					font-size: 14px;
  					}
		";
	}	
	if($cleanzero_font_size == "12"){
		echo "
			.wall-item-content-wrapper {
  					font-size: 12px;
  					}
  					
			.wall-item-content-wrapper.comment {
  					font-size: 12px;
  					}
		";
	}
	if($cleanzero_font_size == "10"){
		echo "
			.wall-item-content-wrapper {
  					font-size: 10px;
  					}
  					
			.wall-item-content-wrapper.comment {
  					font-size: 10px;
  					}
		";
	}

