<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Friendica is a communications platform for integrated social communications
 * utilising decentralised communications and linkage to several indie social
 * projects - as well as popular mainstream providers.
 *
 * Our mission is to free our friends and families from the clutches of
 * data-harvesting corporations, and pave the way to a future where social
 * communications are free and open and flow between alternate providers as
 * easily as email does today.
 */

use Friendica\Model\Contact;

define('FRIENDICA_PLATFORM',     'Friendica');
define('FRIENDICA_CODENAME',     'Siberian Iris');
define('FRIENDICA_VERSION',      '2021.12-rc');
define('DFRN_PROTOCOL_VERSION',  '2.23');
define('NEW_TABLE_STRUCTURE_VERSION', 1288);

/**
 * Constant with a HTML line break.
 *
 * Contains a HTML line break (br) element and a real carriage return with line
 * feed for the source.
 * This can be used in HTML and JavaScript where needed a line break.
 */
define('EOL',                    "<br />\r\n");

/**
 * @name CP
 *
 * Type of the community page
 * @{
 */
define('CP_NO_INTERNAL_COMMUNITY', -2);
define('CP_NO_COMMUNITY_PAGE',     -1);
define('CP_USERS_ON_SERVER',        0);
define('CP_GLOBAL_COMMUNITY',       1);
define('CP_USERS_AND_GLOBAL',       2);
/**
 * @}
 */

/**
 * @name Gravity
 *
 * Item weight for query ordering
 * @{
 */
define('GRAVITY_PARENT',       0);
define('GRAVITY_ACTIVITY',     3);
define('GRAVITY_COMMENT',      6);
define('GRAVITY_UNKNOWN',      9);
/* @}*/

/**
 * @name Priority
 *
 * Process priority for the worker
 * @{
 */
define('PRIORITY_UNDEFINED',   0);
define('PRIORITY_CRITICAL',   10);
define('PRIORITY_HIGH',       20);
define('PRIORITY_MEDIUM',     30);
define('PRIORITY_LOW',        40);
define('PRIORITY_NEGLIGIBLE', 50);
define('PRIORITIES', [PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE]);
/* @}*/

// Normally this constant is defined - but not if "pcntl" isn't installed
if (!defined("SIGTERM")) {
	define("SIGTERM", 15);
}

/**
 * Depending on the PHP version this constant does exist - or not.
 * See here: http://php.net/manual/en/curl.constants.php#117928
 */
if (!defined('CURLE_OPERATION_TIMEDOUT')) {
	define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
}

if (!function_exists('exif_imagetype')) {
	function exif_imagetype($file)
	{
		$size = getimagesize($file);
		return $size[2];
	}
}

/**
 * Returns the user id of locally logged in user or false.
 *
 * @return int|bool user id or false
 */
function local_user()
{
	if (!empty($_SESSION['authenticated']) && !empty($_SESSION['uid'])) {
		return intval($_SESSION['uid']);
	}
	return false;
}

/**
 * Returns the public contact id of logged in user or false.
 *
 * @return int|bool public contact id or false
 */
function public_contact()
{
	static $public_contact_id = false;

	if (!$public_contact_id && !empty($_SESSION['authenticated'])) {
		if (!empty($_SESSION['my_address'])) {
			// Local user
			$public_contact_id = intval(Contact::getIdForURL($_SESSION['my_address'], 0, false));
		} elseif (!empty($_SESSION['visitor_home'])) {
			// Remote user
			$public_contact_id = intval(Contact::getIdForURL($_SESSION['visitor_home'], 0, false));
		}
	} elseif (empty($_SESSION['authenticated'])) {
		$public_contact_id = false;
	}

	return $public_contact_id;
}

/**
 * Returns public contact id of authenticated site visitor or false
 *
 * @return int|bool visitor_id or false
 */
function remote_user()
{
	if (empty($_SESSION['authenticated'])) {
		return false;
	}

	if (!empty($_SESSION['visitor_id'])) {
		return intval($_SESSION['visitor_id']);
	}

	return false;
}

/**
 * Show an error message to user.
 *
 * This function save text in session, to be shown to the user at next page load
 *
 * @param string $s - Text of notice
 */
function notice($s)
{
	if (empty($_SESSION)) {
		return;
	}

	if (empty($_SESSION['sysmsg'])) {
		$_SESSION['sysmsg'] = [];
	}

	$_SESSION['sysmsg'][] = $s;
}

/**
 * Show an info message to user.
 *
 * This function save text in session, to be shown to the user at next page load
 *
 * @param string $s - Text of notice
 */
function info($s)
{
	if (empty($_SESSION)) {
		return;
	}

	if (empty($_SESSION['sysmsg_info'])) {
		$_SESSION['sysmsg_info'] = [];
	}

	$_SESSION['sysmsg_info'][] = $s;
}
