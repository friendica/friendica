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

namespace Friendica\Network;

use DOMDocument;
use DomXPath;
use Exception;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Email;
use Friendica\Protocol\Feed;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use GuzzleHttp\Psr7\Uri;

/**
 * This class contain functions for probing URL
 */
class Probe
{
	const HOST_META = '/.well-known/host-meta';
	const WEBFINGER = '/.well-known/webfinger?resource={uri}';

	/**
	 * @var string Base URL
	 */
	private static $baseurl;

	/**
	 * @var boolean Whether a timeout has occured
	 */
	private static $isTimeout;

	/**
	 * Checks if the provided network can be probed
	 *
	 * @param string $network
	 *
	 * @return boolean
	 */
	public static function isProbable(string $network): bool
	{
		return (in_array($network, array_merge(Protocol::FEDERATED, [Protocol::ZOT, Protocol::PHANTOM])));
	}

	/**
	 * Remove stuff from an URI that doesn't belong there
	 *
	 * @param string $rawUri
	 * @return string Cleaned URI
	 */
	public static function cleanURI(string $rawUri): string
	{
		// At first remove leading and trailing junk
		$rawUri = trim($rawUri, "@#?: \t\n\r\0\x0B");

		$rawUri = Network::convertToIdn($rawUri);

		$uri = new Uri($rawUri);
		if (!$uri->getScheme()) {
			return $uri->__toString();
		}

		// Remove the URL fragment, since these shouldn't be part of any profile URL
		$uri = $uri->withFragment('');

		return $uri->__toString();
	}

	/**
	 * Rearrange the array so that it always has the same order
	 *
	 * @param array $data Unordered data
	 * @return array Ordered data
	 */
	private static function rearrangeData(array $data): array
	{
		$fields = ['name', 'given_name', 'family_name', 'nick', 'guid', 'url', 'addr', 'alias',
		        'photo', 'photo_medium', 'photo_small', 'header',
				'account-type', 'community', 'keywords', 'location', 'about', 'xmpp', 'matrix',
				'hide', 'batch', 'notify', 'poll', 'request', 'confirm', 'subscribe', 'poco',
				'following', 'followers', 'inbox', 'outbox', 'sharedinbox',
				'priority', 'network', 'pubkey', 'manually-approve', 'baseurl', 'gsid'];

		$numeric_fields = ['gsid', 'hide', 'account-type', 'manually-approve'];

		$newdata = [];
		foreach ($fields as $field) {
			if (isset($data[$field])) {
				if (in_array($field, $numeric_fields)) {
					$newdata[$field] = (int)$data[$field];
				} else {
					$newdata[$field] = trim($data[$field]);
				}
			} elseif (!in_array($field, $numeric_fields)) {
				$newdata[$field] = '';
			} else {
				$newdata[$field] = null;
			}
		}

		// We don't use the "priority" field anymore and replace it with a dummy.
		$newdata['priority'] = 0;

		return $newdata;
	}

	/**
	 * Check if the hostname belongs to the own server
	 *
	 * @param string $host The hostname that is to be checked
	 * @return bool Does the testes hostname belongs to the own server?
	 */
	private static function ownHost(string $host): bool
	{
		$own_host = DI::baseUrl()->getHostname();

		$parts = parse_url($host);

		if (!isset($parts['scheme'])) {
			$parts = parse_url('http://' . $host);
		}

		if (!isset($parts['host'])) {
			return false;
		}
		return $parts['host'] == $own_host;
	}

	/**
	 * Probes for webfinger path via "host-meta"
	 *
	 * We have to check if the servers in the future still will offer this.
	 * It seems as if it was dropped from the standard.
	 *
	 * @param string $host The host part of an url
	 *
	 * @return array with template and type of the webfinger template for JSON or XML
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function hostMeta(string $host): array
	{
		// Reset the static variable
		self::$baseurl = '';

		// Handles the case when the hostname contains the scheme
		if (!parse_url($host, PHP_URL_SCHEME)) {
			$ssl_url = 'https://' . $host . self::HOST_META;
			$url = 'http://' . $host . self::HOST_META;
		} else {
			$ssl_url = $host . self::HOST_META;
			$url = '';
		}

		$xrd_timeout = DI::config()->get('system', 'xrd_timeout', 20);

		Logger::info('Probing', ['host' => $host, 'ssl_url' => $ssl_url, 'url' => $url, 'callstack' => System::callstack(20)]);
		$xrd = null;

		$curlResult = DI::httpClient()->get($ssl_url, HttpClientAccept::XRD_XML, [HttpClientOptions::TIMEOUT => $xrd_timeout]);
		$ssl_connection_error = ($curlResult->getErrorNumber() == CURLE_COULDNT_CONNECT) || ($curlResult->getReturnCode() == 0);
		if ($curlResult->isSuccess()) {
			$xml = $curlResult->getBody();
			$xrd = XML::parseString($xml, true);
			if (!empty($url)) {
				$host_url = 'https://' . $host;
			} else {
				$host_url = $host;
			}
		} elseif ($curlResult->isTimeout()) {
			Logger::info('Probing timeout', ['url' => $ssl_url]);
			self::$isTimeout = true;
			return [];
		}

		if (!is_object($xrd) && !empty($url)) {
			$curlResult = DI::httpClient()->get($url, HttpClientAccept::XRD_XML, [HttpClientOptions::TIMEOUT => $xrd_timeout]);
			$connection_error = ($curlResult->getErrorNumber() == CURLE_COULDNT_CONNECT) || ($curlResult->getReturnCode() == 0);
			if ($curlResult->isTimeout()) {
				Logger::info('Probing timeout', ['url' => $url]);
				self::$isTimeout = true;
				return [];
			} elseif ($connection_error && $ssl_connection_error) {
				self::$isTimeout = true;
				return [];
			}

			$xml = $curlResult->getBody();
			$xrd = XML::parseString($xml, true);
			$host_url = 'http://'.$host;
		}
		if (!is_object($xrd)) {
			Logger::info('No xrd object found', ['host' => $host]);
			return [];
		}

		$links = XML::elementToArray($xrd);
		if (!isset($links['xrd']['link'])) {
			Logger::info('No xrd data found', ['host' => $host]);
			return [];
		}

		$lrdd = [];

		foreach ($links['xrd']['link'] as $value => $link) {
			if (!empty($link['@attributes'])) {
				$attributes = $link['@attributes'];
			} elseif ($value == '@attributes') {
				$attributes = $link;
			} else {
				continue;
			}

			if (!empty($attributes['rel']) && $attributes['rel'] == 'lrdd' && !empty($attributes['template'])) {
				$type = (empty($attributes['type']) ? '' : $attributes['type']);

				$lrdd[$type] = $attributes['template'];
			}
		}

		if (Network::isUrlBlocked($host_url)) {
			Logger::info('Domain is blocked', ['url' => $host]);
			return [];
		}

		self::$baseurl = $host_url;

		Logger::info('Probing successful', ['host' => $host]);

		return $lrdd;
	}

	/**
	 * Check an URI for LRDD data
	 *
	 * @param string $uri     Address that should be probed
	 * @return array uri data
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function lrdd(string $uri): array
	{
		$data = self::getWebfingerArray($uri);
		if (empty($data)) {
			return [];
		}
		$webfinger = $data['webfinger'];

		if (empty($webfinger['links'])) {
			Logger::info('No webfinger links found', ['uri' => $uri]);
			return [];
		}

		$data = [];

		foreach ($webfinger['links'] as $link) {
			$data[] = ['@attributes' => $link];
		}

		if (!empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				$data[] = [
					'@attributes' => [
						'rel' => 'alias',
						'href' => $alias,
					]
				];
			}
		}

		return $data;
	}

	/**
	 * Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * @param string  $uri     Address that should be probed
	 * @param string  $network Test for this specific network
	 * @param integer $uid     User ID for the probe (only used for mails)
	 * @param boolean $cache   Use cached values?
	 *
	 * @return array uri data
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function uri(string $uri, string $network = '', int $uid = -1): array
	{
		// Local profiles aren't probed via network
		if (empty($network) && Contact::isLocal($uri)) {
			$data = self::localProbe($uri);
			if (!empty($data)) {
				return $data;
			}
		}

		if ($uid == -1) {
			$uid = DI::userSession()->getLocalUserId();
		}

		if (empty($network) || ($network == Protocol::ACTIVITYPUB)) {
			$ap_profile = ActivityPub::probeProfile($uri);
		} else {
			$ap_profile = [];
		}

		self::$isTimeout = false;

		if ($network != Protocol::ACTIVITYPUB) {
			$data = self::detect($uri, $network, $uid, $ap_profile);
			if (!is_array($data)) {
				$data = [];
			}
			if (empty($data) || (!empty($ap_profile) && empty($network) && (($data['network'] ?? '') != Protocol::DFRN))) {
				$data = $ap_profile;
			} elseif (!empty($ap_profile)) {
				$ap_profile['batch'] = '';
				$data = array_merge($ap_profile, $data);
			}
		} else {
			$data = $ap_profile;
		}

		if (!isset($data['url'])) {
			$data['url'] = $uri;
		}

		if (empty($data['photo'])) {
			$data['photo'] = DI::baseUrl() . Contact::DEFAULT_AVATAR_PHOTO;
		}

		if (empty($data['name'])) {
			if (!empty($data['nick'])) {
				$data['name'] = $data['nick'];
			}

			if (empty($data['name'])) {
				$data['name'] = $data['url'];
			}
		}

		if (empty($data['nick'])) {
			$data['nick'] = strtolower($data['name']);

			if (strpos($data['nick'], ' ')) {
				$data['nick'] = trim(substr($data['nick'], 0, strpos($data['nick'], ' ')));
			}
		}

		if (!empty($data['baseurl']) && empty($data['gsid'])) {
			$data['gsid'] = GServer::getID($data['baseurl']);
		}

		if (empty($data['network'])) {
			$data['network'] = Protocol::PHANTOM;
		}

		// Ensure that local connections always are DFRN
		if (($network == '') && ($data['network'] != Protocol::PHANTOM) && (self::ownHost($data['baseurl'] ?? '') || self::ownHost($data['url']))) {
			$data['network'] = Protocol::DFRN;
		}

		if (!isset($data['hide']) && in_array($data['network'], Protocol::FEDERATED)) {
			$data['hide'] = self::getHideStatus($data['url']);
		}

		return self::rearrangeData($data);
	}


	/**
	 * Fetches the "hide" status from the profile
	 *
	 * @param string $url URL of the profile
	 * @return boolean "hide" status
	 */
	private static function getHideStatus(string $url): bool
	{
		$curlResult = DI::httpClient()->get($url, HttpClientAccept::HTML, [HttpClientOptions::CONTENT_LENGTH => 1000000]);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		// If it isn't a HTML file then exit
		if (($curlResult->getContentType() != '') && !strstr(strtolower($curlResult->getContentType()), 'html')) {
			return false;
		}

		$body = $curlResult->getBody();
		if (empty($body)) {
			return false;
		}

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		$xpath = new DOMXPath($doc);

		$list = $xpath->query('//meta[@name]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (empty($meta_tag['content'])) {
				continue;
			}

			$content = strtolower(trim($meta_tag['content']));

			switch (strtolower(trim($meta_tag['name']))) {
				case 'dfrn-global-visibility':
					if ($content == 'false') {
						return true;
					}
					break;
				case 'robots':
					if (strpos($content, 'noindex') !== false) {
						return true;
					}
					break;
			}
		}

		return false;
	}

	/**
	 * Fetch the "subscribe" and add it to the result
	 *
	 * @param array $result Result array
	 * @param array $webfinger Webfinger data
	 *
	 * @return array result Altered/unaltered result array
	 */
	private static function getSubscribeLink(array $result, array $webfinger): array
	{
		if (empty($webfinger['links'])) {
			return $result;
		}

		foreach ($webfinger['links'] as $link) {
			if (!empty($link['template']) && ($link['rel'] === ActivityNamespace::OSTATUSSUB)) {
				$result['subscribe'] = $link['template'];
			}
		}

		return $result;
	}

	/**
	 * Get webfinger data from a given URI
	 *
	 * @param string $uri URI
	 *
	 * @return array Webfinger data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function getWebfingerArray(string $uri): array
	{
		$parts = parse_url($uri);

		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			$host = $parts['host'];
			if (!empty($parts['port'])) {
				$host .= ':' . $parts['port'];
			}

			$baseurl = $parts['scheme'] . '://' . $host;

			$nick = '';
			$addr = '';

			$path_parts = explode('/', trim($parts['path'] ?? '', '/'));
			if (!empty($path_parts)) {
				$nick = ltrim(end($path_parts), '@');
				$addr = $nick . '@' . $host;
			}

			$webfinger = self::getWebfinger($parts['scheme'] . '://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
			if (empty($webfinger)) {
				$lrdd = self::hostMeta($host);
			}

			if (empty($webfinger) && empty($lrdd)) {
				while (empty($lrdd) && empty($webfinger) && (sizeof($path_parts) > 1)) {
					$host    .= '/' . array_shift($path_parts);
					$baseurl = $parts['scheme'] . '://' . $host;

					if (!empty($nick)) {
						$addr = $nick . '@' . $host;
					}

					$webfinger = self::getWebfinger($parts['scheme'] . '://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
					if (empty($webfinger)) {
						$lrdd = self::hostMeta($host);
					}
				}

				if (empty($lrdd) && empty($webfinger)) {
					return [];
				}
			}
		} elseif (strstr($uri, '@')) {
			// Remove "acct:" from the URI
			$uri = str_replace('acct:', '', $uri);

			$host = substr($uri, strpos($uri, '@') + 1);
			$nick = substr($uri, 0, strpos($uri, '@'));
			$addr = $uri;

			$webfinger = self::getWebfinger('https://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
			if (self::$isTimeout) {
				return [];
			}

			if (empty($webfinger)) {
				$webfinger = self::getWebfinger('http://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
				if (self::$isTimeout) {
					return [];
				}
			} else {
				$baseurl = 'https://' . $host;
			}

			if (empty($webfinger)) {
				$lrdd = self::hostMeta($host);
				if (self::$isTimeout) {
					return [];
				}
				$baseurl = self::$baseurl;
			} else {
				$baseurl = 'http://' . $host;
			}
		} else {
			Logger::info('URI was not detectable', ['uri' => $uri]);
			return [];
		}

		if (empty($webfinger)) {
			foreach ($lrdd as $type => $template) {
				if ($webfinger) {
					continue;
				}

				$webfinger = self::getWebfinger($template, $type, $uri, $addr);
			}
		}

		if (empty($webfinger)) {
			return [];
		}

		if ($webfinger['detected'] == $addr) {
			$webfinger['nick'] = $nick;
			$webfinger['addr'] = $addr;
		}

		$webfinger['baseurl'] = $baseurl;

		return $webfinger;
	}

	/**
	 * Perform network request for webfinger data
	 *
	 * @param string $template
	 * @param string $type
	 * @param string $uri
	 * @param string $addr
	 *
	 * @return array webfinger results
	 */
	private static function getWebfinger(string $template, string $type, string $uri, string $addr): array
	{
		if (Network::isUrlBlocked($template)) {
			Logger::info('Domain is blocked', ['url' => $template]);
			return [];
		}

		// First try the address because this is the primary purpose of webfinger
		if (!empty($addr)) {
			$detected = $addr;
			$path = str_replace('{uri}', urlencode('acct:' . $addr), $template);
			$webfinger = self::webfinger($path, $type);
			if (self::$isTimeout) {
				return [];
			}
		}

		// Then try the URI
		if (empty($webfinger) && $uri != $addr) {
			$detected = $uri;
			$path = str_replace('{uri}', urlencode($uri), $template);
			$webfinger = self::webfinger($path, $type);
			if (self::$isTimeout) {
				return [];
			}
		}

		if (empty($webfinger)) {
			return [];
		}

		return ['webfinger' => $webfinger, 'detected' => $detected];
	}

	/**
	 * Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * This function is only called by the "uri" function that adds caching and rearranging of data.
	 *
	 * @param string  $uri        Address that should be probed
	 * @param string  $network    Test for this specific network
	 * @param integer $uid        User ID for the probe (only used for mails)
	 * @param array   $ap_profile Previously probed AP profile
	 * @return array URI data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function detect(string $uri, string $network, int $uid, array $ap_profile): array
	{
		$hookData = [
			'uri'     => $uri,
			'network' => $network,
			'uid'     => $uid,
			'result'  => null,
		];

		Hook::callAll('probe_detect', $hookData);

		if (isset($hookData['result'])) {
			return is_array($hookData['result']) ? $hookData['result'] : [];
		}

		$parts = parse_url($uri);
		if (empty($parts['scheme']) && empty($parts['host']) && !strstr($parts['path'], '@')) {
			Logger::info('URI was not detectable', ['uri' => $uri]);
			return [];
		}

		// If the URI starts with "mailto:" then jump directly to the mail detection
		if (strpos($uri, 'mailto:') !== false) {
			$uri = str_replace('mailto:', '', $uri);
			return self::mail($uri, $uid);
		}

		if ($network == Protocol::MAIL) {
			return self::mail($uri, $uid);
		}

		Logger::info('Probing start', ['uri' => $uri]);

		if (!empty($ap_profile['addr']) && ($ap_profile['addr'] != $uri)) {
			$data = self::getWebfingerArray($ap_profile['addr']);
		}

		if (empty($data)) {
			$data = self::getWebfingerArray($uri);
		}

		if (empty($data)) {
			if (!empty($parts['scheme'])) {
				return self::feed($uri);
			} elseif (!empty($uid)) {
				return self::mail($uri, $uid);
			} else {
				return [];
			}
		}

		$webfinger = $data['webfinger'];
		$nick = $data['nick'] ?? '';
		$addr = $data['addr'] ?? '';
		$baseurl = $data['baseurl'] ?? '';

		$result = [];

		if (in_array($network, ['', Protocol::DFRN])) {
			$result = self::dfrn($webfinger);
		}
		if ((!$result && ($network == '')) || ($network == Protocol::DIASPORA)) {
			$result = self::diaspora($webfinger);
		}
		if ((!$result && ($network == '')) || ($network == Protocol::OSTATUS)) {
			$result = self::ostatus($webfinger);
		}
		if (in_array($network, ['', Protocol::ZOT])) {
			$result = self::zot($webfinger, $result, $baseurl);
		}
		if ((!$result && ($network == '')) || ($network == Protocol::PUMPIO)) {
			$result = self::pumpio($webfinger, $addr);
		}
		if (empty($result['network']) && empty($ap_profile['network']) || ($network == Protocol::FEED)) {
			$result = self::feed($uri);
		} else {
			// We overwrite the detected nick with our try if the previois routines hadn't detected it.
			// Additionally it is overwritten when the nickname doesn't make sense (contains spaces).
			if ((empty($result['nick']) || (strstr($result['nick'], ' '))) && ($nick != '')) {
				$result['nick'] = $nick;
			}

			if (empty($result['addr']) && ($addr != '')) {
				$result['addr'] = $addr;
			}
		}

		$result = self::getSubscribeLink($result, $webfinger);

		if (empty($result['network'])) {
			$result['network'] = Protocol::PHANTOM;
		}

		if (empty($result['baseurl']) && !empty($baseurl)) {
			$result['baseurl'] = $baseurl;
		}

		if (empty($result['url'])) {
			$result['url'] = $uri;
		}

		Logger::info('Probing done', ['uri' => $uri, 'network' => $result['network']]);

		return $result;
	}

	/**
	 * Check for Zot contact
	 *
	 * @param array  $webfinger Webfinger data
	 * @param array  $data      previously probed data
	 * @param string $baseUrl   Base URL
	 *
	 * @return array Zot data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function zot(array $webfinger, array $data, string $baseurl): array
	{
		if (!empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (substr($alias, 0, 5) == 'acct:') {
					$data['addr'] = substr($alias, 5);
				}
			}
		}

		if (!empty($webfinger['subject']) && (substr($webfinger['subject'], 0, 5) == 'acct:')) {
			$data['addr'] = substr($webfinger['subject'], 5);
		}

		$zot_url = '';
		foreach ($webfinger['links'] as $link) {
			if (($link['rel'] == 'http://purl.org/zot/protocol') && !empty($link['href'])) {
				$zot_url = $link['href'];
			}
		}

		if (empty($zot_url) && !empty($data['addr']) && !empty($baseurl)) {
			$condition = ['nurl' => Strings::normaliseLink($baseurl), 'platform' => ['hubzilla']];
			if (!DBA::exists('gserver', $condition)) {
				return $data;
			}
			$zot_url = $baseurl . '/.well-known/zot-info?address=' . $data['addr'];
		}

		if (empty($zot_url)) {
			return $data;
		}

		$data = self::pollZot($zot_url, $data);

		if (!empty($data['url']) && !empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (!strstr($alias, '@') && Strings::normaliseLink($alias) != Strings::normaliseLink($data['url'])) {
					$data['alias'] = $alias;
				}
			}
		}

		return $data;
	}

	public static function pollZot(string $url, array $data): array
	{
		$curlResult = DI::httpClient()->get($url, HttpClientAccept::JSON);
		if ($curlResult->isTimeout()) {
			return $data;
		}
		$content = $curlResult->getBody();
		if (!$content) {
			return $data;
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			return $data;
		}

		if (empty($data['network'])) {
			if (!empty($json['protocols']) && in_array('zot', $json['protocols'])) {
				$data['network'] = Protocol::ZOT;
			} elseif (!isset($json['protocols'])) {
				$data['network'] = Protocol::ZOT;
			}
		}

		if (!empty($json['guid']) && empty($data['guid'])) {
			$data['guid'] = $json['guid'];
		}
		if (!empty($json['key']) && empty($data['pubkey'])) {
			$data['pubkey'] = $json['key'];
		}
		if (!empty($json['name'])) {
			$data['name'] = $json['name'];
		}
		if (!empty($json['photo'])) {
			$data['photo'] = $json['photo'];
			if (!empty($json['photo_updated'])) {
				$data['photo'] .= '?rev=' . urlencode($json['photo_updated']);
			}
		}
		if (!empty($json['address'])) {
			$data['addr'] = $json['address'];
		}
		if (!empty($json['url'])) {
			$data['url'] = $json['url'];
		}
		if (!empty($json['connections_url'])) {
			$data['poco'] = $json['connections_url'];
		}
		if (isset($json['searchable'])) {
			$data['hide'] = !$json['searchable'];
		}
		if (!empty($json['public_forum'])) {
			$data['community'] = $json['public_forum'];
			$data['account-type'] = User::PAGE_FLAGS_COMMUNITY;
		}

		if (!empty($json['profile'])) {
			$profile = $json['profile'];
			if (!empty($profile['description'])) {
				$data['about'] = $profile['description'];
			}
			if (!empty($profile['keywords'])) {
				$keywords = implode(', ', $profile['keywords']);
				if (!empty($keywords)) {
					$data['keywords'] = $keywords;
				}
			}

			$loc = [];
			if (!empty($profile['region'])) {
				$loc['region'] = $profile['region'];
			}
			if (!empty($profile['country'])) {
				$loc['country-name'] = $profile['country'];
			}
			$location = Profile::formatLocation($loc);
			if (!empty($location)) {
				$data['location'] = $location;
			}
		}

		return $data;
	}

	/**
	 * Perform a webfinger request.
	 *
	 * For details see RFC 7033: <https://tools.ietf.org/html/rfc7033>
	 *
	 * @param string $url  Address that should be probed
	 * @param string $type type
	 *
	 * @return array webfinger data
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function webfinger(string $url, string $type): array
	{
		$xrd_timeout = DI::config()->get('system', 'xrd_timeout', 20);

		$curlResult = DI::httpClient()->get($url, $type, [HttpClientOptions::TIMEOUT => $xrd_timeout]);
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}
		$data = $curlResult->getBody();

		$webfinger = json_decode($data, true);
		if (!empty($webfinger)) {
			if (!isset($webfinger['links'])) {
				Logger::info('No json webfinger links', ['url' => $url]);
				return [];
			}
			return $webfinger;
		}

		// If it is not JSON, maybe it is XML
		$xrd = XML::parseString($data, true);
		if (!is_object($xrd)) {
			Logger::info('No webfinger data retrievable', ['url' => $url]);
			return [];
		}

		$xrd_arr = XML::elementToArray($xrd);
		if (!isset($xrd_arr['xrd']['link'])) {
			Logger::info('No XML webfinger links', ['url' => $url]);
			return [];
		}

		$webfinger = [];

		if (!empty($xrd_arr['xrd']['subject'])) {
			$webfinger['subject'] = $xrd_arr['xrd']['subject'];
		}

		if (!empty($xrd_arr['xrd']['alias'])) {
			$webfinger['aliases'] = $xrd_arr['xrd']['alias'];
		}

		$webfinger['links'] = [];

		foreach ($xrd_arr['xrd']['link'] as $value => $data) {
			if (!empty($data['@attributes'])) {
				$attributes = $data['@attributes'];
			} elseif ($value == '@attributes') {
				$attributes = $data;
			} else {
				continue;
			}

			$webfinger['links'][] = $attributes;
		}
		return $webfinger;
	}

	/**
	 * Poll the Friendica specific noscrape page.
	 *
	 * "noscrape" is a faster alternative to fetch the data from the hcard.
	 * This functionality was originally created for the directory.
	 *
	 * @param string $noscrape_url Link to the noscrape page
	 * @param array  $data         The already fetched data
	 *
	 * @return array noscrape data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function pollNoscrape(string $noscrape_url, array $data): array
	{
		$curlResult = DI::httpClient()->get($noscrape_url, HttpClientAccept::JSON);
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return $data;
		}
		$content = $curlResult->getBody();
		if (!$content) {
			Logger::info('Empty body', ['url' => $noscrape_url]);
			return $data;
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			Logger::info('No json data', ['url' => $noscrape_url]);
			return $data;
		}

		if (!empty($json['fn'])) {
			$data['name'] = $json['fn'];
		}

		if (!empty($json['addr'])) {
			$data['addr'] = $json['addr'];
		}

		if (!empty($json['nick'])) {
			$data['nick'] = $json['nick'];
		}

		if (!empty($json['guid'])) {
			$data['guid'] = $json['guid'];
		}

		if (!empty($json['comm'])) {
			$data['community'] = $json['comm'];
		}

		if (!empty($json['tags'])) {
			$keywords = implode(', ', $json['tags']);
			if ($keywords != '') {
				$data['keywords'] = $keywords;
			}
		}

		$location = Profile::formatLocation($json);
		if ($location) {
			$data['location'] = $location;
		}

		if (!empty($json['about'])) {
			$data['about'] = $json['about'];
		}

		if (!empty($json['xmpp'])) {
			$data['xmpp'] = $json['xmpp'];
		}

		if (!empty($json['matrix'])) {
			$data['matrix'] = $json['matrix'];
		}

		if (!empty($json['key'])) {
			$data['pubkey'] = $json['key'];
		}

		if (!empty($json['photo'])) {
			$data['photo'] = $json['photo'];
		}

		if (!empty($json['dfrn-request'])) {
			$data['request'] = $json['dfrn-request'];
		}

		if (!empty($json['dfrn-confirm'])) {
			$data['confirm'] = $json['dfrn-confirm'];
		}

		if (!empty($json['dfrn-notify'])) {
			$data['notify'] = $json['dfrn-notify'];
		}

		if (!empty($json['dfrn-poll'])) {
			$data['poll'] = $json['dfrn-poll'];
		}

		if (isset($json['hide'])) {
			$data['hide'] = (bool)$json['hide'];
		} else {
			$data['hide'] = false;
		}

		return $data;
	}

	/**
	 * Check for valid DFRN data
	 *
	 * @param array $data DFRN data
	 *
	 * @return int Number of errors
	 */
	public static function validDfrn(array $data): int
	{
		$errors = 0;
		if (!isset($data['key'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-request'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-confirm'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-notify'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-poll'])) {
			$errors ++;
		}
		return $errors;
	}

	/**
	 * Fetch data from a DFRN profile page and via "noscrape"
	 *
	 * @param string $profile_link Link to the profile page
	 * @return array profile data
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function profile(string $profile_link): array
	{
		$data = [];

		Logger::info('Check profile', ['link' => $profile_link]);

		// Fetch data via noscrape - this is faster
		$noscrape_url = str_replace(['/hcard/', '/profile/'], '/noscrape/', $profile_link);
		$data = self::pollNoscrape($noscrape_url, $data);

		if (!isset($data['notify'])
			|| !isset($data['confirm'])
			|| !isset($data['request'])
			|| !isset($data['poll'])
			|| !isset($data['name'])
			|| !isset($data['photo'])
		) {
			$data = self::pollHcard($profile_link, $data, true);
		}

		$prof_data = [];

		if (empty($data['addr']) || empty($data['nick'])) {
			$probe_data = self::uri($profile_link);
			$data['addr'] = ($data['addr'] ?? '') ?: $probe_data['addr'];
			$data['nick'] = ($data['nick'] ?? '') ?: $probe_data['nick'];
		}

		$prof_data['addr']         = $data['addr'];
		$prof_data['nick']         = $data['nick'];
		$prof_data['dfrn-request'] = $data['request'] ?? null;
		$prof_data['dfrn-confirm'] = $data['confirm'] ?? null;
		$prof_data['dfrn-notify']  = $data['notify']  ?? null;
		$prof_data['dfrn-poll']    = $data['poll']    ?? null;
		$prof_data['photo']        = $data['photo']   ?? null;
		$prof_data['fn']           = $data['name']    ?? null;
		$prof_data['key']          = $data['pubkey']  ?? null;

		Logger::debug('Result', ['link' => $profile_link, 'data' => $prof_data]);

		return $prof_data;
	}

	/**
	 * Check for DFRN contact
	 *
	 * @param array $webfinger Webfinger data
	 * @return array DFRN data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function dfrn(array $webfinger): array
	{
		$hcard_url = '';
		$data = [];
		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger['links']) as $link) {
			if (($link['rel'] == ActivityNamespace::DFRN) && !empty($link['href'])) {
				$data['network'] = Protocol::DFRN;
			} elseif (($link['rel'] == ActivityNamespace::FEED) && !empty($link['href'])) {
				$data['poll'] = $link['href'];
			} elseif (($link['rel'] == 'http://webfinger.net/rel/profile-page') && (($link['type'] ?? '') == 'text/html') && !empty($link['href'])) {
				$data['url'] = $link['href'];
			} elseif (($link['rel'] == 'http://microformats.org/profile/hcard') && !empty($link['href'])) {
				$hcard_url = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::POCO) && !empty($link['href'])) {
				$data['poco'] = $link['href'];
			} elseif (($link['rel'] == 'http://webfinger.net/rel/avatar') && !empty($link['href'])) {
				$data['photo'] = $link['href'];
			} elseif (($link['rel'] == 'http://joindiaspora.com/seed_location') && !empty($link['href'])) {
				$data['baseurl'] = trim($link['href'], '/');
			} elseif (($link['rel'] == 'http://joindiaspora.com/guid') && !empty($link['href'])) {
				$data['guid'] = $link['href'];
			} elseif (($link['rel'] == 'diaspora-public-key') && !empty($link['href'])) {
				$data['pubkey'] = base64_decode($link['href']);

				if (strstr($data['pubkey'], 'RSA ')) {
					$data['pubkey'] = Crypto::rsaToPem($data['pubkey']);
				}
			}
		}

		if (!empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (empty($data['url']) && !strstr($alias, '@')) {
					$data['url'] = $alias;
				} elseif (!strstr($alias, '@') && Strings::normaliseLink($alias) != Strings::normaliseLink($data['url'])) {
					$data['alias'] = $alias;
				} elseif (substr($alias, 0, 5) == 'acct:') {
					$data['addr'] = substr($alias, 5);
				}
			}
		}

		if (!empty($webfinger['subject']) && (substr($webfinger['subject'], 0, 5) == 'acct:')) {
			$data['addr'] = substr($webfinger['subject'], 5);
		}

		if (!isset($data['network']) || ($hcard_url == '')) {
			return [];
		}

		// Fetch data via noscrape - this is faster
		$noscrape_url = str_replace('/hcard/', '/noscrape/', $hcard_url);
		$data = self::pollNoscrape($noscrape_url, $data);

		if (isset($data['notify'])
			&& isset($data['confirm'])
			&& isset($data['request'])
			&& isset($data['poll'])
			&& isset($data['name'])
			&& isset($data['photo'])
		) {
			return $data;
		}

		$data = self::pollHcard($hcard_url, $data, true);

		return $data;
	}

	/**
	 * Poll the hcard page (Diaspora and Friendica specific)
	 *
	 * @param string  $hcard_url Link to the hcard page
	 * @param array   $data      The already fetched data
	 * @param boolean $dfrn      Poll DFRN specific data
	 * @return array hcard data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function pollHcard(string $hcard_url, array $data, bool $dfrn = false): array
	{
		$curlResult = DI::httpClient()->get($hcard_url, HttpClientAccept::HTML);
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}
		$content = $curlResult->getBody();
		if (empty($content)) {
			return [];
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($content)) {
			return [];
		}

		$xpath = new DomXPath($doc);

		$vcards = $xpath->query("//div[contains(concat(' ', @class, ' '), ' vcard ')]");
		if (!is_object($vcards)) {
			return [];
		}

		if (!isset($data['baseurl'])) {
			$data['baseurl'] = '';
		}

		if ($vcards->length > 0) {
			$vcard = $vcards->item(0);

			// We have to discard the guid from the hcard in favour of the guid from lrdd
			// Reason: Hubzilla doesn't use the value "uid" in the hcard like Diaspora does.
			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' uid ')]", $vcard); // */
			if (($search->length > 0) && empty($data['guid'])) {
				$data['guid'] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' nickname ')]", $vcard); // */
			if ($search->length > 0) {
				$data['nick'] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' fn ')]", $vcard); // */
			if ($search->length > 0) {
				$data['name'] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' given_name ')]", $vcard); // */
			if ($search->length > 0) {
				$data["given_name"] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' family_name ')]", $vcard); // */
			if ($search->length > 0) {
				$data["family_name"] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' searchable ')]", $vcard); // */
			if ($search->length > 0) {
				$data['hide'] = (strtolower($search->item(0)->nodeValue) != 'true');
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' key ')]", $vcard); // */
			if ($search->length > 0) {
				$data['pubkey'] = $search->item(0)->nodeValue;
				if (strstr($data['pubkey'], 'RSA ')) {
					$data['pubkey'] = Crypto::rsaToPem($data['pubkey']);
				}
			}

			$search = $xpath->query("//*[@id='pod_location']", $vcard); // */
			if ($search->length > 0) {
				$data['baseurl'] = trim($search->item(0)->nodeValue, '/');
			}
		}

		$avatars = [];
		if (!empty($vcard)) {
			$photos = $xpath->query("//*[contains(concat(' ', @class, ' '), ' photo ') or contains(concat(' ', @class, ' '), ' avatar ')]", $vcard); // */
			foreach ($photos as $photo) {
				$attr = [];
				foreach ($photo->attributes as $attribute) {
					$attr[$attribute->name] = trim($attribute->value);
				}

				if (isset($attr['src']) && isset($attr['width'])) {
					$avatars[$attr['width']] = self::fixAvatar($attr['src'], $data['baseurl']);
				}

				// We don't have a width. So we just take everything that we got.
				// This is a Hubzilla workaround which doesn't send a width.
				if (!$avatars && !empty($attr['src'])) {
					$avatars[] = self::fixAvatar($attr['src'], $data['baseurl']);
				}
			}
		}

		if ($avatars) {
			ksort($avatars);
			$data['photo'] = array_pop($avatars);
			if ($avatars) {
				$data['photo_medium'] = array_pop($avatars);
			}

			if ($avatars) {
				$data['photo_small'] = array_pop($avatars);
			}
		}

		if ($dfrn) {
			// Poll DFRN specific data
			$search = $xpath->query("//link[contains(concat(' ', @rel), ' dfrn-')]");
			if ($search->length > 0) {
				foreach ($search as $link) {
					//$data['request'] = $search->item(0)->nodeValue;
					$attr = [];
					foreach ($link->attributes as $attribute) {
						$attr[$attribute->name] = trim($attribute->value);
					}

					$data[substr($attr['rel'], 5)] = $attr['href'];
				}
			}

			// Older Friendica versions had used the "uid" field differently than newer versions
			if (!empty($data['nick']) && !empty($data['guid']) && ($data['nick'] == $data['guid'])) {
				unset($data['guid']);
			}
		}

		return $data;
	}

	/**
	 * Check for Diaspora contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array Diaspora data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function diaspora(array $webfinger): array
	{
		$hcard_url = '';
		$data = [];

		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger['links']) as $link) {
			if (($link['rel'] == 'http://microformats.org/profile/hcard') && !empty($link['href'])) {
				$hcard_url = $link['href'];
			} elseif (($link['rel'] == 'http://joindiaspora.com/seed_location') && !empty($link['href'])) {
				$data['baseurl'] = trim($link['href'], '/');
			} elseif (($link['rel'] == 'http://joindiaspora.com/guid') && !empty($link['href'])) {
				$data['guid'] = $link['href'];
			} elseif (($link['rel'] == 'http://webfinger.net/rel/profile-page') && (($link['type'] ?? '') == 'text/html') && !empty($link['href'])) {
				$data['url'] = $link['href'];
			} elseif (($link['rel'] == 'http://webfinger.net/rel/profile-page') && empty($link['type']) && !empty($link['href'])) {
				$profile_url = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::FEED) && !empty($link['href'])) {
				$data['poll'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::POCO) && !empty($link['href'])) {
				$data['poco'] = $link['href'];
			} elseif (($link['rel'] == 'salmon') && !empty($link['href'])) {
				$data['notify'] = $link['href'];
			} elseif (($link['rel'] == 'diaspora-public-key') && !empty($link['href'])) {
				$data['pubkey'] = base64_decode($link['href']);

				if (strstr($data['pubkey'], 'RSA ')) {
					$data['pubkey'] = Crypto::rsaToPem($data['pubkey']);
				}
			}
		}

		if (empty($data['url']) && !empty($profile_url)) {
			$data['url'] = $profile_url;
		}

		if (empty($data['url']) || empty($hcard_url)) {
			return [];
		}

		if (!empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (Strings::normaliseLink($alias) != Strings::normaliseLink($data['url']) && ! strstr($alias, '@')) {
					$data['alias'] = $alias;
				} elseif (substr($alias, 0, 5) == 'acct:') {
					$data['addr'] = substr($alias, 5);
				}
			}
		}

		if (!empty($webfinger['subject']) && (substr($webfinger['subject'], 0, 5) == 'acct:')) {
			$data['addr'] = substr($webfinger['subject'], 5);
		}

		// Fetch further information from the hcard
		$data = self::pollHcard($hcard_url, $data);

		if (!$data) {
			return [];
		}

		if (!empty($data['url'])
			&& !empty($data['guid'])
			&& !empty($data['baseurl'])
			&& !empty($data['pubkey'])
			&& !empty($hcard_url)
		) {
			$data['network'] = Protocol::DIASPORA;
			$data['manually-approve'] = false;

			// The Diaspora handle must always be lowercase
			if (!empty($data['addr'])) {
				$data['addr'] = strtolower($data['addr']);
			}

			// We have to overwrite the detected value for "notify" since Hubzilla doesn't send it
			$data['notify'] = $data['baseurl'] . '/receive/users/' . $data['guid'];
			$data['batch']  = $data['baseurl'] . '/receive/public';
		} else {
			return [];
		}

		return $data;
	}

	/**
	 * Check for OStatus contact
	 *
	 * @param array $webfinger Webfinger data
	 * @param bool  $short     Short detection mode
	 *
	 * @return array|bool OStatus data or "false" on error or "true" on short mode
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function ostatus(array $webfinger, bool $short = false)
	{
		$data = [];

		if (!empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (strstr($alias, '@') && !strstr(Strings::normaliseLink($alias), 'http://')) {
					$data['addr'] = str_replace('acct:', '', $alias);
				}
			}
		}

		if (!empty($webfinger['subject']) && strstr($webfinger['subject'], '@')
			&& !strstr(Strings::normaliseLink($webfinger['subject']), 'http://')
		) {
			$data['addr'] = str_replace('acct:', '', $webfinger['subject']);
		}

		if (!empty($webfinger['links'])) {
			// The array is reversed to take into account the order of preference for same-rel links
			// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
			foreach (array_reverse($webfinger['links']) as $link) {
				if (($link['rel'] == 'http://webfinger.net/rel/profile-page')
					&& (($link['type'] ?? '') == 'text/html')
					&& ($link['href'] != '')
				) {
					$data['url'] = $data['alias'] = $link['href'];
				} elseif (($link['rel'] == 'salmon') && !empty($link['href'])) {
					$data['notify'] = $link['href'];
				} elseif (($link['rel'] == ActivityNamespace::FEED) && !empty($link['href'])) {
					$data['poll'] = $link['href'];
				} elseif (($link['rel'] == 'magic-public-key') && !empty($link['href'])) {
					$pubkey = $link['href'];

					if (substr($pubkey, 0, 5) === 'data:') {
						if (strstr($pubkey, ',')) {
							$pubkey = substr($pubkey, strpos($pubkey, ',') + 1);
						} else {
							$pubkey = substr($pubkey, 5);
						}
					} elseif (Strings::normaliseLink($pubkey) == 'http://') {
						$curlResult = DI::httpClient()->get($pubkey, HttpClientAccept::MAGIC_KEY);
						if ($curlResult->isTimeout()) {
							self::$isTimeout = true;
							return $short ? false : [];
						}
						Logger::debug('Fetched public key', ['Content-Type' => $curlResult->getHeader('Content-Type'), 'url' => $pubkey]);
						$pubkey = $curlResult->getBody();
					}

					try {
						$data['pubkey'] = Salmon::magicKeyToPem($pubkey);
					} catch (\Throwable $e) {

					}
				}
			}
		}

		if (isset($data['notify']) && isset($data['pubkey'])
			&& isset($data['poll'])
			&& isset($data['url'])
		) {
			$data['network'] = Protocol::OSTATUS;
			$data['manually-approve'] = false;
		} else {
			return $short ? false : [];
		}

		if ($short) {
			return true;
		}

		// Fetch all additional data from the feed
		$curlResult = DI::httpClient()->get($data['poll'], HttpClientAccept::FEED_XML);
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}
		$feed = $curlResult->getBody();
		$feed_data = Feed::import($feed);
		if (!$feed_data) {
			return [];
		}

		if (!empty($feed_data['header']['author-name'])) {
			$data['name'] = $feed_data['header']['author-name'];
		}
		if (!empty($feed_data['header']['author-nick'])) {
			$data['nick'] = $feed_data['header']['author-nick'];
		}
		if (!empty($feed_data['header']['author-avatar'])) {
			$data['photo'] = self::fixAvatar($feed_data['header']['author-avatar'], $data['url']);
		}
		if (!empty($feed_data['header']['author-id'])) {
			$data['alias'] = $feed_data['header']['author-id'];
		}
		if (!empty($feed_data['header']['author-location'])) {
			$data['location'] = $feed_data['header']['author-location'];
		}
		if (!empty($feed_data['header']['author-about'])) {
			$data['about'] = $feed_data['header']['author-about'];
		}
		// OStatus has serious issues when the the url doesn't fit (ssl vs. non ssl)
		// So we take the value that we just fetched, although the other one worked as well
		if (!empty($feed_data['header']['author-link'])) {
			$data['url'] = $feed_data['header']['author-link'];
		}

		if ($data['url'] == $data['alias']) {
			$data['alias'] = '';
		}

		/// @todo Fetch location and "about" from the feed as well
		return $data;
	}

	/**
	 * Fetch data from a pump.io profile page
	 *
	 * @param string $profile_link Link to the profile page
	 *
	 * @return array Profile data
	 */
	private static function pumpioProfileData(string $profile_link): array
	{
		$curlResult = DI::httpClient()->get($profile_link, HttpClientAccept::HTML);
		if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
			return [];
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($curlResult->getBody())) {
			return [];
		}

		$xpath = new DomXPath($doc);

		$data = [];
		$data['name'] = $xpath->query("//span[contains(@class, 'p-name')]")->item(0)->nodeValue;

		if ($data['name'] == '') {
			// This is ugly - but pump.io doesn't seem to know a better way for it
			$data['name'] = trim($xpath->query("//h1[@class='media-header']")->item(0)->nodeValue);
			$pos = strpos($data['name'], chr(10));
			if ($pos) {
				$data['name'] = trim(substr($data['name'], 0, $pos));
			}
		}

		$data['location'] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'p-locality')]");

		if ($data['location'] == '') {
			$data['location'] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'location')]");
		}

		$data['about'] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'p-note')]");

		if ($data['about'] == '') {
			$data['about'] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'summary')]");
		}

		$avatar = $xpath->query("//img[contains(@class, 'u-photo')]")->item(0);
		if (!$avatar) {
			$avatar = $xpath->query("//img[@class='img-rounded media-object']")->item(0);
		}
		if ($avatar) {
			foreach ($avatar->attributes as $attribute) {
				if ($attribute->name == 'src') {
					$data['photo'] = trim($attribute->value);
				}
			}
		}

		return $data;
	}

	/**
	 * Check for pump.io contact
	 *
	 * @param array  $webfinger Webfinger data
	 * @param string $addr
	 *
	 * @return array pump.io data
	 */
	private static function pumpio(array $webfinger, string $addr): array
	{
		$data = [];
		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger['links']) as $link) {
			if (($link['rel'] == 'http://webfinger.net/rel/profile-page')
				&& (($link['type'] ?? '') == 'text/html')
				&& ($link['href'] != '')
			) {
				$data['url'] = $link['href'];
			} elseif (($link['rel'] == 'activity-inbox') && ($link['href'] != '')) {
				$data['notify'] = $link['href'];
			} elseif (($link['rel'] == 'activity-outbox') && ($link['href'] != '')) {
				$data['poll'] = $link['href'];
			} elseif (($link['rel'] == 'dialback') && ($link['href'] != '')) {
				$data['dialback'] = $link['href'];
			}
		}
		if (isset($data['poll']) && isset($data['notify'])
			&& isset($data['dialback'])
			&& isset($data['url'])
		) {
			// by now we use these fields only for the network type detection
			// So we unset all data that isn't used at the moment
			unset($data['dialback']);

			$data['network'] = Protocol::PUMPIO;
		} else {
			return [];
		}

		$profile_data = self::pumpioProfileData($data['url']);

		if (!$profile_data) {
			return [];
		}

		$data = array_merge($data, $profile_data);

		if (($addr != '') && ($data['name'] != '')) {
			$name = trim(str_replace($addr, '', $data['name']));
			if ($name != '') {
				$data['name'] = $name;
			}
		}

		return $data;
	}

	/**
	 * Checks HTML page for RSS feed link
	 *
	 * @param string $url  Page link
	 * @param string $body Page body string
	 *
	 * @return string|false Feed link or false if body was invalid HTML document
	 */
	public static function getFeedLink(string $url, string $body)
	{
		if (empty($body)) {
			return '';
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($body)) {
			return false;
		}

		$xpath = new DOMXPath($doc);

		$feedUrl = $xpath->evaluate('string(/html/head/link[@type="application/rss+xml" and @rel="alternate"]/@href)');
		$feedUrl = $feedUrl ?: $xpath->evaluate('string(/html/head/link[@type="application/atom+xml" and @rel="alternate"]/@href)');

		$feedUrl = $feedUrl ? self::ensureAbsoluteLinkFromHTMLDoc($feedUrl, $url, $xpath) : '';

		return $feedUrl;
	}

	/**
	 * Return an absolute URL in the context of a HTML document retrieved from the provided URL.
	 *
	 * Loosely based on RFC 1808
	 *
	 * @see https://tools.ietf.org/html/rfc1808
	 *
	 * @param string   $href  The potential relative href found in the HTML document
	 * @param string   $base  The HTML document URL
	 * @param DOMXPath $xpath The HTML document XPath
	 *
	 * @return string Absolute URL
	 */
	private static function ensureAbsoluteLinkFromHTMLDoc(string $href, string $base, DOMXPath $xpath): string
	{
		if (filter_var($href, FILTER_VALIDATE_URL)) {
			return $href;
		}

		$base = $xpath->evaluate('string(/html/head/base/@href)') ?: $base;

		$baseParts = parse_url($base);
		if (empty($baseParts['host'])) {
			return $href;
		}

		// Naked domain case (scheme://basehost)
		$path = $baseParts['path'] ?? '/';

		// Remove the filename part of the path if it exists (/base/path/file)
		$path = implode('/', array_slice(explode('/', $path), 0, -1));

		$hrefParts = parse_url($href);

		if (!empty($hrefParts['path'])) {
			// Root path case (/path) including relative scheme case (//host/path)
			if ($hrefParts['path'] && $hrefParts['path'][0] == '/') {
				$path = $hrefParts['path'];
			} else {
				$path = $path . '/' . $hrefParts['path'];

				// Resolve arbitrary relative path
				// Lifted from https://www.php.net/manual/en/function.realpath.php#84012
				$parts = array_filter(explode('/', $path), 'strlen');
				$absolutes = [];
				foreach ($parts as $part) {
					if ('.' == $part) continue;
					if ('..' == $part) {
						array_pop($absolutes);
					} else {
						$absolutes[] = $part;
					}
				}

				$path = '/' . implode('/', $absolutes);
			}
		}

		// Relative scheme case (//host/path)
		$baseParts['host'] = $hrefParts['host'] ?? $baseParts['host'];
		$baseParts['path'] = $path;
		unset($baseParts['query']);
		unset($baseParts['fragment']);

		return Network::unparseURL($baseParts);
	}

	/**
	 * Check for feed contact
	 *
	 * @param string  $url   Profile link
	 * @param boolean $probe Do a probe if the page contains a feed link
	 *
	 * @return array feed data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function feed(string $url, bool $probe = true): array
	{
		$curlResult = DI::httpClient()->get($url, HttpClientAccept::FEED_XML);
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}
		$feed = $curlResult->getBody();
		$feed_data = Feed::import($feed);

		if (!$feed_data) {
			if (!$probe) {
				return [];
			}

			$feed_url = self::getFeedLink($url, $feed);

			if (!$feed_url) {
				return [];
			}

			return self::feed($feed_url, false);
		}

		if (!empty($feed_data['header']['author-name'])) {
			$data['name'] = $feed_data['header']['author-name'];
		}

		if (!empty($feed_data['header']['author-nick'])) {
			$data['nick'] = $feed_data['header']['author-nick'];
		}

		if (!empty($feed_data['header']['author-avatar'])) {
			$data['photo'] = $feed_data['header']['author-avatar'];
		}

		if (!empty($feed_data['header']['author-id'])) {
			$data['alias'] = $feed_data['header']['author-id'];
		}

		$data['url'] = $url;
		$data['poll'] = $url;

		$data['network'] = Protocol::FEED;

		return $data;
	}

	/**
	 * Check for mail contact
	 *
	 * @param string  $uri Profile link
	 * @param integer $uid User ID
	 *
	 * @return array mail data
	 * @throws \Exception
	 */
	private static function mail(string $uri, int $uid): array
	{
		if (!Network::isEmailDomainValid($uri)) {
			return [];
		}

		if ($uid == 0) {
			return [];
		}

		$user = DBA::selectFirst('user', ['prvkey'], ['uid' => $uid]);

		$condition = ["`uid` = ? AND `server` != ''", $uid];
		$fields = ['pass', 'user', 'server', 'port', 'ssltype', 'mailbox'];
		$mailacct = DBA::selectFirst('mailacct', $fields, $condition);

		if (!DBA::isResult($user) || !DBA::isResult($mailacct)) {
			return [];
		}

		$mailbox = Email::constructMailboxName($mailacct);
		$password = '';
		openssl_private_decrypt(hex2bin($mailacct['pass']), $password, $user['prvkey']);
		$mbox = Email::connect($mailbox, $mailacct['user'], $password);
		if (!$mbox) {
			return [];
		}

		$msgs = Email::poll($mbox, $uri);
		Logger::info('Messages found', ['uri' => $uri, 'count' => count($msgs)]);

		if (!count($msgs)) {
			return [];
		}

		$phost = substr($uri, strpos($uri, '@') + 1);

		$data = [
			'addr'    => $uri,
			'network' => Protocol::MAIL,
			'name'    => substr($uri, 0, strpos($uri, '@')),
			'photo'   => Network::lookupAvatarByEmail($uri),
			'url'     => 'mailto:' . $uri,
			'notify'  => 'smtp ' . Strings::getRandomHex(),
			'poll'    => 'email ' . Strings::getRandomHex(),
		];

		$data['nick']    = $data['name'];

		$x = Email::messageMeta($mbox, $msgs[0]);

		if (stristr($x[0]->from, $uri)) {
			$adr = imap_rfc822_parse_adrlist($x[0]->from, '');
		} elseif (stristr($x[0]->to, $uri)) {
			$adr = imap_rfc822_parse_adrlist($x[0]->to, '');
		}

		if (isset($adr)) {
			foreach ($adr as $feadr) {
				if ((strcasecmp($feadr->mailbox, $data['name']) == 0)
					&&(strcasecmp($feadr->host, $phost) == 0)
					&& (strlen($feadr->personal))
				) {
					$personal = imap_mime_header_decode($feadr->personal);
					$data['name'] = '';
					foreach ($personal as $perspart) {
						if ($perspart->charset != 'default') {
							$data['name'] .= iconv($perspart->charset, 'UTF-8//IGNORE', $perspart->text);
						} else {
							$data['name'] .= $perspart->text;
						}
					}
				}
			}
		}

		if (!empty($mbox)) {
			imap_close($mbox);
		}

		return $data;
	}

	/**
	 * Mix two paths together to possibly fix missing parts
	 *
	 * @param string $avatar Path to the avatar
	 * @param string $base   Another path that is hopefully complete
	 *
	 * @return string fixed avatar path
	 * @throws \Exception
	 */
	public static function fixAvatar(string $avatar, string $base): string
	{
		$base_parts = parse_url($base);

		// Remove all parts that could create a problem
		unset($base_parts['path']);
		unset($base_parts['query']);
		unset($base_parts['fragment']);

		$avatar_parts = parse_url($avatar);

		// Now we mix them
		$parts = array_merge($base_parts, $avatar_parts);

		// And put them together again
		$scheme   = isset($parts['scheme'])   ? $parts['scheme'] . '://' : '';
		$host     = isset($parts['host'])     ? $parts['host']           : '';
		$port     = isset($parts['port'])     ? ':' . $parts['port']     : '';
		$path     = isset($parts['path'])     ? $parts['path']           : '';
		$query    = isset($parts['query'])    ? '?' . $parts['query']    : '';
		$fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

		$fixed = $scheme.$host.$port.$path.$query.$fragment;

		Logger::debug('Avatar fixed', ['base' => $base, 'avatar' => $avatar, 'fixed' => $fixed]);

		return $fixed;
	}

	/**
	 * Fetch the last date that the contact had posted something (publically)
	 *
	 * @param array $data  probing result
	 *
	 * @return string last activity
	 */
	public static function getLastUpdate(array $data): string
	{
		$uid = User::getIdForURL($data['url']);
		if (!empty($uid)) {
			$contact = Contact::selectFirst(['url', 'last-item'], ['self' => true, 'uid' => $uid]);
			if (!empty($contact['last-item'])) {
				return $contact['last-item'];
			}
		}

		if ($lastUpdate = self::updateFromNoScrape($data)) {
			return $lastUpdate;
		}

		if (!empty($data['outbox'])) {
			return self::updateFromOutbox($data['outbox'], $data);
		} elseif (!empty($data['poll']) && ($data['network'] == Protocol::ACTIVITYPUB)) {
			return self::updateFromOutbox($data['poll'], $data);
		} elseif (!empty($data['poll'])) {
			return self::updateFromFeed($data);
		}

		return '';
	}

	/**
	 * Fetch the last activity date from the "noscrape" endpoint
	 *
	 * @param array $data Probing result
	 *
	 * @return string last activity or true if update was successful or the server was unreachable
	 */
	private static function updateFromNoScrape(array $data): string
	{
		if (empty($data['baseurl'])) {
			return '';
		}

		// Check the 'noscrape' endpoint when it is a Friendica server
		$gserver = DBA::selectFirst('gserver', ['noscrape'], ["`nurl` = ? AND `noscrape` != ''",
			Strings::normaliseLink($data['baseurl'])]);
		if (!DBA::isResult($gserver)) {
			return '';
		}

		$curlResult = DI::httpClient()->get($gserver['noscrape'] . '/' . $data['nick'], HttpClientAccept::JSON);

		if ($curlResult->isSuccess() && !empty($curlResult->getBody())) {
			$noscrape = json_decode($curlResult->getBody(), true);
			if (!empty($noscrape) && !empty($noscrape['updated'])) {
				return DateTimeFormat::utc($noscrape['updated'], DateTimeFormat::MYSQL);
			}
		}

		return '';
	}

	/**
	 * Fetch the last activity date from an ActivityPub Outbox
	 *
	 * @param string $feed
	 * @param array  $data Probing result
	 *
	 * @return string last activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function updateFromOutbox(string $feed, array $data): string
	{
		$outbox = ActivityPub::fetchContent($feed);
		if (empty($outbox)) {
			return '';
		}

		if (!empty($outbox['orderedItems'])) {
			$items = $outbox['orderedItems'];
		} elseif (!empty($outbox['first']['orderedItems'])) {
			$items = $outbox['first']['orderedItems'];
		} elseif (!empty($outbox['first']['href']) && ($outbox['first']['href'] != $feed)) {
			return self::updateFromOutbox($outbox['first']['href'], $data);
		} elseif (!empty($outbox['first'])) {
			if (is_string($outbox['first']) && ($outbox['first'] != $feed)) {
				return self::updateFromOutbox($outbox['first'], $data);
			} else {
				Logger::warning('Unexpected data', ['outbox' => $outbox]);
			}
			return '';
		} else {
			$items = [];
		}

		$last_updated = '';
		foreach ($items as $activity) {
			if (!empty($activity['published'])) {
				$published =  DateTimeFormat::utc($activity['published']);
			} elseif (!empty($activity['object']['published'])) {
				$published =  DateTimeFormat::utc($activity['object']['published']);
			} else {
				continue;
			}

			if ($last_updated < $published) {
				$last_updated = $published;
			}
		}

		if (!empty($last_updated)) {
			return $last_updated;
		}

		return '';
	}

	/**
	 * Fetch the last activity date from an XML feed
	 *
	 * @param array $data Probing result
	 * @return string last activity
	 */
	private static function updateFromFeed(array $data): string
	{
		// Search for the newest entry in the feed
		$curlResult = DI::httpClient()->get($data['poll'], HttpClientAccept::ATOM_XML);
		if (!$curlResult->isSuccess() || !$curlResult->getBody()) {
			return '';
		}

		$doc = new DOMDocument();
		@$doc->loadXML($curlResult->getBody());

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

		$entries = $xpath->query('/atom:feed/atom:entry');

		$last_updated = '';

		foreach ($entries as $entry) {
			$published_item = $xpath->query('atom:published/text()', $entry)->item(0);
			$updated_item   = $xpath->query('atom:updated/text()'  , $entry)->item(0);
			$published      = !empty($published_item->nodeValue) ? DateTimeFormat::utc($published_item->nodeValue) : null;
			$updated        = !empty($updated_item->nodeValue) ? DateTimeFormat::utc($updated_item->nodeValue) : null;

			if (empty($published) || empty($updated)) {
				Logger::notice('Invalid entry for XPath.', ['entry' => $entry, 'url' => $data['url']]);
				continue;
			}

			if ($last_updated < $published) {
				$last_updated = $published;
			}

			if ($last_updated < $updated) {
				$last_updated = $updated;
			}
		}

		if (!empty($last_updated)) {
			return $last_updated;
		}

		return '';
	}

	/**
	 * Probe data from local profiles without network traffic
	 *
	 * @param string $url
	 *
	 * @return array probed data
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 */
	private static function localProbe(string $url): array
	{
		try {
			$uid = User::getIdForURL($url);
			if (!$uid) {
				throw new HTTPException\NotFoundException('User not found.');
			}

			$owner     = User::getOwnerDataById($uid);
			$approfile = ActivityPub\Transmitter::getProfile($uid);

			if (empty($owner['gsid'])) {
				$owner['gsid'] = GServer::getID($approfile['generator']['url']);
			}

			$data = [
				'name'             => $owner['name'], 'nick' => $owner['nick'], 'guid' => $approfile['diaspora:guid'] ?? '',
				'url'              => $owner['url'], 'addr' => $owner['addr'], 'alias' => $owner['alias'],
				'photo'            => User::getAvatarUrl($owner),
				'header'           => $owner['header'] ? Contact::getHeaderUrlForId($owner['id'], $owner['updated']) : '',
				'account-type'     => $owner['contact-type'], 'community' => ($owner['contact-type'] == User::ACCOUNT_TYPE_COMMUNITY),
				'keywords'         => $owner['keywords'], 'location' => $owner['location'], 'about' => $owner['about'],
				'xmpp'             => $owner['xmpp'], 'matrix' => $owner['matrix'],
				'hide'             => !$owner['net-publish'], 'batch' => '', 'notify' => $owner['notify'],
				'poll'             => $owner['poll'], 'request' => $owner['request'], 'confirm' => $owner['confirm'],
				'subscribe'        => $approfile['generator']['url'] . '/contact/follow?url={uri}', 'poco' => $owner['poco'],
				'following'        => $approfile['following'], 'followers' => $approfile['followers'],
				'inbox'            => $approfile['inbox'], 'outbox' => $approfile['outbox'],
				'sharedinbox'      => $approfile['endpoints']['sharedInbox'], 'network' => Protocol::DFRN,
				'pubkey'           => $owner['upubkey'], 'baseurl' => $approfile['generator']['url'], 'gsid' => $owner['gsid'],
				'manually-approve' => in_array($owner['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP])
			];
		} catch (Exception $e) {
			// Default values for non existing targets
			$data = [
				'name' => $url, 'nick' => $url, 'url' => $url, 'network' => Protocol::PHANTOM,
				'photo' => DI::baseUrl() . Contact::DEFAULT_AVATAR_PHOTO
			];
		}

		return self::rearrangeData($data);
	}
}
