<?php

namespace Friendica\Util;

use Friendica\Core\Logger;
use Friendica\Model\APContact;

/**
 * @brief Implements JSON-LD signatures
 *
 * Ported from Osada: https://framagit.org/macgirvin/osada
 */
class LDSignature
{
	public static function isSigned($data)
	{
		return !empty($data['signature']);
	}

	public static function getSigner($data)
	{
		if (!self::isSigned($data)) {
			return false;
		}

		$actor = JsonLD::fetchElement($data, 'actor', 'id');
		if (empty($actor) || !is_string($actor)) {
			return false;
		}

		$profile = APContact::getByURL($actor);
		if (empty($profile['pubkey'])) {
			return false;
		}
		$pubkey = $profile['pubkey'];

		$ohash = self::hash(self::signableOptions($data['signature']));
		$dhash = self::hash(self::signableData($data));

		$x = Crypto::rsaVerify($ohash . $dhash, base64_decode($data['signature']['signatureValue']), $pubkey);
		Logger::log('LD-verify: ' . intval($x));

		if (empty($x)) {
			return false;
		} else {
			return $actor;
		}
	}

	public static function sign($data, $owner)
	{
		$options = [
			'type' => 'RsaSignature2017',
			'nonce' => Strings::getRandomHex(64),
			'creator' => $owner['url'] . '#main-key',
			'created' => DateTimeFormat::utcNow(DateTimeFormat::ATOM)
		];

		$ohash = self::hash(self::signableOptions($options));
		$dhash = self::hash(self::signableData($data));
		$options['signatureValue'] = base64_encode(Crypto::rsaSign($ohash . $dhash, $owner['uprvkey']));

		return array_merge($data, ['signature' => $options]);
	}

	private static function signableData($data)
	{
		unset($data['signature']);
		return $data;
	}

	private static function signableOptions($options)
	{
		$newopts = ['@context' => 'https://w3id.org/identity/v1'];

		unset($options['type']);
		unset($options['id']);
		unset($options['signatureValue']);

		return array_merge($newopts, $options);
	}

	private static function hash($obj)
	{
		return hash('sha256', JsonLD::normalize($obj));
	}
}
