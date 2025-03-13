<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Content\Text\HTML;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ActivityPub\Transmitter;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;
use Friendica\Util\Network;
use GuzzleHttp\Psr7\Uri;

class APContact
{
	/**
	 * Fetch webfinger data
	 *
	 * @param string $addr Address
	 * @return array webfinger data
	 */
	private static function fetchWebfingerData(string $addr): array
	{
		$addr_parts = explode('@', $addr);
		if (count($addr_parts) != 2) {
			return [];
		}

		if (Contact::isLocal($addr) && ($local_uid = User::getIdForURL($addr)) && ($local_owner = User::getOwnerDataById($local_uid))) {
			$data = [
				'addr'      => $local_owner['addr'],
				'baseurl'   => $local_owner['baseurl'],
				'url'       => $local_owner['url'],
				'subscribe' => $local_owner['baseurl'] . '/contact/follow?url={uri}'];

			if (!empty($local_owner['alias']) && ($local_owner['url'] != $local_owner['alias'])) {
				$data['alias'] = $local_owner['alias'];
			}

			return $data;
		}

		$webfinger = Probe::getWebfingerArray($addr);
		if (empty($webfinger['webfinger']['links'])) {
			return [];
		}

		$data['baseurl'] = $webfinger['baseurl'];

		foreach ($webfinger['webfinger']['links'] as $link) {
			if (empty($link['rel'])) {
				continue;
			}

			if (!empty($link['template']) && ($link['rel'] == ActivityNamespace::OSTATUSSUB)) {
				$data['subscribe'] = $link['template'];
			} elseif (!empty($link['href']) && !empty($link['type']) && ($link['rel'] == 'self') && ($link['type'] == 'application/activity+json')) {
				$data['url'] = $link['href'];
			} elseif (!empty($link['href']) && !empty($link['type']) && ($link['rel'] == ActivityNamespace::WEBFINGERPROFILE) && ($link['type'] == 'text/html')) {
				$data['alias'] = $link['href'];
			}
		}

		if (!empty($data['url']) && !empty($data['alias']) && ($data['url'] == $data['alias'])) {
			unset($data['alias']);
		}

		return $data;
	}

	/**
	 * Fetches a profile from a given url
	 *
	 * @param string   $url    profile url
	 * @param ?boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @return array profile array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getByURL(string $url, bool $update = null): array
	{
		if (empty($url) || Network::isUrlBlocked($url)) {
			DI::logger()->info('Domain is blocked', ['url' => $url]);
			return [];
		}

		if (!Network::isValidHttpUrl($url) && !filter_var($url, FILTER_VALIDATE_EMAIL)) {
			DI::logger()->info('Invalid URL', ['url' => $url]);
			return [];
		}

		$fetched_contact = [];

		if (empty($update)) {
			if (is_null($update)) {
				$ref_update = DateTimeFormat::utc('now - 1 month');
			} else {
				$ref_update = DBA::NULL_DATETIME;
			}

			$apcontact = DBA::selectFirst('apcontact', [], ['url' => $url]);
			if (!DBA::isResult($apcontact)) {
				$apcontact = DBA::selectFirst('apcontact', [], ['alias' => $url]);
			}

			if (!DBA::isResult($apcontact)) {
				$apcontact = DBA::selectFirst('apcontact', [], ['addr' => $url]);
			}

			if (DBA::isResult($apcontact) && ($apcontact['updated'] > $ref_update) && !empty($apcontact['pubkey']) && !empty($apcontact['uri-id'])) {
				return $apcontact;
			}

			if (!is_null($update)) {
				return DBA::isResult($apcontact) ? $apcontact : [];
			}

			if (DBA::isResult($apcontact)) {
				$fetched_contact = $apcontact;
			}
		}

		$apcontact = [];

		// Mastodon profile short-form URL https://domain.tld/@user doesn't return AP data when queried
		// with HTTPSignature::fetchRaw, but returns the correct data when provided to WebFinger
		// @see https://github.com/friendica/friendica/issues/13359
		$webfinger = empty(parse_url($url, PHP_URL_SCHEME)) || strpos($url, '@') !== false;
		if ($webfinger) {
			$apcontact = self::fetchWebfingerData($url);
			if (empty($apcontact['url'])) {
				return $fetched_contact;
			}
			$url = $apcontact['url'];
		} elseif (empty(parse_url($url, PHP_URL_PATH))) {
			$apcontact['baseurl'] = $url;
		}

		// Detect multiple fast repeating request to the same address
		// See https://github.com/friendica/friendica/issues/9303
		$cachekey = 'apcontact:' . ItemURI::getIdByURI($url);
		$result   = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			DI::logger()->info('Multiple requests for the address', ['url' => $url, 'update' => $update, 'result' => $result]);
			if (!empty($fetched_contact)) {
				return $fetched_contact;
			}
		} else {
			DI::cache()->set($cachekey, System::callstack(20), Duration::FIVE_MINUTES);
		}

		$local_owner = [];

		if (DI::baseUrl()->isLocalUrl($url) && ($local_uid = User::getIdForURL($url))) {
			try {
				$data        = Transmitter::getProfile($local_uid);
				$local_owner = User::getOwnerDataById($local_uid);
			} catch(HTTPException\NotFoundException $e) {
				$data = null;
			}
		}

		if (empty($data)) {
			$local_owner = [];

			try {
				$curlResult = HTTPSignature::fetchRaw($url);
				$failed     = empty($curlResult->getBodyString()) ||
					(!$curlResult->isSuccess() && ($curlResult->getReturnCode() != 410));

				if (!$failed) {
					$data   = json_decode($curlResult->getBodyString(), true);
					$failed = empty($data) || !is_array($data);
				}

				if (!$failed && ($curlResult->getReturnCode() == 410)) {
					$data = ['@context' => ActivityPub::CONTEXT, 'id' => $url, 'type' => 'Tombstone'];
				} elseif (!$failed && !HTTPSignature::isValidContentType($curlResult->getContentType(), $url)) {
					$failed = true;
				}
			} catch (\Exception $exception) {
				DI::logger()->notice('Error fetching url', ['url' => $url, 'exception' => $exception]);
				$failed = true;
			}

			if ($failed) {
				self::markForArchival($fetched_contact ?: []);
				return $fetched_contact;
			}
		}

		$compacted = JsonLD::compact($data);
		if (empty($compacted['@id'])) {
			return $fetched_contact;
		}

		return self::compactProfile($apcontact, $compacted, $url, $fetched_contact, $webfinger, $local_owner);
	}

	/**
	 * @param array|bool $fetched_contact
	 * @param array|bool $local_owner
	 */
	private static function compactProfile(array $apcontact, array $compacted, string $url, $fetched_contact, bool $webfinger, $local_owner): array
	{
		$apcontact['url']       = $compacted['@id'];
		$apcontact['uuid']      = JsonLD::fetchElement($compacted, 'diaspora:guid', '@value');
		$apcontact['type']      = str_replace('as:', '', JsonLD::fetchElement($compacted, '@type'));
		$apcontact['following'] = JsonLD::fetchElement($compacted, 'as:following', '@id');
		$apcontact['followers'] = JsonLD::fetchElement($compacted, 'as:followers', '@id');
		$apcontact['inbox']     = (JsonLD::fetchElement($compacted, 'ldp:inbox', '@id') ?? '');
		$apcontact['outbox']    = JsonLD::fetchElement($compacted, 'as:outbox', '@id');

		$apcontact['sharedinbox'] = '';
		if (!empty($compacted['as:endpoints'])) {
			$apcontact['sharedinbox'] = (JsonLD::fetchElement($compacted['as:endpoints'], 'as:sharedInbox', '@id') ?? '');
		}

		$apcontact['featured']      = JsonLD::fetchElement($compacted, 'toot:featured', '@id');
		$apcontact['featured-tags'] = JsonLD::fetchElement($compacted, 'toot:featuredTags', '@id');

		$apcontact['nick'] = JsonLD::fetchElement($compacted, 'as:preferredUsername', '@value') ?? '';
		$apcontact['name'] = JsonLD::fetchElement($compacted, 'as:name', '@value');

		if (empty($apcontact['name'])) {
			$apcontact['name'] = $apcontact['nick'];
		}

		$apcontact['about'] = HTML::toBBCode(JsonLD::fetchElement($compacted, 'as:summary', '@value') ?? '');

		$ims = JsonLD::fetchElementArray($compacted, 'vcard:hasInstantMessage');

		if (!empty($ims)) {
			foreach ($ims as $link) {
				if (substr($link, 0, 5) == 'xmpp:') {
					$apcontact['xmpp'] = substr($link, 5);
				}
				if (substr($link, 0, 7) == 'matrix:') {
					$apcontact['matrix'] = substr($link, 7);
				}
			}
		}

		$apcontact['name'] = self::removeCustomEmojis($apcontact['name'], JsonLD::fetchElementArray($compacted, 'as:tag') ?? []);

		$apcontact['photo'] = JsonLD::fetchElement($compacted, 'as:icon', '@id');
		if (is_array($apcontact['photo']) || !empty($compacted['as:icon']['as:url']['@id'])) {
			$apcontact['photo'] = JsonLD::fetchElement($compacted['as:icon'], 'as:url', '@id');
		} elseif (empty($apcontact['photo'])) {
			$photo = JsonLD::fetchElementArray($compacted, 'as:icon', 'as:url');
			if (!empty($photo[0]['@id'])) {
				$apcontact['photo'] = $photo[0]['@id'];
			}
		}

		$apcontact['header'] = JsonLD::fetchElement($compacted, 'as:image', '@id');
		if (is_array($apcontact['header']) || !empty($compacted['as:image']['as:url']['@id'])) {
			$apcontact['header'] = JsonLD::fetchElement($compacted['as:image'], 'as:url', '@id');
		}

		if (empty($apcontact['alias'])) {
			$apcontact['alias'] = JsonLD::fetchElement($compacted, 'as:url', '@id');
			if (is_array($apcontact['alias'])) {
				$apcontact['alias'] = JsonLD::fetchElement($compacted['as:url'], 'as:href', '@id');
			}
		}

		// Quit if none of the basic values are set
		if (empty($apcontact['url']) || empty($apcontact['type']) || (($apcontact['type'] != 'Tombstone') && empty($apcontact['inbox']))) {
			return $fetched_contact;
		} elseif ($apcontact['type'] == 'Tombstone') {
			// The "inbox" field must have a content
			$apcontact['inbox'] = '';
			$apcontact['addr']  = '';
		}

		// Quit if this doesn't seem to be an account at all
		if (!in_array($apcontact['type'], ActivityPub::ACCOUNT_TYPES)) {
			return $fetched_contact;
		}

		if (!empty($compacted['https://webfinger.net/#'])) {
			$apcontact['addr'] = JsonLD::fetchElement($compacted, 'https://webfinger.net/#');
		}

		if (empty($apcontact['addr']) && ($apcontact['type'] != 'Tombstone')) {
			try {
				$apcontact['addr'] = $apcontact['nick'] . '@' . (new Uri($apcontact['url']))->getAuthority();
			} catch (\Throwable $e) {
				DI::logger()->warning('Unable to coerce APContact URL into a UriInterface object', ['url' => $apcontact['url'], 'error' => $e->getMessage()]);
				$apcontact['addr'] = '';
			}
		}

		$apcontact['pubkey'] = null;
		if (!empty($compacted['w3id:publicKey'])) {
			$apcontact['pubkey'] = trim(JsonLD::fetchElement($compacted['w3id:publicKey'], 'w3id:publicKeyPem', '@value') ?? '');
			if (strpos($apcontact['pubkey'], 'RSA ') !== false) {
				$apcontact['pubkey'] = Crypto::rsaToPem($apcontact['pubkey']);
			}
		}

		$apcontact['manually-approve']   = (int)JsonLD::fetchElement($compacted, 'as:manuallyApprovesFollowers');
		$apcontact['posting-restricted'] = (int)JsonLD::fetchElement($compacted, 'lemmy:postingRestrictedToMods');
		$apcontact['suspended']          = (int)JsonLD::fetchElement($compacted, 'toot:suspended');

		if (!empty($compacted['as:generator'])) {
			$apcontact['baseurl']   = JsonLD::fetchElement($compacted['as:generator'], 'as:url', '@id');
			$apcontact['generator'] = JsonLD::fetchElement($compacted['as:generator'], 'as:name', '@value');
		}

		if (!empty($apcontact['following'])) {
			if (!empty($local_owner)) {
				$following = ActivityPub\Transmitter::getContacts($local_owner, [Contact::SHARING, Contact::FRIEND], 'following');
			} else {
				$following = HTTPSignature::fetch($apcontact['following']);
			}
			if (!empty($following['totalItems'])) {
				// Mastodon seriously allows for this condition?
				// Jul 14 2021 - See https://mastodon.social/@BLUW for a negative following count
				if ($following['totalItems'] < 0) {
					$following['totalItems'] = 0;
				}
				$apcontact['following_count'] = $following['totalItems'];
			}
		}

		if (!empty($apcontact['followers'])) {
			if (!empty($local_owner)) {
				$followers = ActivityPub\Transmitter::getContacts($local_owner, [Contact::FOLLOWER, Contact::FRIEND], 'followers');
			} else {
				$followers = HTTPSignature::fetch($apcontact['followers']);
			}
			if (!empty($followers['totalItems'])) {
				// Mastodon seriously allows for this condition?
				// Jul 14 2021 - See https://mastodon.online/@goes11 for a negative followers count
				if ($followers['totalItems'] < 0) {
					$followers['totalItems'] = 0;
				}
				$apcontact['followers_count'] = $followers['totalItems'];
			}
		}

		if (!empty($apcontact['outbox'])) {
			if (!empty($local_owner)) {
				$statuses_count = self::getStatusesCount($local_owner);
			} else {
				$outbox         = HTTPSignature::fetch($apcontact['outbox']);
				$statuses_count = $outbox['totalItems'] ?? 0;
			}
			if (!empty($statuses_count)) {
				// Mastodon seriously allows for this condition?
				// Jul 20 2021 - See https://chaos.social/@m11 for a negative posts count
				if ($statuses_count < 0) {
					$statuses_count = 0;
				}
				$apcontact['statuses_count'] = $statuses_count;
			}
		}

		$apcontact['discoverable'] = JsonLD::fetchElement($compacted, 'toot:discoverable', '@value');
		if (is_null($apcontact['discoverable']) && ($apcontact['type'] == 'Application')) {
			$apcontact['discoverable'] = false;
		}

		if (!empty($apcontact['photo'])) {
			$apcontact['photo'] = Network::addBasePath($apcontact['photo'], $apcontact['url']);

			if (!Network::isValidHttpUrl($apcontact['photo'])) {
				DI::logger()->warning('Invalid URL for photo', ['url' => $apcontact['url'], 'photo' => $apcontact['photo']]);
				$apcontact['photo'] = '';
			}
		}

		// When the photo is too large, try to shorten it by removing parts
		if (strlen($apcontact['photo'] ?? '') > 383) {
			$parts = parse_url($apcontact['photo']);
			unset($parts['fragment']);
			$apcontact['photo'] = (string)Uri::fromParts((array)$parts);

			if (strlen($apcontact['photo']) > 383) {
				unset($parts['query']);
				$apcontact['photo'] = (string)Uri::fromParts((array)$parts);
			}

			if (strlen($apcontact['photo']) > 383) {
				$apcontact['photo'] = substr($apcontact['photo'], 0, 383);
			}
		}

		if (!$webfinger && !empty($apcontact['addr'])) {
			$data = self::fetchWebfingerData($apcontact['addr']);
			if (!empty($data)) {
				$apcontact['baseurl'] = $data['baseurl'];

				if (empty($apcontact['alias']) && !empty($data['alias'])) {
					$apcontact['alias'] = $data['alias'];
				}
				if (!empty($data['subscribe'])) {
					$apcontact['subscribe'] = $data['subscribe'];
				}
			} else {
				$apcontact['addr'] = null;
			}
		}

		if (empty($apcontact['baseurl'])) {
			$apcontact['baseurl'] = null;
		}

		if (empty($apcontact['subscribe'])) {
			$apcontact['subscribe'] = null;
		}

		if (!empty($apcontact['baseurl']) && empty($fetched_contact['gsid'])) {
			$apcontact['gsid'] = GServer::getRealID($apcontact['baseurl']);
		} elseif (!empty($fetched_contact['gsid'])) {
			$apcontact['gsid'] = $fetched_contact['gsid'];
		} else {
			$apcontact['gsid'] = null;
		}

		self::unarchiveInbox($apcontact['inbox'], false, $apcontact['gsid']);

		if (!empty($apcontact['sharedinbox'])) {
			self::unarchiveInbox($apcontact['sharedinbox'], true, $apcontact['gsid']);
		}

		if ($apcontact['url'] == $apcontact['alias']) {
			$apcontact['alias'] = null;
		}

		if (empty($apcontact['uuid'])) {
			$apcontact['uri-id'] = ItemURI::getIdByURI($apcontact['url']);
		} else {
			$apcontact['uri-id'] = ItemURI::insert(['uri' => $apcontact['url'], 'guid' => $apcontact['uuid']]);
		}

		foreach (APContact\Endpoint::ENDPOINT_NAMES as $type => $name) {
			$value = JsonLD::fetchElement($compacted, $name, '@id');
			if (empty($value)) {
				continue;
			}
			APContact\Endpoint::update($apcontact['uri-id'], $type, $value);
		}

		if (!empty($compacted['as:endpoints'])) {
			foreach ($compacted['as:endpoints'] as $name => $endpoint) {
				if (empty($endpoint['@id']) || !is_string($endpoint['@id'])) {
					continue;
				}

				if (in_array($name, APContact\Endpoint::ENDPOINT_NAMES)) {
					$key = array_search($name, APContact\Endpoint::ENDPOINT_NAMES);
					APContact\Endpoint::update($apcontact['uri-id'], $key, $endpoint['@id']);
					DI::logger()->debug('Store endpoint', ['key' => $key, 'name' => $name, 'endpoint' => $endpoint['@id']]);
				} elseif (!in_array($name, ['as:sharedInbox', 'as:uploadMedia', 'as:oauthTokenEndpoint', 'as:oauthAuthorizationEndpoint', 'litepub:oauthRegistrationEndpoint'])) {
					DI::logger()->debug('Unknown endpoint', ['name' => $name, 'endpoint' => $endpoint['@id']]);
				}
			}
		}

		$apcontact['updated'] = DateTimeFormat::utcNow();

		// We delete the old entry when the URL is changed
		if ($url != $apcontact['url']) {
			DI::logger()->info('Delete changed profile url', ['old' => $url, 'new' => $apcontact['url']]);
			DBA::delete('apcontact', ['url' => $url]);
		}

		// Limit the length on incoming fields
		$apcontact = DI::dbaDefinition()->truncateFieldsForTable('apcontact', $apcontact);

		if (DBA::exists('apcontact', ['url' => $apcontact['url']])) {
			DBA::update('apcontact', $apcontact, ['url' => $apcontact['url']]);
		} else {
			DBA::replace('apcontact', $apcontact);
		}

		DI::logger()->info('Updated profile', ['url' => $url]);

		return DBA::selectFirst('apcontact', [], ['url' => $apcontact['url']]) ?: [];
	}

	public static function removeCustomEmojis(string $name, array $tags): string
	{
		$original = $name;
		foreach ($tags as $tag) {
			if (empty($tag['@type']) || ($tag['@type'] != 'toot:Emoji')) {
				continue;
			}
			$name = trim(str_replace($tag['as:name'], '', $name));
		}

		return $name ?: $original;
	}

	/**
	 * Fetch the number of statuses for the given owner
	 *
	 * @param array $owner
	 *
	 * @return integer
	 */
	private static function getStatusesCount(array $owner): int
	{
		$condition = [
			'private'        => [Item::PUBLIC, Item::UNLISTED],
			'author-id'      => Contact::getIdForURL($owner['url'], 0, false),
			'gravity'        => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT],
			'network'        => Protocol::DFRN,
			'parent-network' => Protocol::FEDERATED,
			'deleted'        => false,
			'visible'        => true,
		];

		$count = Post::countPosts($condition);

		return $count;
	}

	/**
	 * Mark the given AP Contact as "to archive"
	 *
	 * @param array $apcontact
	 * @return void
	 */
	public static function markForArchival(array $apcontact)
	{
		if (!empty($apcontact['inbox'])) {
			DI::logger()->info('Set inbox status to failure', ['inbox' => $apcontact['inbox']]);
			HTTPSignature::setInboxStatus($apcontact['inbox'], false, false, $apcontact['gsid']);
		}

		if (!empty($apcontact['sharedinbox'])) {
			// Check if there are any available inboxes
			$available = DBA::exists('apcontact', ["`sharedinbox` = ? AnD `inbox` IN (SELECT `url` FROM `inbox-status` WHERE `success` > `failure`)",
				$apcontact['sharedinbox']]);
			if (!$available) {
				// If all known personal inboxes are failing then set their shared inbox to failure as well
				DI::logger()->info('Set shared inbox status to failure', ['sharedinbox' => $apcontact['sharedinbox']]);
				HTTPSignature::setInboxStatus($apcontact['sharedinbox'], false, true, $apcontact['gsid']);
			}
		}
	}

	/**
	 * Unmark the given AP Contact as "to archive"
	 *
	 * @param array $apcontact
	 * @return void
	 */
	public static function unmarkForArchival(array $apcontact)
	{
		if (!empty($apcontact['inbox'])) {
			DI::logger()->info('Set inbox status to success', ['inbox' => $apcontact['inbox']]);
			HTTPSignature::setInboxStatus($apcontact['inbox'], true, false, $apcontact['gsid']);
		}
		if (!empty($apcontact['sharedinbox'])) {
			DI::logger()->info('Set shared inbox status to success', ['sharedinbox' => $apcontact['sharedinbox']]);
			HTTPSignature::setInboxStatus($apcontact['sharedinbox'], true, true, $apcontact['gsid']);
		}
	}

	/**
	 * Unarchive inboxes
	 *
	 * @param string  $url    inbox url
	 * @param boolean $shared Shared Inbox
	 * @param int     $gsid   Global server id
	 * @return void
	 */
	private static function unarchiveInbox(string $url, bool $shared, int $gsid = null)
	{
		if (empty($url)) {
			return;
		}

		HTTPSignature::setInboxStatus($url, true, $shared, $gsid);
	}

	/**
	 * Check if the apcontact is a relay account
	 *
	 * @param array $apcontact
	 *
	 * @return bool
	 */
	public static function isRelay(array $apcontact): bool
	{
		if (!in_array($apcontact['type'] ?? '', ['Application', 'Group', 'Service'])) {
			return false;
		}

		$path = parse_url($apcontact['url'], PHP_URL_PATH);
		if (($apcontact['type'] == 'Group') && !empty($apcontact['followers']) && ($apcontact['nick'] == 'relay') && ($path == '/actor')) {
			return true;
		}

		if (in_array($apcontact['type'], ['Application', 'Service']) && empty($apcontact['following']) && empty($apcontact['followers'])) {
			return true;
		}

		if (($apcontact['type'] == 'Application') && ($apcontact['nick'] == 'relay') && in_array($path, ['/actor', '/relay'])) {
			return true;
		}

		return false;
	}
}
