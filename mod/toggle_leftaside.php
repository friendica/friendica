<?php
function toggle_leftaside_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	    if( get_pconfig( local_user(), 'system', 'leftsidebar') ) 
		del_pconfig( local_user(), 'system', 'leftsidebar') ;
    
	    else
		set_pconfig( local_user(), 'system', 'leftsidebar', 'show') ;

        logger( "get_pconfig: " . get_pconfig( local_user(), 'system', 'leftsidebar'), LOGGER_DEBUG) ;

	if(isset($_GET['address']))
		$address = $_GET['address'];
	else
		$address = $a->get_baseurl();

	goaway($address);}
?>
