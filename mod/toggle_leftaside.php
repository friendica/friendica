<?php
function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

info( 'get_pconfig: ' . get_pconfig( local_user(), 'system', 'leftsidebar') ) ;
}
?>
