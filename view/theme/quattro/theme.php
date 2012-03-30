<?php




$quattro_align = get_pconfig(local_user(), 'quattro', 'align' );

if(local_user() && $quattro_align=="center"){
	
	$a->page['htmlhead'].="
	<style>
		html { width: 100%; margin:0px; padding:0px; }
		body {
			margin: 50px auto;
			width: 900px;
		}
	</style>
	";
	
}
