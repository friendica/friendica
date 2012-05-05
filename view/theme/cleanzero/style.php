<?php
	$color=false;
	$cleanzero_font_size=false;
	$cleanzero_theme_width=false;

	$site_color = get_config("cleanzero","color");
	$site_cleanzero_font_size = get_config("cleanzero", "font_size" );
	$site_cleanzero_theme_width = get_config("cleanzero", "theme_width");
	
	if (local_user()) {
		$color = get_pconfig(local_user(), "cleanzero","color");
		$cleanzero_font_size = get_pconfig(local_user(), "cleanzero", "font_size");
		$cleanzero_theme_width = get_pconfig(local_user(), "cleanzero", "theme_width");
	
	}
	
	if ($color===false) $color=$site_color;
	if ($color===false) $color="cleanzero";
	if ($cleanzero_font_size===false) $cleanzero_font_size=$site_cleanzero_font_size;
	if ($cleanzero_theme_width===false) $cleanzero_theme_width=$site_cleanzero_theme_width;
	if ($cleanzero_theme_width===false) $cleanzero_theme_width="standard";
	
		
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
	if ($cleanzero_theme_width === "standard") {
		echo "
                     section {
	                margin: 0px 10%;
                       margin-right:10%;
                       }

                     aside {
	                margin-left: 10%;
                      }
                     nav {
	                margin-left: 10%;
	                margin-right: 10%;

                      }

                     nav #site-location {
	                right: 10%;

                      }
		";
	}

	if ($cleanzero_theme_width === "narrow") {
		echo "
                     section {
	                margin: 0px 15%;
                       margin-right:15%;
                       }

                     aside {
	                margin-left: 15%;
                      }
                     nav {
	                margin-left: 15%;
	                margin-right: 15%;

                      }

                     nav #site-location {
	                right: 15%;

                      }
		";
	}
	if ($cleanzero_theme_width === "wide") {
		echo "
                     section {
	                margin: 0px 5%;
                       margin-right:5%;
                       }

                     aside {
	                margin-left: 5%;
                      }
                     nav {
	                margin-left: 5%;
	                margin-right: 5%;

                      }

                     nav #site-location {
	                right: 5%;

                      }
		";
	}
