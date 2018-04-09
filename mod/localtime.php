<?php
/**
 * @file mod/localtime.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

function localtime_post(App $a)
{
	$t = $_REQUEST['time'];
	if (! $t) {
		$t = 'now';
	}

	$bd_format = L10n::t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	if ($_POST['timezone']) {
		$a->data['mod-localtime'] = DateTimeFormat::convert($t, $_POST['timezone'], 'UTC', $bd_format);
	}
}

function localtime_content(App $a)
{
	$t = $_REQUEST['time'];
	if (! $t) {
		$t = 'now';
	}

	$o  = '<h3>' . L10n::t('Time Conversion') . '</h3>';

	$o .= '<p>' . L10n::t('Friendica provides this service for sharing events with other networks and friends in unknown timezones.') . '</p>';



	$o .= '<p>' . L10n::t('UTC time: %s', $t) . '</p>';

	if ($_REQUEST['timezone']) {
		$o .= '<p>' . L10n::t('Current timezone: %s', $_REQUEST['timezone']) . '</p>';
	}

	if (x($a->data, 'mod-localtime')) {
		$o .= '<p>' . L10n::t('Converted localtime: %s', $a->data['mod-localtime']) . '</p>';
	}


	$o .= '<form action ="' . System::baseUrl() . '/localtime?f=&time=' . $t . '" method="post" >';

	$o .= '<p>' . L10n::t('Please select your timezone:') . '</p>';

	$o .= Temporal::getTimezoneSelect(($_REQUEST['timezone']) ? $_REQUEST['timezone'] : 'America/Los_Angeles');

	$o .= '<input type="submit" name="submit" value="' . L10n::t('Submit') . '" /></form>';

	return $o;
}
