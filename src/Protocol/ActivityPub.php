<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Protocol;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Model\APContact;
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
 * - Public Forum
 * - Private Forum
 * - Relocation
 */
class ActivityPub
{
	const PUBLIC_COLLECTION = 'https://www.w3.org/ns/activitystreams#Public';
	const CONTEXT = ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
		['vcard' => 'http://www.w3.org/2006/vcard/ns#',
		'dfrn' => 'http://purl.org/macgirvin/dfrn/1.0/',
		'diaspora' => 'https://diasporafoundation.org/ns/',
		'litepub' => 'http://litepub.social/ns#',
		'toot' => 'http://joinmastodon.org/ns#',
		'featured' => [
			"@id" => "toot:featured",
			"@type" => "@id",
		],
		'schema' => 'http://schema.org#',
		'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
		'sensitive' => 'as:sensitive', 'Hashtag' => 'as:Hashtag',
		'quoteUrl' => 'as:quoteUrl',
		'conversation' => 'ostatus:conversation',
		'directMessage' => 'litepub:directMessage',
		'discoverable' => 'toot:discoverable',
		'PropertyValue' => 'schema:PropertyValue',
		'value' => 'schema:value',
	]];
	const ACCOUNT_TYPES = ['Person', 'Organization', 'Service', 'Group', 'Application', 'Tombstone'];
	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return bool is it AP?
	 */
	public static function isRequest(): bool
	{
		$isrequest = stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/activity+json') ||
			stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
			stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/ld+json');

		if ($isrequest) {
			Logger::debug('Is AP request', ['accept' => $_SERVER['HTTP_ACCEPT'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}

		return $isrequest;
	}

	/**
	 * Fetches ActivityPub content from the given url
	 *
	 * @param string  $url content url
	 * @param integer $uid User ID for the signature
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchContent(string $url, int $uid = 0): array
	{
		return HTTPSignature::fetch($url, $uid);
	}

	private static function getAccountType(array $apcontact): int
	{
		$accounttype = -1;

		switch($apcontact['type']) {
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

		$profile = ['network' => Protocol::ACTIVITYPUB];
		$profile['nick'] = $apcontact['nick'];
		$profile['name'] = $apcontact['name'];
		$profile['guid'] = $apcontact['uuid'];
		$profile['url'] = $apcontact['url'];
		$profile['addr'] = $apcontact['addr'];
		$profile['alias'] = $apcontact['alias'];
		$profile['following'] = $apcontact['following'];
		$profile['followers'] = $apcontact['followers'];
		$profile['inbox'] = $apcontact['inbox'];
		$profile['outbox'] = $apcontact['outbox'];
		$profile['sharedinbox'] = $apcontact['sharedinbox'];
		$profile['photo'] = $apcontact['photo'];
		$profile['header'] = $apcontact['header'];
		$profile['account-type'] = self::getAccountType($apcontact);
		$profile['community'] = ($profile['account-type'] == User::ACCOUNT_TYPE_COMMUNITY);
		// $profile['keywords']
		// $profile['location']
		$profile['about'] = $apcontact['about'];
		$profile['xmpp'] = $apcontact['xmpp'];
		$profile['matrix'] = $apcontact['matrix'];
		$profile['batch'] = $apcontact['sharedinbox'];
		$profile['notify'] = $apcontact['inbox'];
		$profile['poll'] = $apcontact['outbox'];
		$profile['pubkey'] = $apcontact['pubkey'];
		$profile['subscribe'] = $apcontact['subscribe'];
		$profile['manually-approve'] = $apcontact['manually-approve'];
		$profile['baseurl'] = $apcontact['baseurl'];
		$profile['gsid'] = $apcontact['gsid'];

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
		$data = self::fetchContent($url, $uid);
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
	 * @param string $url  Address of the endpoint
	 * @param integer $uid Optional user id
	 * @return array Endpoint items
	 */
	public static function fetchItems(string $url, int $uid = 0): array
	{
		$data = self::fetchContent($url, $uid);
		if (empty($data)) {
			return [];
		}

		if (!empty($data['orderedItems'])) {
			$items = $data['orderedItems'];
		} elseif (!empty($data['first']['orderedItems'])) {
			$items = $data['first']['orderedItems'];
		} elseif (!empty($data['first']) && is_string($data['first']) && ($data['first'] != $url)) {
			return self::fetchItems($data['first'], $uid);
		} else {
			return [];
		}

		if (!empty($data['next']) && is_string($data['next'])) {
			$items = array_merge($items, self::fetchItems($data['next'], $uid));
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
}
