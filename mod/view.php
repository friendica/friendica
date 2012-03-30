<?php
/**
 * load view/theme/$current_theme/style.php with friendica contex
 */
 
function view_init($a){
	header("Content-Type: text/css");
		
	if ($a->argc == 4){
		$theme = $a->argv[2];
		$THEMEPATH = "view/theme/$theme";
		require_once("view/theme/$theme/style.php");
	}
	
	killme();
}
