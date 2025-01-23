<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Hook;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\OpenWebAuthToken;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * Authentication via OpenWebAuth
 */
class OpenWebAuth
{
	/**
	 * Process the 'zrl' parameter and initiate the remote authentication.
	 *
	 * This method checks if the visitor has a public contact entry and
	 * redirects the visitor to his/her instance to start the magic auth (Authentication)
	 * process.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/channel.php
	 *
	 * The implementation for Friendica sadly differs in some points from the one for Hubzilla:
	 * - Hubzilla uses the "zid" parameter, while for Friendica it had been replaced with "zrl"
	 * - There seem to be some reverse authentication (rmagic) that isn't implemented in Friendica at all
	 *
	 * It would be favourable to harmonize the two implementations.
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function zrlInit()
	{
		$my_url = DI::userSession()->getMyUrl();
		$my_url = Network::isUrlValid($my_url);

		if (empty($my_url) || DI::userSession()->getLocalUserId()) {
			return;
		}

		$addr = $_GET['addr'] ?? $my_url;

		$arr = ['zrl' => $my_url, 'url' => DI::args()->getCommand()];
		Hook::callAll('zrl_init', $arr);

		// Try to find the public contact entry of the visitor.
		$contact = Contact::getByURL($my_url, null, ['id', 'url', 'gsid']);
		if (empty($contact)) {
			DI::logger()->info('No contact record found', ['url' => $my_url]);
			return;
		}

		if (DI::userSession()->getRemoteUserId() && DI::userSession()->getRemoteUserId() == $contact['id']) {
			DI::logger()->info('The visitor is already authenticated', ['url' => $my_url]);
			return;
		}

		$gserver = DBA::selectFirst('gserver', ['url', 'authredirect'], ['id' => $contact['gsid']]);
		if (empty($gserver) || empty($gserver['authredirect'])) {
			DI::logger()->info('No server record found or magic path not defined for server', ['id' => $contact['gsid'], 'gserver' => $gserver]);
			return;
		}

		// Avoid endless loops
		$cachekey = 'zrlInit:' . $my_url;
		if (DI::cache()->get($cachekey)) {
			DI::logger()->info('URL ' . $my_url . ' already tried to authenticate.');
			return;
		} else {
			DI::cache()->set($cachekey, true, Duration::MINUTE);
		}

		DI::logger()->info('Not authenticated. Invoking reverse magic-auth', ['url' => $my_url]);

		// Remove the "addr" parameter from the destination. It is later added as separate parameter again.
		$addr_request = 'addr=' . urlencode($addr);
		$query        = rtrim(str_replace($addr_request, '', DI::args()->getQueryString()), '?&');

		// The other instance needs to know where to redirect.
		$dest = urlencode(DI::baseUrl() . '/' . $query);

		if ($gserver['url'] != DI::baseUrl() && !strstr($dest, '/magic')) {
			$magic_path = $gserver['authredirect'] . '?f=&rev=1&owa=1&dest=' . $dest . '&' . $addr_request;

			DI::logger()->info('Doing magic auth for visitor ' . $my_url . ' to ' . $magic_path);
			System::externalRedirect($magic_path);
		}
	}

	/**
	 * OpenWebAuth authentication.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/zid.php
	 *
	 * @param string $token
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function init(string $token)
	{
		$appHelper = DI::appHelper();

		// Clean old OpenWebAuthToken entries.
		OpenWebAuthToken::purge('owt', '3 MINUTE');

		// Check if the token we got is the same one
		// we have stored in the database.
		$visitor_handle = OpenWebAuthToken::getMeta('owt', 0, $token);

		if ($visitor_handle === false) {
			return;
		}

		$visitor = self::addVisitorCookieForHandle($visitor_handle);
		if (empty($visitor)) {
			return;
		}

		$arr = [
			'visitor' => $visitor,
			'url'     => DI::args()->getQueryString()
		];
		/**
		 * @hooks magic_auth_success
		 *   Called when a magic-auth was successful.
		 *   * \e array \b visitor
		 *   * \e string \b url
		 */
		Hook::callAll('magic_auth_success', $arr);

		$appHelper->setContactId($arr['visitor']['id']);

		DI::sysmsg()->addInfo(DI::l10n()->t('OpenWebAuth: %1$s welcomes %2$s', DI::baseUrl()->getHost(), $visitor['name']));

		DI::logger()->info('OpenWebAuth: auth success from ' . $visitor['addr']);
	}

	/**
	 * Set the visitor cookies (see remote_user()) for the given handle
	 *
	 * @param string $handle Visitor handle
	 *
	 * @return array Visitor contact array
	 */
	public static function addVisitorCookieForHandle(string $handle): array
	{
		$appHelper = DI::appHelper();

		// Try to find the public contact entry of the visitor.
		$cid = Contact::getIdForURL($handle);
		if (!$cid) {
			DI::logger()->info('Handle not found', ['handle' => $handle]);
			return [];
		}

		$visitor = Contact::getById($cid);

		// Authenticate the visitor.
		DI::userSession()->setMultiple([
			'authenticated'  => 1,
			'visitor_id'     => $visitor['id'],
			'visitor_handle' => $visitor['addr'],
			'visitor_home'   => $visitor['url'],
			'my_url'         => $visitor['url'],
			'remote_comment' => $visitor['subscribe'],
		]);

		DI::userSession()->setVisitorsContacts($visitor['url']);

		$appHelper->setContactId($visitor['id']);

		DI::logger()->info('Authenticated visitor', ['url' => $visitor['url']]);

		return $visitor;
	}

	/**
	 * Set the visitor cookies (see remote_user()) for signed HTTP requests
	 *
	 * @param array $server The content of the $_SERVER superglobal
	 * @return array Visitor contact array
	 * @throws InternalServerErrorException
	 */
	public static function addVisitorCookieForHTTPSigner(array $server): array
	{
		$requester = HTTPSignature::getSigner('', $server);
		if (empty($requester)) {
			return [];
		}
		return self::addVisitorCookieForHandle($requester);
	}

	/**
	 * Returns URL with URL-encoded zrl parameter
	 *
	 * @param string $url   URL to enhance
	 * @param bool   $force Either to force adding zrl parameter
	 *
	 * @return string URL with 'zrl' parameter or original URL in case of no Friendica profile URL
	 */
	public static function getZrlUrl(string $url, bool $force = false): string
	{
		if (!strlen($url)) {
			return $url;
		}
		if (!strpos($url, '/profile/') && !$force) {
			return $url;
		}
		if ($force && substr($url, -1, 1) !== '/') {
			$url = $url . '/';
		}

		$achar = strpos($url, '?') ? '&' : '?';
		$mine  = DI::userSession()->getMyUrl();

		if ($mine && !Strings::compareLink($mine, $url)) {
			return $url . $achar . 'zrl=' . urlencode($mine);
		}

		return $url;
	}
}
