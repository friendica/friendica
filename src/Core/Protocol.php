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
 */

namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;

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
	 * Returns whether the provided protocol supports following
	 *
	 * @param $protocol
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function supportsFollow($protocol): bool
	{
		if (in_array($protocol, self::NATIVE_SUPPORT)) {
			return true;
		}

		$hook_data = [
			'protocol' => $protocol,
			'result' => null
		];
		Hook::callAll('support_follow', $hook_data);

		return $hook_data['result'] === true;
	}

	/**
	 * Returns whether the provided protocol supports revoking inbound follows
	 *
	 * @param $protocol
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function supportsRevokeFollow($protocol): bool
	{
		if (in_array($protocol, self::NATIVE_SUPPORT)) {
			return true;
		}

		$hook_data = [
			'protocol' => $protocol,
			'result' => null
		];
		Hook::callAll('support_revoke_follow', $hook_data);

		return $hook_data['result'] === true;
	}

	/**
	 * Send a follow message to a remote server.
	 *
	 * @param int     $uid      User Id
	 * @param array   $contact  Contact being followed
	 * @param ?string $protocol Expected protocol
	 * @return bool Only returns false in the unlikely case an ActivityPub contact ID doesn't exist (???)
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function follow(int $uid, array $contact, ?string $protocol = null): bool
	{
		$owner = User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			return true;
		}

		$protocol = $protocol ?? $contact['protocol'];

		if (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
			// create a follow slap
			$item = [
				'verb'    => Activity::FOLLOW,
				'gravity' => Item::GRAVITY_ACTIVITY,
				'follow'  => $contact['url'],
				'body'    => '',
				'title'   => '',
				'guid'    => '',
				'uri-id'  => 0,
			];

			$slap = OStatus::salmon($item, $owner);

			if (!empty($contact['notify'])) {
				Salmon::slapper($owner, $contact['notify'], $slap);
			}
		} elseif ($protocol == Protocol::DIASPORA) {
			$contact = Diaspora::sendShare($owner, $contact);
			Logger::notice('share returns: ' . $contact);
		} elseif ($protocol == Protocol::ACTIVITYPUB) {
			$activity_id = ActivityPub\Transmitter::activityIDFromContact($contact['id']);
			if (empty($activity_id)) {
				// This really should never happen
				return false;
			}

			$success = ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $owner['uid'], $activity_id);
			Logger::notice('Follow returns: ' . $success);
		}

		return true;
	}

	/**
	 * Sends an unfollow message. Does not remove the contact
	 *
	 * @param array $contact Target public contact (uid = 0) array
	 * @param array $user    Source local user array
	 * @return bool|null true if successful, false if not, null if no remote action was performed
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function unfollow(array $contact, array $user): ?bool
	{
		if (empty($contact['network'])) {
			Logger::notice('Contact has got no network, we quit here', ['id' => $contact['id']]);
			return null;
		}

		$protocol = $contact['network'];
		if (($protocol == Protocol::DFRN) && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
			// create an unfollow slap
			$item = [
				'verb'    => Activity::O_UNFOLLOW,
				'gravity' => Item::GRAVITY_ACTIVITY,
				'follow'  => $contact['url'],
				'body'    => '',
				'title'   => '',
				'guid'    => '',
				'uri-id'  => 0,
			];

			$slap = OStatus::salmon($item, $user);

			if (empty($contact['notify'])) {
				Logger::notice('OStatus/DFRN Contact is missing notify, we quit here', ['id' => $contact['id']]);
				return null;
			}

			return Salmon::slapper($user, $contact['notify'], $slap) === 0;
		} elseif ($protocol == Protocol::DIASPORA) {
			return Diaspora::sendUnshare($user, $contact) > 0;
		} elseif ($protocol == Protocol::ACTIVITYPUB) {
			return ActivityPub\Transmitter::sendContactUndo($contact['url'], $contact['id'], $user['uid']);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid'     => $user['uid'],
			'result'  => null,
		];
		Hook::callAll('unfollow', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Revoke an incoming follow from the provided contact
	 *
	 * @param array $contact Target public contact (uid == 0) array
	 * @param int   $uid     Source local user id
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function revokeFollow(array $contact, int $uid): ?bool
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Missing network key in contact array');
		}

		$protocol = $contact['network'];
		if ($protocol == Protocol::DFRN && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if ($protocol == Protocol::ACTIVITYPUB) {
			return ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $uid);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid'     => $uid,
			'result'  => null,
		];
		Hook::callAll('revoke_follow', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Send a block message to a remote server. Only useful for connector addons.
	 *
	 * @param array $contact Public contact record to block
	 * @param int   $uid     User issuing the block
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function block(array $contact, int $uid): ?bool
	{
		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid' => $uid,
			'result' => null,
		];
		Hook::callAll('block', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Send an unblock message to a remote server. Only useful for connector addons.
	 *
	 * @param array $contact Public contact record to unblock
	 * @param int   $uid     User revoking the block
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function unblock(array $contact, int $uid): ?bool
	{
		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid' => $uid,
			'result' => null,
		];
		Hook::callAll('unblock', $hook_data);

		return $hook_data['result'];
	}
}
