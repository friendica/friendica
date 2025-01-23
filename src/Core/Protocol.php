<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ActivityPub\Transmitter;
use Friendica\Protocol\Diaspora;

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

	const NATIVE_SUPPORT = [self::DFRN, self::DIASPORA, self::FEED, self::MAIL, self::ACTIVITYPUB];

	const FEDERATED = [self::DFRN, self::DIASPORA, self::ACTIVITYPUB];

	const SUPPORT_PRIVATE = [self::DFRN, self::DIASPORA, self::MAIL, self::ACTIVITYPUB, self::PUMPIO];

	// Supported through a connector
	const BLUESKY   = 'bsky';    // Bluesky
	const DIASPORA2 = 'dspc';    // Diaspora connector
	const DISCOURSE = 'dscs';    // Discourse
	const PNUT      = 'pnut';    // pnut.io
	const PUMPIO    = 'pump';    // pump.io
	const TUMBLR    = 'tmbl';    // Tumblr
	const TWITTER   = 'twit';    // Twitter

	// Dead protocols
	const APPNET    = 'apdn';    // app.net - Dead protocol
	const FACEBOOK  = 'face';    // Facebook API - Not working anymore, API is closed
	const GPLUS     = 'goog';    // Google+ - Dead in 2019
	const OSTATUS   = 'stat';    // GNU Social and other OStatus implementations
	const STATUSNET = 'stac';    // Statusnet connector

	// Currently unsupported
	const ICALENDAR = 'ical';    // iCalendar
	const LINKEDIN  = 'lnkd';    // LinkedIn
	const MYSPACE   = 'mysp';    // MySpace
	const NEWS      = 'nntp';    // Network News Transfer Protocol
	const XMPP      = 'xmpp';    // XMPP
	const ZOT       = 'zot!';    // Zot!

	const PHANTOM = 'unkn';    // Place holder

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
			'result'   => null
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
			'result'   => null
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

		if ($protocol == self::DIASPORA) {
			$contact = Diaspora::sendShare($owner, $contact);
			DI::logger()->notice('share returns: ' . $contact);
		} elseif (in_array($protocol, [self::ACTIVITYPUB, self::DFRN])) {
			$activity_id = ActivityPub\Transmitter::activityIDFromContact($contact['id']);
			if (empty($activity_id)) {
				// This really should never happen
				return false;
			}

			$success = ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $owner['uid'], $activity_id);
			DI::logger()->notice('Follow returns: ' . $success);
		}

		return true;
	}

	/**
	 * Sends an unfollow message. Does not remove the contact
	 *
	 * @param array $contact Target public contact (uid = 0) array
	 * @param array $owner   Source owner-view record
	 * @return bool|null true if successful, false if not, null if no remote action was performed
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function unfollow(array $contact, array $owner): ?bool
	{
		if (empty($contact['network'])) {
			DI::logger()->notice('Contact has got no network, we quit here', ['id' => $contact['id']]);
			return null;
		}

		$protocol = $contact['network'];
		if (($protocol == self::DFRN) && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if ($protocol == self::DIASPORA) {
			return Diaspora::sendUnshare($owner, $contact) > 0;
		} elseif (in_array($protocol, [self::ACTIVITYPUB, self::DFRN])) {
			return ActivityPub\Transmitter::sendContactUndo($contact['url'], $contact['id'], $owner);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid'     => $owner['uid'],
			'result'  => null,
		];
		Hook::callAll('unfollow', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Revoke an incoming follow from the provided contact
	 *
	 * @param array $contact Target public contact (uid == 0) array
	 * @param array $owner   Source owner-view record
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function revokeFollow(array $contact, array $owner): ?bool
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Missing network key in contact array');
		}

		$protocol = $contact['network'];
		if ($protocol == self::DFRN && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if ($protocol == self::ACTIVITYPUB) {
			return ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $owner);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid'     => $owner['uid'],
			'result'  => null,
		];
		Hook::callAll('revoke_follow', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Send a block message to a remote server.
	 *
	 * @param array $contact Public contact record to block
	 * @param int   $uid     User issuing the block
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function block(array $contact, int $uid): ?bool
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Missing network key in contact array');
		}

		$protocol = $contact['network'];
		if ($protocol == self::DFRN && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if ($protocol == self::ACTIVITYPUB) {
			$activity_id = Transmitter::activityIDFromContact($contact['id'], $uid);
			if (empty($activity_id)) {
				return false;
			}
			return ActivityPub\Transmitter::sendActivity('Block', $contact['url'], $uid, $activity_id);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid'     => $uid,
			'result'  => null,
		];
		Hook::callAll('block', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Send an unblock message to a remote server.
	 *
	 * @param array $contact Public contact record to unblock
	 * @param int   $uid     User revoking the block
	 * @return bool|null true if successful, false if not, null if no action was performed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function unblock(array $contact, int $uid): ?bool
	{
		$owner = User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			return false;
		}

		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Missing network key in contact array');
		}

		$protocol = $contact['network'];
		if ($protocol == self::DFRN && !empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		}

		if ($protocol == self::ACTIVITYPUB) {
			return ActivityPub\Transmitter::sendContactUnblock($contact['url'], $contact['id'], $owner);
		}

		// Catch-all hook for connector addons
		$hook_data = [
			'contact' => $contact,
			'uid'     => $uid,
			'result'  => null,
		];
		Hook::callAll('unblock', $hook_data);

		return $hook_data['result'];
	}

	/**
	 * Returns whether the provided protocol supports probing for contacts
	 *
	 * @param $protocol
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function supportsProbe($protocol): bool
	{
		// "Mail" can only be probed for a specific user in a specific condition, so we are ignoring it here.
		if ($protocol == self::MAIL) {
			return false;
		}

		if (in_array($protocol, array_merge(self::NATIVE_SUPPORT, [self::ZOT, self::BLUESKY, self::PHANTOM]))) {
			return true;
		}

		$hook_data = [
			'protocol' => $protocol,
			'result'   => null
		];
		Hook::callAll('support_probe', $hook_data);

		return $hook_data['result'] === true;
	}
}
