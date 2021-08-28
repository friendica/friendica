<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core;

use Friendica\DI;

/**
 * Manage compatibility with federated networks
 */
class Protocol
{
	// Native support
	const ACTIVITYPUB = 'apub';    // ActivityPub (Pleroma, Mastodon, Osada, ...)
	const DFRN        = 'dfrn';    // Friendica, Mistpark, other DFRN implementations
	const DIASPORA    = 'dspr';    // Diaspora, Hubzilla, Socialhome, Ganggo
	const FEED        = 'feed';    // RSS/Atom feeds with no known "post/notify" protocol
	const MAIL        = 'mail';    // IMAP/POP
	const OSTATUS     = 'stat';    // GNU Social and other OStatus implementations

	const NATIVE_SUPPORT = [self::DFRN, self::DIASPORA, self::OSTATUS, self::FEED, self::MAIL, self::ACTIVITYPUB];

	const FEDERATED = [self::DFRN, self::DIASPORA, self::OSTATUS, self::ACTIVITYPUB];

	const SUPPORT_PRIVATE = [self::DFRN, self::DIASPORA, self::MAIL, self::ACTIVITYPUB, self::PUMPIO];

	// Supported through a connector
	const DIASPORA2 = 'dspc';    // Diaspora connector
	const LINKEDIN  = 'lnkd';    // LinkedIn
	const PUMPIO    = 'pump';    // pump.io
	const STATUSNET = 'stac';    // Statusnet connector
	const TWITTER   = 'twit';    // Twitter
	const DISCOURSE = 'dscs';    // Discourse

	// Dead protocols
	const APPNET    = 'apdn';    // app.net - Dead protocol
	const FACEBOOK  = 'face';    // Facebook API - Not working anymore, API is closed
	const GPLUS     = 'goog';    // Google+ - Dead in 2019

	// Currently unsupported
	const ICALENDAR = 'ical';    // iCalendar
	const MYSPACE   = 'mysp';    // MySpace
	const NEWS      = 'nntp';    // Network News Transfer Protocol
	const PNUT      = 'pnut';    // pnut.io
	const XMPP      = 'xmpp';    // XMPP
	const ZOT       = 'zot!';    // Zot!

	const PHANTOM   = 'unkn';    // Place holder

	/**
	 * Returns the address string for the provided profile URL
	 *
	 * @param string $profile_url
	 * @return string
	 * @throws \Exception
	 */
	public static function getAddrFromProfileUrl($profile_url)
	{
		$network = self::matchByProfileUrl($profile_url, $matches);

		if ($network === self::PHANTOM) {
			return "";
		}

		$addr = $matches[2] . '@' . $matches[1];

		return $addr;
	}

	/**
	 * Guesses the network from a profile URL
	 *
	 * @param string $profile_url
	 * @param array  $matches preg_match return array: [0] => Full match [1] => hostname [2] => username
	 * @return string
	 */
	public static function matchByProfileUrl($profile_url, &$matches = [])
	{
		if (preg_match('=https?://(twitter\.com)/(.*)=ism', $profile_url, $matches)) {
			return self::TWITTER;
		}

		if (preg_match('=https?://(alpha\.app\.net)/(.*)=ism', $profile_url, $matches)) {
			return self::APPNET;
		}

		if (preg_match('=https?://(plus\.google\.com)/(.*)=ism', $profile_url, $matches)) {
			return self::GPLUS;
		}

		if (preg_match('=https?://(.*)/profile/(.*)=ism', $profile_url, $matches)) {
			return self::DFRN;
		}

		if (preg_match('=https?://(.*)/u/(.*)=ism', $profile_url, $matches)) {
			return self::DIASPORA;
		}

		if (preg_match('=https?://(.*)/channel/(.*)=ism', $profile_url, $matches)) {
			// RedMatrix/Hubzilla is identified as Diaspora - friendica can't connect directly to it
			return self::DIASPORA;
		}

		if (preg_match('=https?://(.*)/user/(.*)=ism', $profile_url, $matches)) {
			$statusnet_host = $matches[1];
			$statusnet_user = $matches[2];
			$UserData = DI::httpClient()->fetch('http://' . $statusnet_host . '/api/users/show.json?user_id=' . $statusnet_user);
			$user = json_decode($UserData);
			if ($user) {
				$matches[2] = $user->screen_name;
				return self::STATUSNET;
			}
		}

		// Mastodon, Pleroma
		if (preg_match('=https?://(.+?)/users/(.+)=ism', $profile_url, $matches)
			|| preg_match('=https?://(.+?)/@(.+)=ism', $profile_url, $matches)
		) {
			return self::ACTIVITYPUB;
		}

		// pumpio (http://host.name/user)
		if (preg_match('=https?://([\.\w]+)/([\.\w]+)$=ism', $profile_url, $matches)) {
			return self::PUMPIO;
		}

		return self::PHANTOM;
	}

	/**
	 * Returns a formatted mention from a profile URL and a display name
	 *
	 * @param string $profile_url
	 * @param string $display_name
	 * @return string
	 * @throws \Exception
	 */
	public static function formatMention($profile_url, $display_name)
	{
		return $display_name . ' (' . self::getAddrFromProfileUrl($profile_url) . ')';
	}
}
