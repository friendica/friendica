<?php
/*
 * @file src/Core/Protocol.php
 */
namespace Friendica\Core;

use Friendica\Util\Network;

/**
 * Manage compatibility with federated networks
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Protocol
{
	// Native support
	const ACTIVITYPUB = 'apub';    // ActivityPub
	const DFRN        = 'dfrn';    // Friendica, Mistpark, other DFRN implementations
	const DIASPORA    = 'dspr';    // Diaspora
	const FEED        = 'feed';    // RSS/Atom feeds with no known "post/notify" protocol
	const MAIL        = 'mail';    // IMAP/POP
	const OSTATUS     = 'stat';    // GNU-social, Pleroma, Mastodon, other OStatus implementations

	const NATIVE_SUPPORT = [self::DFRN, self::DIASPORA, self::OSTATUS, self::FEED, self::MAIL, self::ACTIVITYPUB];

	// Supported through a connector
	const APPNET    = 'apdn';    // app.net - Dead protocol
	const DIASPORA2 = 'dspc';    // Diaspora connector
	const FACEBOOK  = 'face';    // Facebook API
	const GPLUS     = 'goog';    // Google+
	const LINKEDIN  = 'lnkd';    // LinkedIn
	const PUMPIO    = 'pump';    // pump.io
	const STATUSNET = 'stac';    // Statusnet connector
	const TWITTER   = 'twit';    // Twitter

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
	 * @throws Exception
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
	 * @param array  $matches     preg_match return array: [0] => Full match [1] => hostname [2] => username
	 * @return type
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
			$UserData = Network::fetchUrl('http://' . $statusnet_host . '/api/users/show.json?user_id=' . $statusnet_user);
			$user = json_decode($UserData);
			if ($user) {
				$matches[2] = $user->screen_name;
				return self::STATUSNET;
			}
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
	 */
	public static function formatMention($profile_url, $display_name)
	{
		return $display_name . ' (' . self::getAddrFromProfileUrl($profile_url) . ')';
	}
}
