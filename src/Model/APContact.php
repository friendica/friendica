<?php

/**
 * @file src/Model/APContact.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Content\Text\HTML;
use Friendica\Core\Logger;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Network;
use Friendica\Util\JsonLD;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class APContact extends BaseObject
{
	/**
	 * Resolves the profile url from the address by using webfinger
	 *
	 * @param string $addr profile address (user@domain.tld)
	 * @param string $url profile URL. When set then we return "true" when this profile url can be found at the address
	 * @return string|boolean url
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addrToUrl($addr, $url = null)
	{
		$addr_parts = explode('@', $addr);
		if (count($addr_parts) != 2) {
			return false;
		}

		$xrd_timeout = Config::get('system', 'xrd_timeout');

		$webfinger = 'https://' . $addr_parts[1] . '/.well-known/webfinger?resource=acct:' . urlencode($addr);

		$curlResult = Network::curl($webfinger, false, ['timeout' => $xrd_timeout, 'accept_content' => 'application/jrd+json,application/json']);
		if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
			$webfinger = Strings::normaliseLink($webfinger);

			$curlResult = Network::curl($webfinger, false, ['timeout' => $xrd_timeout, 'accept_content' => 'application/jrd+json,application/json']);

			if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
				return false;
			}
		}

		$data = json_decode($curlResult->getBody(), true);

		if (empty($data['links'])) {
			return false;
		}

		foreach ($data['links'] as $link) {
			if (!empty($url) && !empty($link['href']) && ($link['href'] == $url)) {
				return true;
			}

			if (empty($link['href']) || empty($link['rel']) || empty($link['type'])) {
				continue;
			}

			if (empty($url) && ($link['rel'] == 'self') && ($link['type'] == 'application/activity+json')) {
				return $link['href'];
			}
		}

		return false;
	}

	/**
	 * Fetches a profile from a given url
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @return array profile array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getByURL($url, $update = null)
	{
		if (empty($url)) {
			return false;
		}

		$fetched_contact = false;

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

			if (DBA::isResult($apcontact) && ($apcontact['updated'] > $ref_update) && !empty($apcontact['pubkey'])) {
				return $apcontact;
			}

			if (!is_null($update)) {
				return DBA::isResult($apcontact) ? $apcontact : false;
			}

			if (DBA::isResult($apcontact)) {
				$fetched_contact = $apcontact;
			}
		}

		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			$url = self::addrToUrl($url);
			if (empty($url)) {
				return $fetched_contact;
			}
		}

		$data = ActivityPub::fetchContent($url);
		if (empty($data)) {
			return $fetched_contact;
		}

		$compacted = JsonLD::compact($data);

		if (empty($compacted['@id'])) {
			return $fetched_contact;
		}

		$apcontact = [];
		$apcontact['url'] = $compacted['@id'];
		$apcontact['uuid'] = JsonLD::fetchElement($compacted, 'diaspora:guid', '@value');
		$apcontact['type'] = str_replace('as:', '', JsonLD::fetchElement($compacted, '@type'));
		$apcontact['following'] = JsonLD::fetchElement($compacted, 'as:following', '@id');
		$apcontact['followers'] = JsonLD::fetchElement($compacted, 'as:followers', '@id');
		$apcontact['inbox'] = JsonLD::fetchElement($compacted, 'ldp:inbox', '@id');
		self::unarchiveInbox($apcontact['inbox'], false);

		$apcontact['outbox'] = JsonLD::fetchElement($compacted, 'as:outbox', '@id');

		$apcontact['sharedinbox'] = '';
		if (!empty($compacted['as:endpoints'])) {
			$apcontact['sharedinbox'] = JsonLD::fetchElement($compacted['as:endpoints'], 'as:sharedInbox', '@id');
			self::unarchiveInbox($apcontact['sharedinbox'], true);
		}

		$apcontact['nick'] = JsonLD::fetchElement($compacted, 'as:preferredUsername', '@value') ?? '';
		$apcontact['name'] = JsonLD::fetchElement($compacted, 'as:name', '@value');

		if (empty($apcontact['name'])) {
			$apcontact['name'] = $apcontact['nick'];
		}

		$apcontact['about'] = HTML::toBBCode(JsonLD::fetchElement($compacted, 'as:summary', '@value'));

		$apcontact['photo'] = JsonLD::fetchElement($compacted, 'as:icon', '@id');
		if (is_array($apcontact['photo']) || !empty($compacted['as:icon']['as:url']['@id'])) {
			$apcontact['photo'] = JsonLD::fetchElement($compacted['as:icon'], 'as:url', '@id');
		}

		$apcontact['alias'] = JsonLD::fetchElement($compacted, 'as:url', '@id');
		if (is_array($apcontact['alias'])) {
			$apcontact['alias'] = JsonLD::fetchElement($compacted['as:url'], 'as:href', '@id');
		}

		// Quit if none of the basic values are set
		if (empty($apcontact['url']) || empty($apcontact['inbox']) || empty($apcontact['type'])) {
			return $fetched_contact;
		}

		// Quit if this doesn't seem to be an account at all
		if (!in_array($apcontact['type'], ActivityPub::ACCOUNT_TYPES)) {
			return $fetched_contact;
		}

		$parts = parse_url($apcontact['url']);
		unset($parts['scheme']);
		unset($parts['path']);

		if (!empty($apcontact['nick'])) {
			$apcontact['addr'] = $apcontact['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));
		} else {
			$apcontact['addr'] = '';
		}

		if (!empty($compacted['w3id:publicKey'])) {
			$apcontact['pubkey'] = trim(JsonLD::fetchElement($compacted['w3id:publicKey'], 'w3id:publicKeyPem', '@value'));
		}

		$apcontact['manually-approve'] = (int)JsonLD::fetchElement($compacted, 'as:manuallyApprovesFollowers');

		if (!empty($compacted['as:generator'])) {
			$apcontact['baseurl'] = JsonLD::fetchElement($compacted['as:generator'], 'as:url', '@id');
			$apcontact['generator'] = JsonLD::fetchElement($compacted['as:generator'], 'as:name', '@value');
		}

		if (!empty($apcontact['following'])) {
			$data = ActivityPub::fetchContent($apcontact['following']);
			if (!empty($data)) {
				if (!empty($data['totalItems'])) {
					$apcontact['following_count'] = $data['totalItems'];
				}
			}
		}

		if (!empty($apcontact['followers'])) {
			$data = ActivityPub::fetchContent($apcontact['followers']);
			if (!empty($data)) {
				if (!empty($data['totalItems'])) {
					$apcontact['followers_count'] = $data['totalItems'];
				}
			}
		}

		if (!empty($apcontact['outbox'])) {
			$data = ActivityPub::fetchContent($apcontact['outbox']);
			if (!empty($data)) {
				if (!empty($data['totalItems'])) {
					$apcontact['statuses_count'] = $data['totalItems'];
				}
			}
		}

		// To-Do

		// Unhandled
		// tag, attachment, image, nomadicLocations, signature, featured, movedTo, liked

		// Unhandled from Misskey
		// sharedInbox, isCat

		// Unhandled from Kroeg
		// kroeg:blocks, updated

		$parts = parse_url($apcontact['url']);
		unset($parts['path']);
		$baseurl = Network::unparseURL($parts);

		// Check if the address is resolvable or the profile url is identical with the base url of the system
		if (self::addrToUrl($apcontact['addr'], $apcontact['url']) || Strings::compareLink($apcontact['url'], $baseurl)) {
			$apcontact['baseurl'] = $baseurl;
		} else {
			$apcontact['addr'] = null;
		}

		if (empty($apcontact['baseurl'])) {
			$apcontact['baseurl'] = null;
		}

		if ($apcontact['url'] == $apcontact['alias']) {
			$apcontact['alias'] = null;
		}

		$apcontact['updated'] = DateTimeFormat::utcNow();

		DBA::update('apcontact', $apcontact, ['url' => $url], true);

		// We delete the old entry when the URL is changed
		if (($url != $apcontact['url']) && DBA::exists('apcontact', ['url' => $url]) && DBA::exists('apcontact', ['url' => $apcontact['url']])) {
			DBA::delete('apcontact', ['url' => $url]);
		}

		Logger::log('Updated profile for ' . $url, Logger::DEBUG);

		return $apcontact;
	}

	/**
	 * Unarchive inboxes
	 *
	 * @param string $url inbox url
	 */
	private static function unarchiveInbox($url, $shared)
	{
		if (empty($url)) {
			return;
		}

		$now = DateTimeFormat::utcNow();

		$fields = ['archive' => false, 'success' => $now, 'shared' => $shared];

		if (!DBA::exists('inbox-status', ['url' => $url])) {
			$fields = array_merge($fields, ['url' => $url, 'created' => $now]);
			DBA::insert('inbox-status', $fields);
		} else {
			DBA::update('inbox-status', $fields, ['url' => $url]);
		}
	}
}
