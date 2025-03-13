<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network;

use DOMDocument;
use DomXPath;
use Exception;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ATProtocol;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Email;
use Friendica\Protocol\Feed;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
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
	 * @var boolean Whether a timeout has occurred
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
		$fields = [
			'name', 'given_name', 'family_name', 'nick', 'guid', 'url', 'addr', 'alias',
			'photo', 'photo_medium', 'photo_small', 'header',
			'account-type', 'community', 'keywords', 'location', 'about', 'xmpp', 'matrix',
			'hide', 'batch', 'notify', 'poll', 'request', 'confirm', 'subscribe', 'poco',
			'openwebauth', 'following', 'followers', 'inbox', 'outbox', 'sharedinbox',
			'priority', 'network', 'pubkey', 'manually-approve', 'baseurl', 'gsid'
		];

		$numeric_fields = ['gsid', 'account-type'];
		$boolean_fields = ['hide', 'manually-approve'];

		if (!empty($data['photo'])) {
			$data['photo'] = Network::addBasePath($data['photo'], $data['url']);

			if (!Network::isValidHttpUrl($data['photo'])) {
				DI::logger()->warning('Invalid URL for photo', ['url' => $data['url'], 'photo' => $data['photo']]);
				unset($data['photo']);
			}
		}

		$newdata = [];
		foreach ($fields as $field) {
			if (isset($data[$field])) {
				if (in_array($field, $numeric_fields)) {
					$newdata[$field] = (int)$data[$field];
				} elseif (in_array($field, $boolean_fields)) {
					$newdata[$field] = (bool)$data[$field];
				} else {
					$newdata[$field] = trim($data[$field]);
				}
			} elseif (!in_array($field, $numeric_fields) && !in_array($field, $boolean_fields)) {
				$newdata[$field] = '';
			} else {
				$newdata[$field] = null;
			}
		}

		$newdata['networks'] = [];
		foreach ([Protocol::DIASPORA] as $network) {
			if (!empty($data['networks'][$network])) {
				$data['networks'][$network]['subscribe'] = $newdata['subscribe'] ?? '';
				if (empty($data['networks'][$network]['baseurl'])) {
					$data['networks'][$network]['baseurl'] = $newdata['baseurl'] ?? '';
				} else {
					$newdata['baseurl'] = $data['networks'][$network]['baseurl'];
				}
				if (!empty($newdata['baseurl'])) {
					$newdata['gsid'] = $data['networks'][$network]['gsid'] = GServer::getRealID($newdata['baseurl']);
				} else {
					$newdata['gsid'] = $data['networks'][$network]['gsid'] = null;
				}

				$newdata['networks'][$network] = self::rearrangeData($data['networks'][$network]);
				unset($newdata['networks'][$network]['networks']);
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
		$own_host = DI::baseUrl()->getHost();

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
			$url     = 'http://' . $host . self::HOST_META;
		} else {
			$ssl_url = $host . self::HOST_META;
			$url     = '';
		}

		$xrd_timeout = DI::config()->get('system', 'xrd_timeout', 20);

		DI::logger()->info('Probing', ['host' => $host, 'ssl_url' => $ssl_url, 'url' => $url]);
		$xrd = null;

		try {
			$curlResult = DI::httpClient()->get($ssl_url, HttpClientAccept::XRD_XML, [HttpClientOptions::TIMEOUT => $xrd_timeout, HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return [];
		}

		$ssl_connection_error = ($curlResult->getErrorNumber() == CURLE_COULDNT_CONNECT) || ($curlResult->getReturnCode() == 0);

		$host_url = $host;

		if ($curlResult->isSuccess()) {
			$xml = $curlResult->getBodyString();
			$xrd = XML::parseString($xml, true);
			if (!empty($url)) {
				$host_url = 'https://' . $host;
			}
		} elseif ($curlResult->isTimeout()) {
			DI::logger()->info('Probing timeout', ['url' => $ssl_url]);
			self::$isTimeout = true;
			return [];
		}

		if ($ssl_connection_error && !is_object($xrd) && !empty($url)) {
			try {
				$curlResult = DI::httpClient()->get($url, HttpClientAccept::XRD_XML, [HttpClientOptions::TIMEOUT => $xrd_timeout, HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
				return [];
			}
			$connection_error = ($curlResult->getErrorNumber() == CURLE_COULDNT_CONNECT) || ($curlResult->getReturnCode() == 0);
			if ($curlResult->isTimeout()) {
				DI::logger()->info('Probing timeout', ['url' => $url]);
				self::$isTimeout = true;
				return [];
			} elseif ($connection_error && $ssl_connection_error) {
				self::$isTimeout = true;
				return [];
			}

			$xml      = $curlResult->getBodyString();
			$xrd      = XML::parseString($xml, true);
			$host_url = 'http://' . $host;
		}
		if (!is_object($xrd)) {
			DI::logger()->info('No xrd object found', ['host' => $host]);
			return [];
		}

		$links = XML::elementToArray($xrd);
		if (!isset($links['xrd']['link'])) {
			DI::logger()->info('No xrd data found', ['host' => $host]);
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
			DI::logger()->info('Domain is blocked', ['url' => $host]);
			return [];
		}

		self::$baseurl = $host_url;

		DI::logger()->info('Probing successful', ['host' => $host]);

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
			DI::logger()->info('No webfinger links found', ['uri' => $uri]);
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
						'rel'  => 'alias',
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
				$networks = $data['networks'] ?? [];
				unset($data['networks']);
				if (!empty($data['network'])) {
					$networks[$data['network']] = $data;
					$ap_profile['guid']         = $ap_profile['guid']  ?? $data['guid'] ?? null;
					$ap_profile['about']        = $ap_profile['about'] ?? $data['about'] ?? null;
					$ap_profile['keywords']     = $data['keywords']    ?? null;
					$ap_profile['location']     = $data['location']    ?? null;
					$ap_profile['poco']         = $data['poco']        ?? null;
					$ap_profile['openwebauth']  = $data['openwebauth'] ?? null;
				}
				$data             = $ap_profile;
				$data['networks'] = $networks;
			} elseif (!empty($ap_profile)) {
				$ap_profile['batch'] = '';
				$data                = array_merge($ap_profile, $data);
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

		if (empty($data['network'])) {
			$data['network'] = Protocol::PHANTOM;
		}

		$baseurl = parse_url($data['url'], PHP_URL_SCHEME) . '://' . parse_url($data['url'], PHP_URL_HOST);
		if (empty($data['baseurl']) && ($data['network'] == Protocol::ACTIVITYPUB) && (rtrim($data['url'], '/') == $baseurl)) {
			$data['baseurl'] = $baseurl;
		}

		if (!empty($data['baseurl']) && empty($data['gsid'])) {
			$data['gsid'] = GServer::getRealID($data['baseurl']);
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
		try {
			$curlResult = DI::httpClient()->get($url, HttpClientAccept::HTML, [HttpClientOptions::CONTENT_LENGTH => 1000000, HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return false;
		}
		if (!$curlResult->isSuccess()) {
			return false;
		}

		// If it isn't a HTML file then exit
		if (($curlResult->getContentType() != '') && !strstr(strtolower($curlResult->getContentType()), 'html')) {
			return false;
		}

		$body = $curlResult->getBodyString();
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
			} elseif (!empty($link['href']) && ($link['rel'] === ActivityNamespace::OPENWEBAUTH) && ($link['type'] === 'application/x-zot+json')) {
				$result['openwebauth'] = $link['href'];
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
	public static function getWebfingerArray(string $uri): array
	{
		$parts = parse_url($uri);
		$lrdd  = [];

		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			$host = $parts['host'];
			if (!empty($parts['port'])) {
				$host .= ':' . $parts['port'];
			}

			$baseurl = $parts['scheme'] . '://' . $host;

			$nick = '';
			$addr = '';

			$path_parts = [];

			if (array_key_exists('path', $parts) && trim(strval($parts['path']), '/') !== '') {
				$path_parts = explode('/', trim($parts['path'], '/'));

				$nick = ltrim(end($path_parts), '@');
				$addr = $nick . '@' . $host;
			}

			$webfinger = self::getWebfinger($parts['scheme'] . '://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
			if (empty($webfinger) && !is_null($webfinger)) {
				$lrdd = self::hostMeta($host);
			}

			if (empty($webfinger) && empty($lrdd)) {
				while (empty($lrdd) && empty($webfinger) && (count($path_parts) > 1)) {
					$host .= '/' . array_shift($path_parts);
					$baseurl = $parts['scheme'] . '://' . $host;

					if (!empty($nick)) {
						$addr = $nick . '@' . $host;
					}

					$webfinger = self::getWebfinger($parts['scheme'] . '://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
					if (empty($webfinger) && !is_null($webfinger)) {
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

			if (is_null($webfinger)) {
				$webfinger = self::getWebfinger('http://' . $host . self::WEBFINGER, HttpClientAccept::JRD_JSON, $uri, $addr);
				if (self::$isTimeout || is_null($webfinger)) {
					return [];
				}
				$baseurl = 'http://' . $host;
			} else {
				$baseurl = 'https://' . $host;
			}

			if (empty($webfinger)) {
				$lrdd = self::hostMeta($host);
				if (self::$isTimeout) {
					return [];
				}
				$baseurl = self::$baseurl;
			}
		} else {
			DI::logger()->info('URI was not detectable', ['uri' => $uri]);
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
	private static function getWebfinger(string $template, string $type, string $uri, string $addr): ?array
	{
		if (Network::isUrlBlocked($template)) {
			DI::logger()->info('Domain is blocked', ['url' => $template]);
			return null;
		}

		$detected = '';

		// First try the address because this is the primary purpose of webfinger
		if ($addr !== '') {
			$detected  = $addr;
			$path      = str_replace('{uri}', urlencode('acct:' . $addr), $template);
			$webfinger = self::webfinger($path, $type);
			if (is_null($webfinger)) {
				return null;
			}
		}

		// Then try the URI
		if (empty($webfinger) && $uri != $addr) {
			$detected  = $uri;
			$path      = str_replace('{uri}', urlencode($uri), $template);
			$webfinger = self::webfinger($path, $type);
			if (is_null($webfinger)) {
				return null;
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
		if (empty($parts['scheme']) && empty($parts['host']) && (empty($parts['path']) || strpos($parts['path'], '@') === false)) {
			DI::logger()->info('URI was not detectable, probe for AT Protocol now', ['uri' => $uri]);
			return self::atProtocol($uri);
		}

		// If the URI starts with "mailto:" then jump directly to the mail detection
		if (strpos($uri, 'mailto:') !== false) {
			$uri = str_replace('mailto:', '', $uri);
			return self::mail($uri, $uid);
		}

		if ($network == Protocol::MAIL) {
			return self::mail($uri, $uid);
		}

		DI::logger()->info('Probing start', ['uri' => $uri]);

		if (!empty($ap_profile['addr']) && ($ap_profile['addr'] != $uri)) {
			$data = self::getWebfingerArray($ap_profile['addr']);
		}

		if (empty($data)) {
			$data = self::getWebfingerArray($uri);
		}

		if (empty($data)) {
			$data = self::atProtocol($uri);
			if (!empty($data)) {
				return $data;
			}
			if (!empty($parts['scheme'])) {
				return self::feed($uri);
			} elseif (!empty($uid)) {
				return self::mail($uri, $uid);
			} else {
				return [];
			}
		}

		$webfinger = $data['webfinger'];
		$nick      = $data['nick']    ?? '';
		$addr      = $data['addr']    ?? '';
		$baseurl   = $data['baseurl'] ?? '';

		$result = [];

		if (in_array($network, ['', Protocol::DFRN])) {
			$result = self::dfrn($webfinger);
		}
		if ((!$result && ($network == '')) || ($network == Protocol::DIASPORA)) {
			$result = self::diaspora($webfinger);
		} else {
			$result['networks'][Protocol::DIASPORA] = self::diaspora($webfinger);
		}
		if ((!$result && ($network == '')) || ($network == Protocol::OSTATUS)) {
			$result = self::ostatus($webfinger);
		}
		if (in_array($network, ['', Protocol::ZOT])) {
			$result = self::zot($webfinger, $result);
		}
		if (empty($result['network']) && empty($ap_profile['network']) || ($network == Protocol::FEED)) {
			$result = self::feed($uri);
		} else {
			// We overwrite the detected nick with our try if the previous routines hadn't detected it.
			// Additionally, it is overwritten when the nickname doesn't make sense (contains spaces).
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

		DI::logger()->info('Probing done', ['uri' => $uri, 'network' => $result['network']]);

		return $result;
	}

	/**
	 * Check for Zot contact
	 *
	 * @param array  $webfinger Webfinger data
	 * @param array  $data      previously probed data
	 *
	 * @return array Zot data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function zot(array $webfinger, array $data): array
	{
		$zot_url = '';

		foreach ($webfinger['links'] as $link) {
			if (($link['rel'] == 'http://purl.org/zot/protocol/6.0') && !empty($link['href'])) {
				$zot_url = $link['href'];
			}
		}

		if ($zot_url === '') {
			return $data;
		}

		foreach ($webfinger['aliases'] as $alias) {
			if (substr($alias, 0, 5) == 'acct:') {
				$data['addr'] = substr($alias, 5);
			}
		}

		if (!empty($webfinger['subject']) && (substr($webfinger['subject'], 0, 5) == 'acct:')) {
			$data['addr'] = substr($webfinger['subject'], 5);
		}

		if (!empty($webfinger['properties'])) {
			if (!empty($webfinger['properties']['http://webfinger.net/ns/name'])) {
				$data['name'] = $webfinger['properties']['http://webfinger.net/ns/name'];
			}
			if (!empty($webfinger['properties']['http://xmlns.com/foaf/0.1/name'])) {
				$data['name'] = $webfinger['properties']['http://xmlns.com/foaf/0.1/name'];
			}
			if (!empty($webfinger['properties']['https://w3id.org/security/v1#publicKeyPem'])) {
				$data['pubkey'] = $webfinger['properties']['https://w3id.org/security/v1#publicKeyPem'];
			}

			if (empty($data['network']) && !empty($webfinger['properties']['http://purl.org/zot/federation'])) {
				$networks = explode(',', $webfinger['properties']['http://purl.org/zot/federation']);
				if (in_array('zot6', $networks)) {
					$data['network'] = Protocol::ZOT;
				}
			}
		}

		foreach ($webfinger['links'] as $link) {
			if (($link['rel'] == ActivityNamespace::WEBFINGERAVATAR) && !empty($link['href'])) {
				$data['photo'] = $link['href'];
			} elseif (($link['rel'] == 'http://openid.net/specs/connect/1.0/issuer') && !empty($link['href'])) {
				$data['baseurl'] = trim($link['href'], '/');
			} elseif (($link['rel'] == 'http://webfinger.net/rel/blog') && !empty($link['href'])) {
				$data['url'] = $link['href'];
			}
		}

		$data = self::pollZot($zot_url, $data);

		if (!empty($data['url']) && !empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (Network::isValidHttpUrl($alias) && !Strings::compareLink($alias, $data['url'])) {
					$data['alias'] = $alias;
				}
			}
		}

		return $data;
	}

	private static function pollZot(string $url, array $data): array
	{
		try {
			$curlResult = DI::httpClient()->get($url, 'application/x-zot+json', [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return $data;
		}
		if ($curlResult->isTimeout()) {
			return $data;
		}
		$content = $curlResult->getBodyString();
		if (!$content) {
			return $data;
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			return $data;
		}

		if (empty($data['network'])) {
			if (!empty($json['protocols']) && in_array('zot6', $json['protocols'])) {
				$data['network'] = Protocol::ZOT;
			}
		}

		if (!empty($json['public_key'])) {
			$data['pubkey'] = $json['public_key'];
		}
		if (!empty($json['name'])) {
			$data['name'] = $json['name'];
		}
		if (!empty($json['username'])) {
			$data['nick'] = $json['username'];
		}
		if (!empty($json['photo']) && !empty($json['photo']['url'])) {
			$data['photo'] = $json['photo']['url'];
		}
		if (!empty($json['locations'])) {
			foreach ($json['locations'] as $location) {
				if ($location['deleted'] || (parse_url($url, PHP_URL_HOST) != $location['host'])) {
					continue;
				}
				if (!empty($location['address'])) {
					$data['addr'] = $location['address'];
				}
				if (!empty($location['id_url'])) {
					$data['url'] = $location['id_url'];
				}
				if (!empty($location['callback'])) {
					$data['confirm'] = $location['callback'];
				}
			}
		}
		if (!empty($json['primary_location']) && !empty($json['primary_location']['connections_url'])) {
			$data['poco'] = $json['primary_location']['connections_url'];
		}
		if (isset($json['searchable'])) {
			$data['hide'] = !$json['searchable'];
		}
		if (!empty($json['public_forum'])) {
			$data['community']    = $json['public_forum'];
			$data['account-type'] = User::ACCOUNT_TYPE_COMMUNITY;
		} elseif (($json['channel_type'] ?? '') == 'normal') {
			$data['account-type'] = User::ACCOUNT_TYPE_PERSON;
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
	private static function webfinger(string $url, string $type): ?array
	{
		try {
			$curlResult = DI::httpClient()->get(
				$url,
				$type,
				[HttpClientOptions::TIMEOUT => DI::config()->get('system', 'xrd_timeout', 20), HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]
			);
		} catch (\Throwable $e) {
			DI::logger()->notice($e->getMessage(), ['url' => $url, 'type' => $type, 'class' => get_class($e)]);
			return null;
		}

		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return null;
		}
		$data = $curlResult->getBodyString();

		$webfinger = json_decode($data, true);
		if (!empty($webfinger)) {
			if (!isset($webfinger['links'])) {
				DI::logger()->info('No json webfinger links', ['url' => $url]);
				return [];
			}
			return $webfinger;
		}

		// If it is not JSON, maybe it is XML
		$xrd = XML::parseString($data, true);
		if (!is_object($xrd)) {
			DI::logger()->info('No webfinger data retrievable', ['url' => $url]);
			return [];
		}

		$xrd_arr = XML::elementToArray($xrd);
		if (!isset($xrd_arr['xrd']['link'])) {
			DI::logger()->info('No XML webfinger links', ['url' => $url]);
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
		try {
			$curlResult = DI::httpClient()->get($noscrape_url, HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return $data;
		}
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return $data;
		}
		$content = $curlResult->getBodyString();
		if (!$content) {
			DI::logger()->info('Empty body', ['url' => $noscrape_url]);
			return $data;
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			DI::logger()->info('No json data', ['url' => $noscrape_url]);
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
	 * Check for DFRN contact
	 *
	 * @param array $webfinger Webfinger data
	 * @return array DFRN data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function dfrn(array $webfinger): array
	{
		$hcard_url = '';
		$data      = [];
		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger['links']) as $link) {
			if (($link['rel'] == ActivityNamespace::DFRN) && !empty($link['href'])) {
				$data['network'] = Protocol::DFRN;
			} elseif (($link['rel'] == ActivityNamespace::FEED) && !empty($link['href'])) {
				$data['poll'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::WEBFINGERPROFILE) && (($link['type'] ?? '') == 'text/html') && !empty($link['href'])) {
				$data['url'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::HCARD) && !empty($link['href'])) {
				$hcard_url = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::POCO) && !empty($link['href'])) {
				$data['poco'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::WEBFINGERAVATAR) && !empty($link['href'])) {
				$data['photo'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::DIASPORA_SEED) && !empty($link['href'])) {
				$data['baseurl'] = trim($link['href'], '/');
			} elseif (($link['rel'] == ActivityNamespace::DIASPORA_GUID) && !empty($link['href'])) {
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
				if (empty($data['url']) && Network::isValidHttpUrl($alias)) {
					$data['url'] = $alias;
				} elseif (Network::isValidHttpUrl($alias) && !Strings::compareLink($alias, $data['url'])) {
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
		return self::pollNoscrape($noscrape_url, $data);
	}

	/**
	 * Poll the hcard page (Diaspora and Friendica specific)
	 *
	 * @param string  $hcard_url Link to the hcard page
	 * @param array   $data      The already fetched data
	 * @return array hcard data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function pollHcard(string $hcard_url, array $data): array
	{
		try {
			$curlResult = DI::httpClient()->get($hcard_url, HttpClientAccept::HTML, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return [];
		}
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}
		$content = $curlResult->getBodyString();
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
		$data      = [];

		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger['links']) as $link) {
			if (($link['rel'] == ActivityNamespace::HCARD) && !empty($link['href'])) {
				$hcard_url = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::DIASPORA_SEED) && !empty($link['href'])) {
				$data['baseurl'] = trim($link['href'], '/');
			} elseif (($link['rel'] == ActivityNamespace::DIASPORA_GUID) && !empty($link['href'])) {
				$data['guid'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::WEBFINGERPROFILE) && (($link['type'] ?? '') == 'text/html') && !empty($link['href'])) {
				$data['url'] = $link['href'];
			} elseif (($link['rel'] == ActivityNamespace::WEBFINGERPROFILE) && empty($link['type']) && !empty($link['href'])) {
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
				if (Network::isValidHttpUrl($alias) && !Strings::compareLink($alias, $data['url'])) {
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

		if (
			!empty($data['url'])
			&& !empty($data['guid'])
			&& !empty($data['baseurl'])
			&& !empty($data['pubkey'])
			&& $hcard_url !== ''
		) {
			$data['network']          = Protocol::DIASPORA;
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
				if (strstr($alias, '@') && !Network::isValidHttpUrl($alias)) {
					$data['addr'] = str_replace('acct:', '', $alias);
				}
			}
		}

		if (
			!empty($webfinger['subject']) && strstr($webfinger['subject'], '@')
			&& !Network::isValidHttpUrl($webfinger['subject'])
		) {
			$data['addr'] = str_replace('acct:', '', $webfinger['subject']);
		}

		if (!empty($webfinger['links'])) {
			// The array is reversed to take into account the order of preference for same-rel links
			// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
			foreach (array_reverse($webfinger['links']) as $link) {
				if (($link['rel'] == ActivityNamespace::WEBFINGERPROFILE)
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
						$curlResult = DI::httpClient()->get($pubkey, HttpClientAccept::MAGIC_KEY, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
						if ($curlResult->isTimeout()) {
							self::$isTimeout = true;
							return $short ? false : [];
						}
						DI::logger()->debug('Fetched public key', ['Content-Type' => $curlResult->getHeader('Content-Type'), 'url' => $pubkey]);
						$pubkey = $curlResult->getBodyString();
					}

					try {
						$data['pubkey'] = Salmon::magicKeyToPem($pubkey);
					} catch (\Throwable $e) {
					}
				}
			}
		}

		if (
			isset($data['notify']) && isset($data['pubkey'])
			&& isset($data['poll'])
			&& isset($data['url'])
		) {
			$data['network']          = Protocol::OSTATUS;
			$data['manually-approve'] = false;
		} else {
			return $short ? false : [];
		}

		if ($short) {
			return true;
		}

		// Fetch all additional data from the feed
		try {
			$curlResult = DI::httpClient()->get($data['poll'], HttpClientAccept::FEED_XML, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return [];
		}
		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}
		$feed      = $curlResult->getBodyString();
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
		// OStatus has serious issues when the url doesn't fit (ssl vs. non ssl)
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
				$parts     = array_filter(explode('/', $path), 'strlen');
				$absolutes = [];
				foreach ($parts as $part) {
					if ('.' == $part) {
						continue;
					}
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

		return (string)Uri::fromParts((array)(array)$baseParts);
	}

	/**
	 * Check for AT Protocol (Bluesky)
	 *
	 * @param string $uri Profile link
	 * @return array Profile data or empty array
	 */
	private static function atProtocol(string $uri): array
	{
		if (parse_url($uri, PHP_URL_SCHEME) == 'did') {
			$did = $uri;
		} elseif (parse_url($uri, PHP_URL_PATH) == $uri && strpos($uri, '@') === false) {
			$did = DI::atProtocol()->getDid($uri);
			if (empty($did)) {
				return [];
			}
		} elseif (Network::isValidHttpUrl($uri)) {
			$did = DI::atProtocol()->getDidByProfile($uri);
			if (empty($did)) {
				return [];
			}
		} else {
			return [];
		}

		$profile = DI::atProtocol()->XRPCGet('app.bsky.actor.getProfile', ['actor' => $did]);
		if (empty($profile) || empty($profile->did)) {
			return [];
		}

		$nick = $profile->handle      ?? $profile->did;
		$name = $profile->displayName ?? $nick;

		$data = [
			'network' => Protocol::BLUESKY,
			'url'     => $profile->did,
			'alias'   => ATProtocol::WEB . '/profile/' . $nick,
			'name'    => $name ?: $nick,
			'nick'    => $nick,
			'addr'    => $nick,
			'poll'    => ATProtocol::WEB . '/profile/' . $profile->did . '/rss',
			'photo'   => $profile->avatar ?? '',
		];

		if (!empty($profile->description)) {
			$data['about'] = HTML::toBBCode($profile->description);
		}

		if (!empty($profile->banner)) {
			$data['header'] = $profile->banner;
		}

		$directory = DI::atProtocol()->get(ATProtocol::DIRECTORY . '/' . $profile->did);
		if (!empty($directory)) {
			foreach ($directory->service as $service) {
				if (($service->id == '#atproto_pds') && ($service->type == 'AtprotoPersonalDataServer') && !empty($service->serviceEndpoint)) {
					$data['baseurl'] = $service->serviceEndpoint;
				}
			}

			foreach ($directory->verificationMethod as $method) {
				if (!empty($method->publicKeyMultibase)) {
					$data['pubkey'] = $method->publicKeyMultibase;
				}
			}
		}

		return $data;
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
		try {
			$curlResult = DI::httpClient()->get($url, HttpClientAccept::FEED_XML, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $e) {
			DI::logger()->info('Error requesting feed URL', ['url' => $url, 'exception' => $e]);
			return [];
		}

		if ($curlResult->isTimeout()) {
			self::$isTimeout = true;
			return [];
		}

		$feed = $curlResult->getBodyString();
		if (strpos($curlResult->getContentType(), 'xml') !== false) {
			$feed_data = Feed::import($feed);
		}

		if (empty($feed_data)) {
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

		$data['url']  = $url;
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
		$fields    = ['pass', 'user', 'server', 'port', 'ssltype', 'mailbox'];
		$mailacct  = DBA::selectFirst('mailacct', $fields, $condition);

		if (!DBA::isResult($user) || !DBA::isResult($mailacct)) {
			return [];
		}

		$mailbox  = Email::constructMailboxName($mailacct);
		$password = '';
		openssl_private_decrypt(hex2bin($mailacct['pass']), $password, $user['prvkey']);
		$mbox = Email::connect($mailbox, $mailacct['user'], $password);
		if ($mbox === false) {
			return [];
		}

		$msgs = Email::poll($mbox, $uri);
		DI::logger()->info('Messages found', ['uri' => $uri, 'count' => count($msgs)]);

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

		$data['nick'] = $data['name'];

		$x = Email::messageMeta($mbox, $msgs[0]);

		if (stristr($x[0]->from, $uri)) {
			$adr = imap_rfc822_parse_adrlist($x[0]->from, '');
		} elseif (stristr($x[0]->to, $uri)) {
			$adr = imap_rfc822_parse_adrlist($x[0]->to, '');
		}

		if (isset($adr)) {
			foreach ($adr as $feadr) {
				if ((strcasecmp($feadr->mailbox, $data['name']) == 0)
					&& (strcasecmp($feadr->host, $phost) == 0)
					&& !empty($feadr->personal)
				) {
					$personal     = imap_mime_header_decode($feadr->personal);
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

		if ($mbox !== false) {
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

		$fixed = $scheme . $host . $port . $path . $query . $fragment;

		DI::logger()->debug('Avatar fixed', ['base' => $base, 'avatar' => $avatar, 'fixed' => $fixed]);

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
		$gserver = DBA::selectFirst('gserver', ['noscrape'], [
			"`nurl` = ? AND `noscrape` != ''",
			Strings::normaliseLink($data['baseurl'])
		]);
		if (!DBA::isResult($gserver)) {
			return '';
		}

		try {
			$curlResult = DI::httpClient()->get($gserver['noscrape'] . '/' . $data['nick'], HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return '';
		}

		if ($curlResult->isSuccess() && !empty($curlResult->getBodyString())) {
			$noscrape = json_decode($curlResult->getBodyString(), true);
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
		$outbox = HTTPSignature::fetch($feed);
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
				DI::logger()->warning('Unexpected data', ['outbox' => $outbox]);
			}
			return '';
		} else {
			$items = [];
		}

		$last_updated = '';
		foreach ($items as $activity) {
			if (!empty($activity['published'])) {
				$published = DateTimeFormat::utc($activity['published']);
			} elseif (!empty($activity['object']['published'])) {
				$published = DateTimeFormat::utc($activity['object']['published']);
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
		try {
			$curlResult = DI::httpClient()->get($data['poll'], HttpClientAccept::ATOM_XML, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return '';
		}
		if (!$curlResult->isSuccess() || !$curlResult->getBodyString()) {
			return '';
		}

		$doc = new DOMDocument();
		@$doc->loadXML($curlResult->getBodyString());

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

		$entries = $xpath->query('/atom:feed/atom:entry');

		$last_updated = '';

		foreach ($entries as $entry) {
			$published_item = $xpath->query('atom:published/text()', $entry)->item(0);
			$updated_item   = $xpath->query('atom:updated/text()', $entry)->item(0);
			$published      = !empty($published_item->nodeValue) ? DateTimeFormat::utc($published_item->nodeValue) : null;
			$updated        = !empty($updated_item->nodeValue) ? DateTimeFormat::utc($updated_item->nodeValue) : null;

			if (empty($published) || empty($updated)) {
				DI::logger()->notice('Invalid entry for XPath.', ['entry' => $entry, 'url' => $data['url']]);
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

			$split_name = Diaspora::splitName($owner['name']);

			if (empty($owner['gsid'])) {
				$owner['gsid'] = GServer::getRealID($approfile['generator']['url']);
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
				'poll'             => $owner['poll'],
				'subscribe'        => $approfile['generator']['url'] . '/contact/follow?url={uri}', 'poco' => $owner['poco'],
				'openwebauth'      => $approfile['generator']['url'] . '/owa',
				'following'        => $approfile['following'], 'followers' => $approfile['followers'],
				'inbox'            => $approfile['inbox'], 'outbox' => $approfile['outbox'],
				'sharedinbox'      => $approfile['endpoints']['sharedInbox'], 'network' => Protocol::DFRN,
				'pubkey'           => $owner['upubkey'], 'baseurl' => $approfile['generator']['url'], 'gsid' => $owner['gsid'],
				'manually-approve' => in_array($owner['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP, User::PAGE_FLAGS_COMM_MAN]),
				'networks'         => [
					Protocol::DIASPORA => [
						'name'         => $owner['name'],
						'given_name'   => $split_name['first'],
						'family_name'  => $split_name['last'],
						'nick'         => $owner['nick'],
						'guid'         => $approfile['diaspora:guid'],
						'url'          => $owner['url'],
						'addr'         => $owner['addr'],
						'alias'        => $owner['alias'],
						'photo'        => $owner['photo'],
						'photo_medium' => $owner['thumb'],
						'photo_small'  => $owner['micro'],
						'batch'        => $approfile['generator']['url'] . '/receive/public',
						'notify'       => $owner['notify'],
						'poll'         => $owner['poll'],
						'poco'         => $owner['poco'],
						'network'      => Protocol::DIASPORA,
						'pubkey'       => $owner['upubkey'],
					]
				]
			];
		} catch (Exception $e) {
			// Default values for nonexistent targets
			$data = [
				'name'  => $url, 'nick' => $url, 'url' => $url, 'network' => Protocol::PHANTOM,
				'photo' => DI::baseUrl() . Contact::DEFAULT_AVATAR_PHOTO
			];
		}

		return self::rearrangeData($data);
	}
}
