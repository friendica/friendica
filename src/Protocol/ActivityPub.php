<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;

/**
 * ActivityPub Protocol class
 *
 * The ActivityPub Protocol is a message exchange protocol defined by the W3C.
 * https://www.w3.org/TR/activitypub/
 * https://www.w3.org/TR/activitystreams-core/
 * https://www.w3.org/TR/activitystreams-vocabulary/
 *
 * https://blog.joinmastodon.org/2018/06/how-to-implement-a-basic-activitypub-server/
 * https://blog.joinmastodon.org/2018/07/how-to-make-friends-and-verify-requests/
 *
 * Digest: https://tools.ietf.org/html/rfc5843
 * https://tools.ietf.org/html/draft-cavage-http-signatures-10#ref-15
 *
 * Mastodon implementation of supported activities:
 * https://github.com/tootsuite/mastodon/blob/master/app/lib/activitypub/activity.rb#L26
 *
 * Funkwhale:
 * http://docs-funkwhale-funkwhale-549-music-federation-documentation.preview.funkwhale.audio/federation/index.html
 *
 * To-do:
 * - Polling the outboxes for missing content?
 *
 * Missing parts from DFRN:
 * - Public Group
 * - Private Group
 * - Relocation
 */
class ActivityPub
{
	const PUBLIC_COLLECTION = 'https://www.w3.org/ns/activitystreams#Public';
	const CONTEXT           = [
		'https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
		[
			'ostatus'  => 'http://ostatus.org#',
			'vcard'    => 'http://www.w3.org/2006/vcard/ns#',
			'dfrn'     => 'http://purl.org/macgirvin/dfrn/1.0/',
			'diaspora' => 'https://diasporafoundation.org/ns/',
			'litepub'  => 'http://litepub.social/ns#',
			'toot'     => 'http://joinmastodon.org/ns#',
			'featured' => [
				"@id"   => "toot:featured",
				"@type" => "@id",
			],
			'schema'                    => 'http://schema.org#',
			'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
			'sensitive'                 => 'as:sensitive', 'Hashtag' => 'as:Hashtag',
			'quoteUrl'                  => 'as:quoteUrl',
			'conversation'              => 'ostatus:conversation',
			'directMessage'             => 'litepub:directMessage',
			'discoverable'              => 'toot:discoverable',
			'PropertyValue'             => 'schema:PropertyValue',
			'value'                     => 'schema:value',
		]
	];
	const ACCOUNT_TYPES = ['Person', 'Organization', 'Service', 'Group', 'Application', 'Tombstone'];

	const ARTICLE_DEFAULT     = 0;
	const ARTICLE_USE_SUMMARY = 1;
	const ARTICLE_EMBED_TITLE = 2;

	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return bool is it AP?
	 */
	public static function isRequest(): bool
	{
		header('Vary: Accept', false);
		if (stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/activity+json') || stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/ld+json')) {
			DI::logger()->debug('Is AP request', ['accept' => $_SERVER['HTTP_ACCEPT'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
			return true;
		}

		if (stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
			DI::logger()->debug('Is JSON request', ['accept' => $_SERVER['HTTP_ACCEPT'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
			return true;
		}

		return false;
	}

	private static function getAccountType(array $apcontact): int
	{
		$accounttype = -1;

		switch ($apcontact['type']) {
			case 'Person':
				$accounttype = User::ACCOUNT_TYPE_PERSON;
				break;
			case 'Organization':
				$accounttype = User::ACCOUNT_TYPE_ORGANISATION;
				break;
			case 'Service':
				$accounttype = User::ACCOUNT_TYPE_NEWS;
				break;
			case 'Group':
				$accounttype = User::ACCOUNT_TYPE_COMMUNITY;
				break;
			case 'Application':
				$accounttype = User::ACCOUNT_TYPE_RELAY;
				break;
			case 'Tombstone':
				$accounttype = User::ACCOUNT_TYPE_DELETED;
				break;
		}

		return $accounttype;
	}

	/**
	 * Fetches a profile from the given url into an array that is compatible to Probe::uri
	 *
	 * @param string  $url    profile url
	 * @param boolean $update Update the profile
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function probeProfile(string $url, bool $update = true): array
	{
		$apcontact = APContact::getByURL($url, $update);
		if (empty($apcontact)) {
			return [];
		}

		$profile                 = ['network' => Protocol::ACTIVITYPUB];
		$profile['nick']         = $apcontact['nick'];
		$profile['name']         = $apcontact['name'];
		$profile['guid']         = $apcontact['uuid'];
		$profile['url']          = $apcontact['url'];
		$profile['addr']         = $apcontact['addr'];
		$profile['alias']        = $apcontact['alias'];
		$profile['following']    = $apcontact['following'];
		$profile['followers']    = $apcontact['followers'];
		$profile['inbox']        = $apcontact['inbox'];
		$profile['outbox']       = $apcontact['outbox'];
		$profile['sharedinbox']  = $apcontact['sharedinbox'];
		$profile['photo']        = $apcontact['photo'];
		$profile['header']       = $apcontact['header'];
		$profile['account-type'] = self::getAccountType($apcontact);
		$profile['community']    = ($profile['account-type'] == User::ACCOUNT_TYPE_COMMUNITY);
		// $profile['keywords']
		// $profile['location']
		$profile['about']            = $apcontact['about'];
		$profile['xmpp']             = $apcontact['xmpp'];
		$profile['matrix']           = $apcontact['matrix'];
		$profile['batch']            = $apcontact['sharedinbox'];
		$profile['notify']           = $apcontact['inbox'];
		$profile['poll']             = $apcontact['outbox'];
		$profile['pubkey']           = $apcontact['pubkey'];
		$profile['subscribe']        = $apcontact['subscribe'];
		$profile['manually-approve'] = $apcontact['manually-approve'];
		$profile['baseurl']          = $apcontact['baseurl'];
		$profile['gsid']             = $apcontact['gsid'];

		if (!is_null($apcontact['discoverable'])) {
			$profile['hide'] = !$apcontact['discoverable'];
		}

		// Remove all "null" fields
		foreach ($profile as $field => $content) {
			if (is_null($content)) {
				unset($profile[$field]);
			}
		}

		return $profile;
	}

	/**
	 * Fetches activities from the outbox of a given profile and processes it
	 *
	 * @param string  $url
	 * @param integer $uid User ID
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchOutbox(string $url, int $uid)
	{
		$data = HTTPSignature::fetch($url, $uid);
		if (empty($data)) {
			return;
		}

		if (!empty($data['orderedItems'])) {
			$items = $data['orderedItems'];
		} elseif (!empty($data['first']['orderedItems'])) {
			$items = $data['first']['orderedItems'];
		} elseif (!empty($data['first'])) {
			self::fetchOutbox($data['first'], $uid);
			return;
		} else {
			$items = [];
		}

		foreach ($items as $activity) {
			$ldactivity = JsonLD::compact($activity);
			ActivityPub\Receiver::processActivity($ldactivity, '', $uid, true);
		}
	}

	/**
	 * Fetch items from AP endpoints
	 *
	 * @param string $url              Address of the endpoint
	 * @param integer $uid             Optional user id
	 * @param integer $start_timestamp Internally used parameter to stop fetching after some time
	 * @return array Endpoint items
	 */
	public static function fetchItems(string $url, int $uid = 0, int $start_timestamp = 0): array
	{
		$start_timestamp = $start_timestamp ?: time();

		if ((time() - $start_timestamp) > 60) {
			DI::logger()->info('Fetch time limit reached', ['url' => $url, 'uid' => $uid]);
			return [];
		}

		$data = HTTPSignature::fetch($url, $uid);
		if (empty($data)) {
			return [];
		}

		if (!empty($data['orderedItems'])) {
			$items = $data['orderedItems'];
		} elseif (!empty($data['first']['orderedItems'])) {
			$items = $data['first']['orderedItems'];
		} elseif (!empty($data['items'])) {
			$items = $data['items'];
		} elseif (!empty($data['first']['items'])) {
			$items = $data['first']['items'];
		} elseif (!empty($data['first']) && is_string($data['first']) && ($data['first'] != $url)) {
			return self::fetchItems($data['first'], $uid, $start_timestamp);
		} else {
			return [];
		}

		if (!empty($data['first']['next']) && is_string($data['first']['next'])) {
			$items = array_merge($items, self::fetchItems($data['first']['next'], $uid, $start_timestamp));
		} elseif (!empty($data['next']) && is_string($data['next'])) {
			$items = array_merge($items, self::fetchItems($data['next'], $uid, $start_timestamp));
		}

		return $items;
	}

	/**
	 * Checks if the given contact url does support ActivityPub
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSupportedByContactUrl(string $url, $update = null): bool
	{
		return !empty(APContact::getByURL($url, $update));
	}

	public static function isAcceptedRequester(int $uid = 0): bool
	{
		$called_by = System::callstack(1);

		$signer = HTTPSignature::getSigner('', $_SERVER);
		if (!$signer) {
			DI::logger()->debug('No signer or invalid signature', ['uid' => $uid, 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'called_by' => $called_by]);
			return false;
		}

		$apcontact = APContact::getByURL($signer);
		if (empty($apcontact)) {
			DI::logger()->info('APContact not found', ['uid' => $uid, 'handle' => $signer, 'called_by' => $called_by]);
			return false;
		}

		if (empty($apcontact['gsid']) || empty($apcontact['baseurl'])) {
			DI::logger()->debug('No server found', ['uid' => $uid, 'signer' => $signer, 'called_by' => $called_by]);
			return false;
		}

		$contact = Contact::getByURL($signer, false, ['id', 'baseurl', 'gsid']);
		if (!empty($contact) && Contact\User::isBlocked($contact['id'], $uid)) {
			DI::logger()->info('Requesting contact is blocked', ['uid' => $uid, 'id' => $contact['id'], 'signer' => $signer, 'baseurl' => $contact['baseurl'], 'called_by' => $called_by]);
			return false;
		}

		$limited = DI::config()->get('system', 'limited_servers');
		if (!empty($limited)) {
			$servers = explode(',', str_replace(' ', '', $limited));
			$host    = parse_url($apcontact['baseurl'], PHP_URL_HOST);
			if (!empty($host) && in_array($host, $servers)) {
				return false;
			}
		}

		if (DI::userGServer()->isIgnoredByUser($uid, $apcontact['gsid'])) {
			return false;
		}

		DI::logger()->debug('Server is an accepted requester', ['uid' => $uid, 'id' => $apcontact['gsid'], 'url' => $apcontact['baseurl'], 'signer' => $signer, 'called_by' => $called_by]);

		return true;
	}
}
