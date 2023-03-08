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

namespace Friendica\Util;

use Friendica\Core\Logger;
use Friendica\Model\APContact;

/**
 * Implements JSON-LD signatures
 *
 * Ported from Osada: https://framagit.org/macgirvin/osada
 */
class LDSignature
{
	/**
	 * Checks if element 'signature' is found and not empty
	 *
	 * @param array $data
	 * @return bool
	 */
	public static function isSigned(array $data): bool
	{
		return !empty($data['signature']);
	}

	/**
	 * Returns actor (signer) from given data
	 *
	 * @param array $data
	 * @return mixed Returns actor or false on error
	 */
	public static function getSigner(array $data)
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
		Logger::info('LD-verify', ['verified' => (int)$x, 'actor' => $profile['url']]);

		if (empty($x)) {
			return false;
		} else {
			return $actor;
		}
	}

	/**
	 * Signs given data by owner's signature
	 *
	 * @param array $data Data to sign
	 * @param array $owner Owner information, like URL
	 * @return array Merged array of $data and signature
	 */
	public static function sign(array $data, array $owner): array
	{
		$options = [
			'type' => 'RsaSignature2017',
			'nonce' => Strings::getRandomHex(64),
			'creator' => $owner['url'] . '#main-key',
			'created' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
		];

		$ohash = self::hash(self::signableOptions($options));
		$dhash = self::hash(self::signableData($data));
		$options['signatureValue'] = base64_encode(Crypto::rsaSign($ohash . $dhash, $owner['uprvkey']));

		return array_merge($data, ['signature' => $options]);
	}

	/**
	 * Removes element 'signature' from array
	 *
	 * @param array $data
	 * @return array With no element 'signature'
	 */
	private static function signableData(array $data): array
	{
		unset($data['signature']);
		return $data;
	}

	/**
	 * Removes some elements and adds '@context' to it
	 *
	 * @param array $options
	 * @return array With some removed elements and added '@context' element
	 */
	private static function signableOptions(array $options): array
	{
		$newopts = ['@context' => 'https://w3id.org/identity/v1'];

		unset($options['type']);
		unset($options['id']);
		unset($options['signatureValue']);

		return array_merge($newopts, $options);
	}

	/**
	 * Hashes normalized object
	 *
	 * @param ??? $obj
	 * @return string SHA256 hash
	 */
	private static function hash($obj): string
	{
		return hash('sha256', JsonLD::normalize($obj));
	}
}
