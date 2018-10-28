<?php

/**
 * @file src/Model/APContact.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Content\Text;
use Friendica\Database\DBA;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Network;
use Friendica\Util\JsonLD;
use Friendica\Util\DateTimeFormat;
use Friendica\Content\Text\HTML;

require_once 'boot.php';

class APContact extends BaseObject
{
	/**
	 * Resolves the profile url from the address by using webfinger
	 *
	 * @param string $addr profile address (user@domain.tld)
	 * @return string url
	 */
	private static function addrToUrl($addr)
	{
		$addr_parts = explode('@', $addr);
		if (count($addr_parts) != 2) {
			return false;
		}

		$webfinger = 'https://' . $addr_parts[1] . '/.well-known/webfinger?resource=acct:' . urlencode($addr);

		$curlResult = Network::curl($webfinger, false, $redirects, ['accept_content' => 'application/jrd+json,application/json']);
		if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
			return false;
		}

		$data = json_decode($curlResult->getBody(), true);

		if (empty($data['links'])) {
			return false;
		}

		foreach ($data['links'] as $link) {
			if (empty($link['href']) || empty($link['rel']) || empty($link['type'])) {
				continue;
			}

			if (($link['rel'] == 'self') && ($link['type'] == 'application/activity+json')) {
				return $link['href'];
			}
		}

		return false;
	}

	/**
	 * Fetches a profile from a given url
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found
	 * @return array profile array
	 */
	public static function getByURL($url, $update = null)
	{
		if (empty($url)) {
			return false;
		}

		if (empty($update)) {
			$apcontact = DBA::selectFirst('apcontact', [], ['url' => $url]);
			if (DBA::isResult($apcontact)) {
				return $apcontact;
			}

			$apcontact = DBA::selectFirst('apcontact', [], ['alias' => $url]);
			if (DBA::isResult($apcontact)) {
				return $apcontact;
			}

			$apcontact = DBA::selectFirst('apcontact', [], ['addr' => $url]);
			if (DBA::isResult($apcontact)) {
				return $apcontact;
			}

			if (!is_null($update)) {
				return false;
			}
		}

		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			$url = self::addrToUrl($url);
			if (empty($url)) {
				return false;
			}
		}

		$data = ActivityPub::fetchContent($url);
		if (empty($data)) {
			return false;
		}

		$compacted = JsonLD::compact($data);

		$apcontact = [];
		$apcontact['url'] = $compacted['@id'];
		$apcontact['uuid'] = JsonLD::fetchElement($compacted, 'diaspora:guid');
		$apcontact['type'] = str_replace('as:', '', JsonLD::fetchElement($compacted, '@type'));
		$apcontact['following'] = JsonLD::fetchElement($compacted, 'as:following', '@id');
		$apcontact['followers'] = JsonLD::fetchElement($compacted, 'as:followers', '@id');
		$apcontact['inbox'] = JsonLD::fetchElement($compacted, 'ldp:inbox', '@id');
		$apcontact['outbox'] = JsonLD::fetchElement($compacted, 'as:outbox', '@id');

		$apcontact['sharedinbox'] = '';
		if (!empty($compacted['as:endpoints'])) {
			$apcontact['sharedinbox'] = JsonLD::fetchElement($compacted['as:endpoints'], 'as:sharedInbox', '@id');
		}

		$apcontact['nick'] = JsonLD::fetchElement($compacted, 'as:preferredUsername');
		$apcontact['name'] = JsonLD::fetchElement($compacted, 'as:name');

		if (empty($apcontact['name'])) {
			$apcontact['name'] = $apcontact['nick'];
		}

		$apcontact['about'] = HTML::toBBCode(JsonLD::fetchElement($compacted, 'as:summary'));

		$apcontact['photo'] = JsonLD::fetchElement($compacted, 'as:icon', '@id');
		if (is_array($apcontact['photo'])) {
			$apcontact['photo'] = JsonLD::fetchElement($compacted['as:icon'], 'as:url', '@id');
		}

		$apcontact['alias'] = JsonLD::fetchElement($compacted, 'as:url', '@id');
		if (is_array($apcontact['alias'])) {
			$apcontact['alias'] = JsonLD::fetchElement($compacted['as:url'], 'as:href', '@id');
		}

		if (empty($apcontact['url']) || empty($apcontact['inbox'])) {
			return false;
		}

		$parts = parse_url($apcontact['url']);
		unset($parts['scheme']);
		unset($parts['path']);
		$apcontact['addr'] = $apcontact['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));

		$apcontact['pubkey'] = trim(JsonLD::fetchElement($compacted, 'w3id:publicKey', 'w3id:publicKeyPem'));

		// To-Do
		// manuallyApprovesFollowers

		// Unhandled
		// @context, tag, attachment, image, nomadicLocations, signature, following, followers, featured, movedTo, liked

		// Unhandled from Misskey
		// sharedInbox, isCat

		// Unhandled from Kroeg
		// kroeg:blocks, updated

		// Check if the address is resolvable
		if (self::addrToUrl($apcontact['addr']) == $apcontact['url']) {
			$parts = parse_url($apcontact['url']);
			unset($parts['path']);
			$apcontact['baseurl'] = Network::unparseURL($parts);
		} else {
			$apcontact['addr'] = null;
			$apcontact['baseurl'] = null;
		}

		if ($apcontact['url'] == $apcontact['alias']) {
			$apcontact['alias'] = null;
		}

		$apcontact['updated'] = DateTimeFormat::utcNow();

		DBA::update('apcontact', $apcontact, ['url' => $url], true);

		// Update some data in the contact table with various ways to catch them all
		$contact_fields = ['name' => $apcontact['name'], 'about' => $apcontact['about']];
		DBA::update('contact', $contact_fields, ['nurl' => normalise_link($url)]);

		$contacts = DBA::select('contact', ['uid', 'id'], ['nurl' => normalise_link($url)]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::updateAvatar($apcontact['photo'], $contact['uid'], $contact['id']);
		}
		DBA::close($contacts);

		// Update the gcontact table
		DBA::update('gcontact', $contact_fields, ['nurl' => normalise_link($url)]);

		Text::logger('Updated profile for ' . $url, LOGGER_DEBUG);

		return $apcontact;
	}
}
