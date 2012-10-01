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

    

    $textarea_font_size = false;
    $post_font_size = false;
    
    $site_textarea_font_size = get_config("quattro","tfs");
    $site_post_font_size = get_config("quattro","pfs");
    if ($site_textarea_font_size===false) $site_textarea_font_size="20";
    if ($site_post_font_size===false) $site_post_font_size="12";
    
   	if (local_user()) {
        $textarea_font_size = get_pconfig(local_user(), "quattro","tfs");
        $post_font_size = get_pconfig(local_user(), "quattro","pfs");    
	} 
    
    if ($textarea_font_size===false) $textarea_font_size = $site_textarea_font_size;
    if ($post_font_size===false) $post_font_size = $site_post_font_size;

    echo "
        textarea { font-size: ${textarea_font_size}px; }
        .wall-item-comment-wrapper .comment-edit-text-full { font-size: ${textarea_font_size}px; }
        #jot .profile-jot-text:focus { font-size: ${textarea_font_size}px; }
        .wall-item-container .wall-item-content  { font-size: ${post_font_size}px; }
    ";