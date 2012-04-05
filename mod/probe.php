<?php

require_once('include/Scrape.php');

function probe_content(&$a) {

	$o .= '<h3>Probe Diagnostic</h3>';

	$o .= '<form action="probe" method="get">';
	$o .= 'Lookup address: <input type="text" style="width: 250px;" name="addr" value="' . $_GET['addr'] .'" />';
	$o .= '<input type="submit" name="submit" value="Submit" /></form>'; 

	$o .= '<br /><br />';

	if(x($_GET,'addr')) {

		$addr = trim($_GET['addr']);
		$res = probe_url($addr);
		$o .= '<pre>';
		$o .= str_replace("\n",'<br />',print_r($res,true));
		$o .= '</pre>';
	}
	return $o;
}
