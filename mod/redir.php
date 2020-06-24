<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Util\Network;
use Friendica\Util\Strings;

function redir_init(App $a) {
	if (!Session::isAuthenticated()) {
		throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
	}

	$url = $_GET['url'] ?? '';
	$quiet = !empty($_GET['quiet']) ? '&quiet=1' : '';

	if ($a->argc > 1 && intval($a->argv[1])) {
		$cid = intval($a->argv[1]);
	} else {
		$cid = 0;
	}

	// Try magic auth before the legacy stuff
	redir_magic($a, $cid, $url);

	if (empty($cid)) {
		throw new \Friendica\Network\HTTPException\BadRequestException(DI::l10n()->t('Bad Request.'));
	}

	$fields = ['id', 'uid', 'nurl', 'url', 'addr', 'name', 'network', 'poll', 'issued-id', 'dfrn-id', 'duplex', 'pending'];
	$contact = DBA::selectFirst('contact', $fields, ['id' => $cid, 'uid' => [0, local_user()]]);
	if (!DBA::isResult($contact)) {
		throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('Contact not found.'));
	}

	$contact_url = $contact['url'];

	if (!empty($a->contact['id']) && $a->contact['id'] == $cid) {
		// Local user is already authenticated.
		redir_check_url($contact_url, $url);
		$a->redirect($url ?: $contact_url);
	}

	if ($contact['uid'] == 0 && local_user()) {
		// Let's have a look if there is an established connection
		// between the public contact we have found and the local user.
		$contact = DBA::selectFirst('contact', $fields, ['nurl' => $contact['nurl'], 'uid' => local_user()]);

		if (DBA::isResult($contact)) {
			$cid = $contact['id'];
		}

		if (!empty($a->contact['id']) && $a->contact['id'] == $cid) {
			// Local user is already authenticated.
			redir_check_url($contact_url, $url);
			$target_url = $url ?: $contact_url;
			Logger::log($contact['name'] . " is already authenticated. Redirecting to " . $target_url, Logger::DEBUG);
			$a->redirect($target_url);
		}
	}

	if (remote_user()) {
		$host = substr(DI::baseUrl()->getUrlPath() . (DI::baseUrl()->getUrlPath() ? '/' . DI::baseUrl()->getUrlPath() : ''), strpos(DI::baseUrl()->getUrlPath(), '://') + 3);
		$remotehost = substr($contact['addr'], strpos($contact['addr'], '@') + 1);

		// On a local instance we have to check if the local user has already authenticated
		// with the local contact. Otherwise the local user would ask the local contact
		// for authentification everytime he/she is visiting a profile page of the local
		// contact.
		if (($host == $remotehost) && (Session::getRemoteContactID(Session::get('visitor_visiting')) == Session::get('visitor_id'))) {
			// Remote user is already authenticated.
			redir_check_url($contact_url, $url);
			$target_url = $url ?: $contact_url;
			Logger::log($contact['name'] . " is already authenticated. Redirecting to " . $target_url, Logger::DEBUG);
			$a->redirect($target_url);
		}
	}

	// Doing remote auth with dfrn.
	if (local_user() && (!empty($contact['dfrn-id']) || !empty($contact['issued-id'])) && empty($contact['pending'])) {
		$dfrn_id = $orig_id = (($contact['issued-id']) ? $contact['issued-id'] : $contact['dfrn-id']);

		if ($contact['duplex'] && $contact['issued-id']) {
			$orig_id = $contact['issued-id'];
			$dfrn_id = '1:' . $orig_id;
		}
		if ($contact['duplex'] && $contact['dfrn-id']) {
			$orig_id = $contact['dfrn-id'];
			$dfrn_id = '0:' . $orig_id;
		}

		$sec = Strings::getRandomHex();

		$fields = ['uid' => local_user(), 'cid' => $cid, 'dfrn_id' => $dfrn_id,
			'sec' => $sec, 'expire' => time() + 45];
		DBA::insert('profile_check', $fields);

		Logger::log('mod_redir: ' . $contact['name'] . ' ' . $sec, Logger::DEBUG);

		$dest = (!empty($url) ? '&destination_url=' . $url : '');

		System::externalRedirect($contact['poll'] . '?dfrn_id=' . $dfrn_id
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest . $quiet);
	}

	if (empty($url)) {
		throw new \Friendica\Network\HTTPException\BadRequestException(DI::l10n()->t('Bad Request.'));
	}

	// If we don't have a connected contact, redirect with
	// the 'zrl' parameter.
	$my_profile = Profile::getMyURL();

	if (!empty($my_profile) && !Strings::compareLink($my_profile, $url)) {
		$separator = strpos($url, '?') ? '&' : '?';

		$url .= $separator . 'zrl=' . urlencode($my_profile);
	}

	Logger::log('redirecting to ' . $url, Logger::DEBUG);
	$a->redirect($url);
}

function redir_magic($a, $cid, $url)
{
	$visitor = Profile::getMyURL();
	if (!empty($visitor)) {
		Logger::info('Got my url', ['visitor' => $visitor]);
	}

	$contact = DBA::selectFirst('contact', ['url'], ['id' => $cid]);
	if (!DBA::isResult($contact)) {
		Logger::info('Contact not found', ['id' => $cid]);
		throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('Contact not found.'));
	} else {
		$contact_url = $contact['url'];
		redir_check_url($contact_url, $url);
		$target_url = $url ?: $contact_url;
	}

	$basepath = Contact::getBasepath($contact_url);

	// We don't use magic auth when there is no visitor, we are on the same system or we visit our own stuff
	if (empty($visitor) || Strings::compareLink($basepath, DI::baseUrl()) || Strings::compareLink($contact_url, $visitor)) {
		Logger::info('Redirecting without magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
		DI::app()->redirect($target_url);
	}

	// Test for magic auth on the target system
	$serverret = Network::curl($basepath . '/magic');
	if ($serverret->isSuccess()) {
		$separator = strpos($target_url, '?') ? '&' : '?';
		$target_url .= $separator . 'zrl=' . urlencode($visitor) . '&addr=' . urlencode($contact_url);

		Logger::info('Redirecting with magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
		System::externalRedirect($target_url);
	} else {
		Logger::info('No magic for contact', ['contact' => $contact_url]);
	}
}

function redir_check_url(string $contact_url, string $url)
{
	if (empty($contact_url) || empty($url)) {
		return;
	}

	$url_host = parse_url($url, PHP_URL_HOST);
	if (empty($url_host)) {
		$url_host = parse_url(DI::baseUrl(), PHP_URL_HOST);
	}

	$contact_url_host = parse_url($contact_url, PHP_URL_HOST);

	if ($url_host == $contact_url_host) {
		return;
	}

	Logger::error('URL check host mismatch', ['contact' => $contact_url, 'url' => $url]);
	throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
}
