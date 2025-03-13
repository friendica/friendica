<?php

/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

namespace Friendica\Protocol\ATProtocol;

use Friendica\Content\Text\HTML;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Protocol\ATProtocol;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * Class to handle AT Protocol actors
 */
class Actor
{
	/** @var LoggerInterface */
	private $logger;

	/** @var ATProtocol */
	private $atprotocol;

	public function __construct(LoggerInterface $logger, ATProtocol $atprotocol)
	{
		$this->logger     = $logger;
		$this->atprotocol = $atprotocol;
	}

	/**
	 * Syncronize the contacts (followers, sharers) for the given user
	 *
	 * @param integer $uid User ID
	 * @return void
	 */
	public function syncContacts(int $uid): void
	{
		$this->logger->info('Sync contacts for user - start', ['uid' => $uid]);
		$contacts = Contact::selectToArray(['id', 'url', 'rel'], ['uid' => $uid, 'network' => Protocol::BLUESKY, 'rel' => [Contact::FRIEND, Contact::SHARING, Contact::FOLLOWER]]);

		$follows  = [];
		$cursor   = '';
		$profiles = [];

		do {
			$parameters = [
				'actor'  => $this->atprotocol->getUserDid($uid),
				'limit'  => 100,
				'cursor' => $cursor
			];

			$data = $this->atprotocol->XRPCGet('app.bsky.graph.getFollows', $parameters);

			foreach ($data->follows ?? [] as $follow) {
				$profiles[$follow->did] = $follow;
				$follows[$follow->did]  = Contact::SHARING;
			}
			$cursor = $data->cursor ?? '';
		} while (!empty($data->follows) && !empty($data->cursor));

		$cursor = '';

		do {
			$parameters = [
				'actor'  => $this->atprotocol->getUserDid($uid),
				'limit'  => 100,
				'cursor' => $cursor
			];

			$data = $this->atprotocol->XRPCGet('app.bsky.graph.getFollowers', $parameters);

			foreach ($data->followers ?? [] as $follow) {
				$profiles[$follow->did] = $follow;
				$follows[$follow->did]  = ($follows[$follow->did] ?? 0) | Contact::FOLLOWER;
			}
			$cursor = $data->cursor ?? '';
		} while (!empty($data->followers) && !empty($data->cursor));

		foreach ($contacts as $contact) {
			if (empty($follows[$contact['url']])) {
				Contact::update(['rel' => Contact::NOTHING], ['id' => $contact['id']]);
			}
		}

		foreach ($follows as $did => $rel) {
			$contact = $this->getContactByDID($did, $uid, $uid);
			if (($contact['rel'] != $rel) && ($contact['uid'] != 0)) {
				Contact::update(['rel' => $rel], ['id' => $contact['id']]);
			}
		}
		$this->logger->info('Sync contacts for user - done', ['uid' => $uid]);
	}

	/**
	 * Update a contact for a given DID and user id
	 *
	 * @param string  $did         DID (did:plc:...)
	 * @param integer $contact_uid User id of the contact to be updated
	 * @return void
	 */
	public function updateContactByDID(string $did, int $contact_uid): void
	{
		$profile = $this->atprotocol->XRPCGet('app.bsky.actor.getProfile', ['actor' => $did], $contact_uid);
		if (empty($profile) || empty($profile->did)) {
			return;
		}

		$nick = $profile->handle      ?? $profile->did;
		$name = $profile->displayName ?? $nick;

		$fields = [
			'alias'   => ATProtocol::WEB . '/profile/' . $nick,
			'name'    => $name ?: $nick,
			'nick'    => $nick,
			'addr'    => $nick,
			'updated' => DateTimeFormat::utcNow(DateTimeFormat::MYSQL),
		];

		if (!empty($profile->description)) {
			$fields['about'] = HTML::toBBCode($profile->description);
		}

		if (!empty($profile->banner)) {
			$fields['header'] = $profile->banner;
		}

		$directory = $this->atprotocol->get(ATProtocol::DIRECTORY . '/' . $profile->did);
		if (!empty($directory)) {
			foreach ($directory->service as $service) {
				if (($service->id == '#atproto_pds') && ($service->type == 'AtprotoPersonalDataServer') && !empty($service->serviceEndpoint)) {
					$fields['baseurl'] = $service->serviceEndpoint;
				}
			}

			if (!empty($fields['baseurl'])) {
				GServer::check($fields['baseurl'], Protocol::BLUESKY);
				$fields['gsid'] = GServer::getRealID($fields['baseurl'], true);
			}

			foreach ($directory->verificationMethod as $method) {
				if (!empty($method->publicKeyMultibase)) {
					$fields['pubkey'] = $method->publicKeyMultibase;
				}
			}
		}

		Contact::update($fields, ['nurl' => $profile->did, 'network' => Protocol::BLUESKY]);

		if (!empty($profile->avatar)) {
			$contact = Contact::selectFirst(['id', 'avatar'], ['network' => Protocol::BLUESKY, 'nurl' => $did, 'uid' => 0]);
			if (!empty($contact['id']) && ($contact['avatar'] != $profile->avatar)) {
				Contact::updateAvatar($contact['id'], $profile->avatar);
			}
		}

		$this->logger->notice('Update global profile', ['did' => $profile->did, 'fields' => $fields]);

		if (!empty($profile->viewer) && ($contact_uid != 0)) {
			if (!empty($profile->viewer->following) && !empty($profile->viewer->followedBy)) {
				$user_fields = ['rel' => Contact::FRIEND];
			} elseif (!empty($profile->viewer->following) && empty($profile->viewer->followedBy)) {
				$user_fields = ['rel' => Contact::SHARING];
			} elseif (empty($profile->viewer->following) && !empty($profile->viewer->followedBy)) {
				$user_fields = ['rel' => Contact::FOLLOWER];
			} else {
				$user_fields = ['rel' => Contact::NOTHING];
			}
			Contact::update($user_fields, ['nurl' => $profile->did, 'network' => Protocol::BLUESKY, 'uid' => $contact_uid]);
			$this->logger->notice('Update user profile', ['uid' => $contact_uid, 'did' => $profile->did, 'fields' => $user_fields]);
		}
	}

	/**
	 * Fetch and possibly create a contact array for a given DID
	 *
	 * @param string  $did         The contact DID
	 * @param integer $uid         "0" when either the public contact or the user contact is desired
	 * @param integer $contact_uid If not found, the contact will be created for this user id
	 * @param boolean $auto_update Default "false". If activated, the contact will be updated every 24 hours
	 * @return array Contact array
	 */
	public function getContactByDID(string $did, int $uid, int $contact_uid, bool $auto_update = false): array
	{
		$contact = Contact::selectFirst([], ['network' => Protocol::BLUESKY, 'nurl' => $did, 'uid' => [$contact_uid, $uid]], ['order' => ['uid' => true]]);

		if (!empty($contact) && (!$auto_update || ($contact['updated'] > DateTimeFormat::utc('now -24 hours')))) {
			return $contact;
		}

		$fields = [
			'uid'      => $contact_uid,
			'network'  => Protocol::BLUESKY,
			'priority' => 1,
			'writable' => true,
			'blocked'  => false,
			'readonly' => false,
			'pending'  => false,
			'url'      => $did,
			'nurl'     => $did,
			'alias'    => ATProtocol::WEB . '/profile/' . $did,
			'name'     => $did,
			'nick'     => $did,
			'addr'     => $did,
			'rel'      => Contact::NOTHING,
		];

		$cid = Contact::insert($fields);

		$this->updateContactByDID($did, $contact_uid);

		return Contact::getById($cid);
	}
}
