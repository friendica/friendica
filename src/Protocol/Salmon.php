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

namespace Friendica\Protocol;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\Probe;
use Friendica\Protocol\Salmon\Format\Magic;
use Friendica\Util\Crypto;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Salmon Protocol class
 *
 * The Salmon Protocol is a message exchange protocol running over HTTP designed to decentralize commentary
 * and annotations made against newsfeed articles such as blog posts.
 */
class Salmon
{
	/**
	 * @param string $uri     Uniform Resource Identifier
	 * @param string $keyhash encoded key
	 * @return string Key or empty string on any errors
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getKey(string $uri, string $keyhash): string
	{
		$ret = [];

		Logger::info('Fetching salmon key for '.$uri);

		$arr = Probe::lrdd($uri);

		if (is_array($arr)) {
			foreach ($arr as $a) {
				if ($a['@attributes']['rel'] === 'magic-public-key') {
					$ret[] = $a['@attributes']['href'];
				}
			}
		} else {
			return '';
		}

		// We have found at least one key URL
		// If it's inline, parse it - otherwise get the key

		if (count($ret) > 0) {
			for ($x = 0; $x < count($ret); $x ++) {
				if (substr($ret[$x], 0, 5) === 'data:') {
					if (strstr($ret[$x], ',')) {
						$ret[$x] = substr($ret[$x], strpos($ret[$x], ',') + 1);
					} else {
						$ret[$x] = substr($ret[$x], 5);
					}
				} elseif (Strings::normaliseLink($ret[$x]) == 'http://') {
					$ret[$x] = DI::httpClient()->fetch($ret[$x], HttpClientAccept::MAGIC_KEY);
					Logger::debug('Fetched public key', ['url' => $ret[$x]]);
				}
			}
		}


		Logger::notice('Key located', ['ret' => $ret]);

		if (count($ret) == 1) {
			/* We only found one one key so we don't care if the hash matches.
			 * If it's the wrong key we'll find out soon enough because
			 * message verification will fail. This also covers some older
			 * software which don't supply a keyhash. As long as they only
			 * have one key we'll be right.
			 */
			return (string) $ret[0];
		} else {
			foreach ($ret as $a) {
				$hash = Strings::base64UrlEncode(hash('sha256', $a));
				if ($hash == $keyhash) {
					return $a;
				}
			}
		}

		return '';
	}

	/**
	 * @param array  $owner owner
	 * @param string $url   url
	 * @param string $slap  slap
	 * @return integer
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function slapper(array $owner, string $url, string $slap): int
	{
		// does contact have a salmon endpoint?

		if (!strlen($url)) {
			return -1;
		}

		if (!$owner['sprvkey']) {
			Logger::notice(sprintf("user '%s' (%d) does not have a salmon private key. Send failed.",
			$owner['name'], $owner['uid']));
			return -1;
		}

		Logger::info('slapper called for '.$url.'. Data: ' . $slap);

		// create a magic envelope

		$data      = Strings::base64UrlEncode($slap);
		$data_type = 'application/atom+xml';
		$encoding  = 'base64url';
		$algorithm = 'RSA-SHA256';
		$keyhash   = Strings::base64UrlEncode(hash('sha256', self::salmonKey($owner['spubkey'])), true);

		$precomputed = '.' . Strings::base64UrlEncode($data_type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($algorithm);

		// GNU Social format
		$signature   = Strings::base64UrlEncode(Crypto::rsaSign($data . $precomputed, $owner['sprvkey']));

		// Compliant format
		$signature2  = Strings::base64UrlEncode(Crypto::rsaSign(str_replace('=', '', $data . $precomputed), $owner['sprvkey']));

		// Old Status.net format
		$signature3  = Strings::base64UrlEncode(Crypto::rsaSign($data, $owner['sprvkey']));

		// At first try the non compliant method that works for GNU Social
		$xmldata = [
			'me:env' => [
				'me:data' => $data,
				'@attributes' => ['type' => $data_type],
				'me:encoding' => $encoding,
				'me:alg' => $algorithm,
				'me:sig' => $signature,
				'@attributes2' => ['key_id' => $keyhash],
			]
		];

		$namespaces = ['me' => ActivityNamespace::SALMON_ME];

		$salmon = XML::fromArray($xmldata, $xml, false, $namespaces);

		// slap them
		$postResult = DI::httpClient()->post($url, $salmon, [
			'Content-type' => 'application/magic-envelope+xml',
			'Content-length' => strlen($salmon),
		]);

		$return_code = $postResult->getReturnCode();

		// check for success, e.g. 2xx

		if ($return_code > 299) {
			Logger::notice('GNU Social salmon failed. Falling back to compliant mode');

			// Now try the compliant mode that normally isn't used for GNU Social
			$xmldata = [
				'me:env' => [
					'me:data' => $data,
					'@attributes' => ['type' => $data_type],
					'me:encoding' => $encoding,
					'me:alg' => $algorithm,
					'me:sig' => $signature2,
					'@attributes2' => ['key_id' => $keyhash]
				]
			];

			$namespaces = ['me' => ActivityNamespace::SALMON_ME];

			$salmon = XML::fromArray($xmldata, $xml, false, $namespaces);

			// slap them
			$postResult = DI::httpClient()->post($url, $salmon, [
				'Content-type' => 'application/magic-envelope+xml',
				'Content-length' => strlen($salmon),
			]);
			$return_code = $postResult->getReturnCode();
		}

		if ($return_code > 299) {
			Logger::notice('compliant salmon failed. Falling back to old status.net');

			// Last try. This will most likely fail as well.
			$xmldata = [
				'me:env' => [
					'me:data' => $data,
					'@attributes' => ['type' => $data_type],
					'me:encoding' => $encoding,
					'me:alg' => $algorithm,
					'me:sig' => $signature3,
					'@attributes2' => ['key_id' => $keyhash],
				]
			];

			$namespaces = ['me' => ActivityNamespace::SALMON_ME];

			$salmon = XML::fromArray($xmldata, $xml, false, $namespaces);

			// slap them
			$postResult = DI::httpClient()->post($url, $salmon, [
				'Content-type' => 'application/magic-envelope+xml',
				'Content-length' => strlen($salmon)]);
			$return_code = $postResult->getReturnCode();
		}

		Logger::info('slapper for '.$url.' returned ' . $return_code);

		if (! $return_code) {
			return -1;
		}

		if (($return_code == 503) && $postResult->inHeader('retry-after')) {
			return -1;
		}

		return (($return_code >= 200) && ($return_code < 300)) ? 0 : 1;
	}

	/**
	 * @param string $pubkey public key
	 * @return string
	 * @throws \Exception
	 */
	public static function salmonKey(string $pubkey): string
	{
		\phpseclib3\Crypt\RSA::addFileFormat(Magic::class);

		return PublicKeyLoader::load($pubkey)->toString('Magic');
	}

	/**
	 * @param string $magic Magic key format starting with "RSA."
	 * @return string
	 */
	public static function magicKeyToPem(string $magic): string
	{
		\phpseclib3\Crypt\RSA::addFileFormat(Magic::class);

		return (string) PublicKeyLoader::load($magic);
	}
}
