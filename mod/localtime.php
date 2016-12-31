<?php

require_once('include/datetime.php');


function localtime_post(&$a) {

	$t = $_REQUEST['time'];
	if(! $t)
		$t = 'now';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	if($_POST['timezone'])
		$a->data['mod-localtime'] = datetime_convert('UTC',$_POST['timezone'],$t,$bd_format);

}

function localtime_content(&$a) {
	$t = $_REQUEST['time'];
	if(! $t)
		$t = 'now';

	$o .= '<h3>' . t('Time Conversion') . '</h3>';

	$o .= '<p>' . t('Friendica provides this service for sharing events with other networks and friends in unknown timezones.') . '</p>';



	$o .= '<p>' . sprintf( t('UTC time: %s'), $t) . '</p>';

	if($_REQUEST['timezone'])
		$o .= '<p>' . sprintf( t('Current timezone: %s'), $_REQUEST['timezone']) . '</p>';

	if(x($a->data,'mod-localtime'))
		$o .= '<p>' . sprintf( t('Converted localtime: %s'),$a->data['mod-localtime']) . '</p>';


	$o .= '<form action ="' . App::get_baseurl() . '/localtime?f=&time=' . $t . '" method="post" >';

	$o .= '<p>' . t('Please select your timezone:') . '</p>'; 

	$o .= select_timezone(($_REQUEST['timezone']) ? $_REQUEST['timezone'] : 'America/Los_Angeles');

	$o .= '<input type="submit" name="submit" value="' . t('Submit') . '" /></form>';

	return $o;

}