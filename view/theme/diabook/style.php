<?php
	$line_height=false;
	$diabook_font_size=false;
	$resolution=false;
	$color=false;
	$site_line_height = get_config("diabook","line_height");
	$site_diabook_font_size = get_config("diabook", "font_size" );
	$site_resolution = get_config("diabook", "resolution" );
	$site_color = get_config("diabook", "color" );
	
	
	if (local_user()) {
		$line_height = get_pconfig(local_user(), "diabook","line_height");
		$diabook_font_size = get_pconfig(local_user(), "diabook", "font_size");
		$resolution = get_pconfig(local_user(), "diabook", "resolution");
		$color = get_pconfig(local_user(), "diabook", "color");
	}
	
	if ($line_height===false) $line_height=$site_line_height;
	if ($line_height===false) $line_height="1.3";
	if ($diabook_font_size===false) $diabook_font_size=$site_diabook_font_size;
	if ($diabook_font_size===false) $diabook_font_size="13";
	if ($resolution===false) $resolution=$site_resolution;
	if ($resolution===false) $resolution="normal";
	if ($color===false) $color=$site_color;
	if ($color===false) $color="diabook";
	
	if($color == "diabook") {
	if($resolution == "normal") {	
	if (file_exists("$THEMEPATH/style.css")){
		echo file_get_contents("$THEMEPATH/style.css");
	}

   if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}	
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	
	if($resolution == "wide") {	
	if (file_exists("$THEMEPATH/style-wide.css")){
		echo file_get_contents("$THEMEPATH/style-wide.css");
	}
	if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	}
	
	if($color == "aerith") {
	if($resolution == "normal") {	
	if (file_exists("$THEMEPATH/diabook-aerith/style.css")){
		echo file_get_contents("$THEMEPATH/diabook-aerith/style.css");
	}

   if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}	
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	
	if($resolution == "wide") {	
	if (file_exists("$THEMEPATH/diabook-aerith/style-wide.css")){
		echo file_get_contents("$THEMEPATH/diabook-aerith/style-wide.css");
	}
	if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	}
	
	if($color== "blue") {
	if($resolution == "normal") {	
	if (file_exists("$THEMEPATH/diabook-blue/style.css")){
		echo file_get_contents("$THEMEPATH/diabook-blue/style.css");
	}

   if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}	
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	
	if($resolution == "wide") {	
	if (file_exists("$THEMEPATH/diabook-blue/style-wide.css")){
		echo file_get_contents("$THEMEPATH/diabook-blue/style-wide.css");
	}
	if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	}
	
	if($color== "red") {
	if($resolution == "normal") {	
	if (file_exists("$THEMEPATH/diabook-red/style.css")){
		echo file_get_contents("$THEMEPATH/diabook-red/style.css");
	}

   if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}	
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	
	if($resolution == "wide") {	
	if (file_exists("$THEMEPATH/diabook-red/style-wide.css")){
		echo file_get_contents("$THEMEPATH/diabook-red/style-wide.css");
	}
	if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	}
	
	if($color== "pink") {
	if($resolution == "normal") {	
	if (file_exists("$THEMEPATH/diabook-pink/style.css")){
		echo file_get_contents("$THEMEPATH/diabook-pink/style.css");
	}

   if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}	
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	
	if($resolution == "wide") {	
	if (file_exists("$THEMEPATH/diabook-pink/style-wide.css")){
		echo file_get_contents("$THEMEPATH/diabook-pink/style-wide.css");
	}
	if($diabook_font_size == "15"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 15px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 15px;
  					}
		";
	}
	if($diabook_font_size == "14"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 14px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 14px;
  					}
		";
	}
	if($diabook_font_size == "13.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13.5px;
  					}
		";
	}
	if($diabook_font_size == "13"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 13px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 13px;
  					}
		";
	}
	if($diabook_font_size == "12.5"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12.5px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12.5px;
  					}
		";
	}
	if($diabook_font_size == "12"){
		echo "
			.wall-item-container .wall-item-content {
  					font-size: 12px;
  					}
  					
			.wall-item-photo-container .wall-item-content {
  					font-size: 12px;
  					}
		";
	}
	if($line_height == "1.5"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.5;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.5;
  					}
		";
	}	
	if($line_height == "1.4"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.4;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.4;
  					}
		";
	}
	if($line_height == "1.3"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.3;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.3;
  					}
		";
	}
	if($line_height == "1.2"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.2;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.2;
  					}
		";
	}
	if($line_height == "1.1"){
		echo "
			.wall-item-container .wall-item-content {
  					line-height: 1.1;
  					}
  					
			.wall-item-photo-container .wall-item-content {
 					line-height: 1.1;
  					}
		";
	}
	}
	}