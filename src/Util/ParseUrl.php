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

namespace Friendica\Util;

use DOMDocument;
use DOMXPath;
use Friendica\Content\OEmbed;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;

/**
 * Get information about a given URL
 *
 * Class with methods for extracting certain content from an url
 */
class ParseUrl
{
	const DEFAULT_EXPIRATION_FAILURE = 'now + 1 day';
	const DEFAULT_EXPIRATION_SUCCESS = 'now + 3 months';

	/**
	 * Maximum number of characters for the description
	 */
	const MAX_DESC_COUNT = 250;

	/**
	 * Minimum number of characters for the description
	 */
	const MIN_DESC_COUNT = 100;

	/**
	 * Fetch the content type of the given url
	 * @param string $url    URL of the page
	 * @param string $accept content-type to accept
	 * @param int    $timeout
	 * @return array content type
	 */
	public static function getContentType(string $url, string $accept = HttpClientAccept::DEFAULT, int $timeout = 0): array
	{
		if (!empty($timeout)) {
			$options = [HttpClientOptions::TIMEOUT => $timeout];
		} else {
			$options = [];
		}

		$curlResult = DI::httpClient()->head($url, array_merge([HttpClientOptions::ACCEPT_CONTENT => $accept], $options));

		// Workaround for systems that can't handle a HEAD request. Don't retry on timeouts.
		if (!$curlResult->isSuccess() && ($curlResult->getReturnCode() >= 400) && !in_array($curlResult->getReturnCode(), [408, 504])) {
			$curlResult = DI::httpClient()->get($url, $accept, array_merge([HttpClientOptions::CONTENT_LENGTH => 1000000], $options));
		}

		if (!$curlResult->isSuccess()) {
			Logger::debug('Got HTTP Error', ['http error' => $curlResult->getReturnCode(), 'url' => $url]);
			return [];
		}

		$contenttype =  $curlResult->getHeader('Content-Type')[0] ?? '';
		if (empty($contenttype)) {
			return ['application', 'octet-stream'];
		}

		return explode('/', current(explode(';', $contenttype)));
	}

	/**
	 * Search for chached embeddable data of an url otherwise fetch it
	 *
	 * @param string $url         The url of the page which should be scraped
	 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
	 *                            to avoid endless loops
	 *
	 * @return array which contains needed data for embedding
	 *    string 'url'      => The url of the parsed page
	 *    string 'type'     => Content type
	 *    string 'title'    => (optional) The title of the content
	 *    string 'text'     => (optional) The description for the content
	 *    string 'image'    => (optional) A preview image of the content
	 *    array  'images'   => (optional) Array of preview pictures
	 *    string 'keywords' => (optional) The tags which belong to the content
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @see   ParseUrl::getSiteinfo() for more information about scraping
	 * embeddable content
	 */
	public static function getSiteinfoCached(string $url, bool $do_oembed = true): array
	{
		if (empty($url)) {
			return [
				'url' => '',
				'type' => 'error',
			];
		}

		$urlHash = hash('sha256', $url);

		$parsed_url = DBA::selectFirst('parsed_url', ['content'],
			['url_hash' => $urlHash, 'oembed' => $do_oembed]
		);
		if (!empty($parsed_url['content'])) {
			$data = unserialize($parsed_url['content']);
			return $data;
		}

		$data = self::getSiteinfo($url, $do_oembed);

		$expires = $data['expires'];

		unset($data['expires']);

		DI::dba()->insert(
			'parsed_url',
			[
				'url_hash' => $urlHash,
				'oembed'   => $do_oembed,
				'url'      => $url,
				'content'  => serialize($data),
				'created'  => DateTimeFormat::utcNow(),
				'expires'  => $expires,
			],
			Database::INSERT_UPDATE
		);

		return $data;
	}

	/**
	 * Parse a page for embeddable content information
	 *
	 * This method parses to url for meta data which can be used to embed
	 * the content. If available it prioritizes Open Graph meta tags.
	 * If this is not available it uses the twitter cards meta tags.
	 * As fallback it uses standard html elements with meta informations
	 * like \<title\>Awesome Title\</title\> or
	 * \<meta name="description" content="An awesome description"\>
	 *
	 * @param string $url         The url of the page which should be scraped
	 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
	 *                            to avoid endless loops
	 * @param int    $count       Internal counter to avoid endless loops
	 *
	 * @return array which contains needed data for embedding
	 *    string 'url'      => The url of the parsed page
	 *    string 'type'     => Content type (error, link, photo, image, audio, video)
	 *    string 'title'    => (optional) The title of the content
	 *    string 'text'     => (optional) The description for the content
	 *    string 'image'    => (optional) A preview image of the content
	 *    array  'images'   => (optional) Array of preview pictures
	 *    string 'keywords' => (optional) The tags which belong to the content
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  https://developers.google.com/+/plugins/snippet/
	 * @verbatim
	 * <meta itemprop="name" content="Awesome title">
	 * <meta itemprop="description" content="An awesome description">
	 * <meta itemprop="image" content="http://maple.libertreeproject.org/images/tree-icon.png">
	 *
	 * <body itemscope itemtype="http://schema.org/Product">
	 *   <h1 itemprop="name">Shiny Trinket</h1>
	 *   <img itemprop="image" src="{image-url}" />
	 *   <p itemprop="description">Shiny trinkets are shiny.</p>
	 * </body>
	 * @endverbatim
	 */
	public static function getSiteinfo(string $url, bool $do_oembed = true, int $count = 1): array
	{
		if (empty($url)) {
			return [
				'url' => '',
				'type' => 'error',
			];
		}

		// Check if the URL does contain a scheme
		$scheme = parse_url($url, PHP_URL_SCHEME);

		if ($scheme == '') {
			$url = 'http://' . ltrim($url, '/');
		}

		$url = trim($url, "'\"");

		$url = Network::stripTrackingQueryParams($url);

		$siteinfo = [
			'url' => $url,
			'type' => 'link',
			'expires' => DateTimeFormat::utc(self::DEFAULT_EXPIRATION_FAILURE),
		];

		if ($count > 10) {
			Logger::warning('Endless loop detected', ['url' => $url]);
			return $siteinfo;
		}

		$type = self::getContentType($url);
		Logger::info('Got content-type', ['content-type' => $type, 'url' => $url]);
		if (!empty($type) && in_array($type[0], ['image', 'video', 'audio'])) {
			$siteinfo['type'] = $type[0];
			return $siteinfo;
		}

		if ((count($type) >= 2) && (($type[0] != 'text') || ($type[1] != 'html'))) {
			Logger::info('Unparseable content-type, quitting here, ', ['content-type' => $type, 'url' => $url]);
			return $siteinfo;
		}

		$curlResult = DI::httpClient()->get($url, HttpClientAccept::HTML, [HttpClientOptions::CONTENT_LENGTH => 1000000]);
		if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
			Logger::info('Empty body or error when fetching', ['url' => $url, 'success' => $curlResult->isSuccess(), 'code' => $curlResult->getReturnCode()]);
			return $siteinfo;
		}

		$siteinfo['expires'] = DateTimeFormat::utc(self::DEFAULT_EXPIRATION_SUCCESS);

		if ($cacheControlHeader = $curlResult->getHeader('Cache-Control')[0] ?? '') {
			if (preg_match('/max-age=([0-9]+)/i', $cacheControlHeader, $matches)) {
				$maxAge = max(86400, (int)array_pop($matches));
				$siteinfo['expires'] = DateTimeFormat::utc("now + $maxAge seconds");
			}
		}

		$body = $curlResult->getBody();

		if ($do_oembed) {
			$oembed_data = OEmbed::fetchURL($url, false, false);

			if (!empty($oembed_data->type)) {
				if (!in_array($oembed_data->type, ['error', 'rich', 'image', 'video', 'audio', ''])) {
					$siteinfo['type'] = $oembed_data->type;
				}

				// See https://github.com/friendica/friendica/pull/5763#discussion_r217913178
				if ($siteinfo['type'] != 'photo') {
					if (!empty($oembed_data->title)) {
						$siteinfo['title'] = trim($oembed_data->title);
					}
					if (!empty($oembed_data->description)) {
						$siteinfo['text'] = trim($oembed_data->description);
					}
					if (!empty($oembed_data->author_name)) {
						$siteinfo['author_name'] = trim($oembed_data->author_name);
					}
					if (!empty($oembed_data->author_url)) {
						$siteinfo['author_url'] = trim($oembed_data->author_url);
					}
					if (!empty($oembed_data->provider_name)) {
						$siteinfo['publisher_name'] = trim($oembed_data->provider_name);
					}
					if (!empty($oembed_data->provider_url)) {
						$siteinfo['publisher_url'] = trim($oembed_data->provider_url);
					}
					if (!empty($oembed_data->thumbnail_url)) {
						$siteinfo['image'] = $oembed_data->thumbnail_url;
					}
				}
			}
		}

		$charset = '';
		// Look for a charset, first in headers
		// Expected form: Content-Type: text/html; charset=ISO-8859-4
		if (preg_match('/charset=([a-z0-9-_.\/]+)/i', $curlResult->getContentType(), $matches)) {
			$charset = trim(trim(trim(array_pop($matches)), ';,'));
		} else {
			// Then in body that gets precedence
			// Expected forms:
			// - <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			// - <meta charset="utf-8">
			// - <meta charset=utf-8>
			// - <meta charSet="utf-8">
			// We escape <style> and <script> tags since they can contain irrelevant charset information
			// (see https://github.com/friendica/friendica/issues/9251#issuecomment-698636806)
			Strings::performWithEscapedBlocks($body, '#<(?:style|script).*?</(?:style|script)>#ism', function ($body) use (&$charset) {
				if (preg_match('/charset=["\']?([a-z0-9-_.\/]+)/i', $body, $matches)) {
					$charset = trim(trim(trim(array_pop($matches)), ';,'));
				}
			});
		}

		$siteinfo['charset'] = $charset;

		if ($charset && strtoupper($charset) != 'UTF-8') {
			// See https://github.com/friendica/friendica/issues/5470#issuecomment-418351211
			$charset = str_ireplace('latin-1', 'latin1', $charset);

			Logger::info('detected charset', ['charset' => $charset]);
			$body = iconv($charset, 'UTF-8//TRANSLIT', $body);
		}

		$body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');

		if (empty($body)) {
			return $siteinfo;
		}

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		XML::deleteNode($doc, 'style');
		XML::deleteNode($doc, 'option');
		XML::deleteNode($doc, 'h1');
		XML::deleteNode($doc, 'h2');
		XML::deleteNode($doc, 'h3');
		XML::deleteNode($doc, 'h4');
		XML::deleteNode($doc, 'h5');
		XML::deleteNode($doc, 'h6');
		XML::deleteNode($doc, 'ol');
		XML::deleteNode($doc, 'ul');

		$xpath = new DOMXPath($doc);

		$list = $xpath->query('//meta[@content]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (@$meta_tag['http-equiv'] == 'refresh') {
				$path = $meta_tag['content'];
				$pathinfo = explode(';', $path);
				$content = '';
				foreach ($pathinfo as $value) {
					if (substr(strtolower($value), 0, 4) == 'url=') {
						$content = substr($value, 4);
					}
				}
				if ($content != '') {
					$siteinfo = self::getSiteinfo($content, $do_oembed, ++$count);
					return $siteinfo;
				}
			}
		}

		$list = $xpath->query('//title');
		if ($list->length > 0) {
			$siteinfo['title'] = trim($list->item(0)->nodeValue);
		}

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

			$meta_tag['content'] = trim(html_entity_decode($meta_tag['content'], ENT_QUOTES, 'UTF-8'));

			switch (strtolower($meta_tag['name'])) {
				case 'fulltitle':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'thumbnail':
					$siteinfo['image'] = $meta_tag['content'];
					break;
				case 'twitter:image':
					$siteinfo['image'] = $meta_tag['content'];
					break;
				case 'twitter:image:src':
					$siteinfo['image'] = $meta_tag['content'];
					break;
				case 'twitter:description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'twitter:title':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'twitter:player':
					$siteinfo['player']['embed'] = trim($meta_tag['content']);
					break;
				case 'twitter:player:stream':
					$siteinfo['player']['stream'] = trim($meta_tag['content']);
					break;
				case 'twitter:player:width':
					$siteinfo['player']['width'] = intval($meta_tag['content']);
					break;
				case 'twitter:player:height':
					$siteinfo['player']['height'] = intval($meta_tag['content']);
					break;
				case 'dc.title':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'dc.description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'dc.creator':
					$siteinfo['publisher_name'] = trim($meta_tag['content']);
					break;
				case 'keywords':
					$keywords = explode(',', $meta_tag['content']);
					break;
				case 'news_keywords':
					$keywords = explode(',', $meta_tag['content']);
					break;
			}
		}

		if (isset($keywords)) {
			$siteinfo['keywords'] = [];
			foreach ($keywords as $keyword) {
				if (!in_array(trim($keyword), $siteinfo['keywords'])) {
					$siteinfo['keywords'][] = trim($keyword);
				}
			}
		}

		$list = $xpath->query('//meta[@property]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (!empty($meta_tag['content'])) {
				$meta_tag['content'] = trim(html_entity_decode($meta_tag['content'], ENT_QUOTES, 'UTF-8'));

				switch (strtolower($meta_tag['property'])) {
					case 'og:image':
						$siteinfo['image'] = $meta_tag['content'];
						break;
					case 'og:image:url':
						$siteinfo['image'] = $meta_tag['content'];
						break;
					case 'og:image:secure_url':
						$siteinfo['image'] = $meta_tag['content'];
						break;
					case 'og:title':
						$siteinfo['title'] = trim($meta_tag['content']);
						break;
					case 'og:description':
						$siteinfo['text'] = trim($meta_tag['content']);
						break;
					case 'og:site_name':
						$siteinfo['publisher_name'] = trim($meta_tag['content']);
						break;
					case 'og:locale':
						$siteinfo['language'] = trim($meta_tag['content']);
						break;
					case 'og:type':
						$siteinfo['pagetype'] = trim($meta_tag['content']);
						break;
					case 'twitter:description':
						$siteinfo['text'] = trim($meta_tag['content']);
						break;
					case 'twitter:title':
						$siteinfo['title'] = trim($meta_tag['content']);
						break;
					case 'twitter:image':
						$siteinfo['image'] = $meta_tag['content'];
						break;
				}
			}
		}

		$list = $xpath->query("//script[@type='application/ld+json']");
		foreach ($list as $node) {
			if (!empty($node->nodeValue)) {
				if ($jsonld = json_decode($node->nodeValue, true)) {
					$siteinfo = self::parseParts($siteinfo, $jsonld);
				}
			}
		}

		if (!empty($siteinfo['player']['stream'])) {
			// Only add player data to media arrays if there is no duplicate
			$content_urls = array_merge(array_column($siteinfo['audio'] ?? [], 'content'), array_column($siteinfo['video'] ?? [], 'content'));
			if (!in_array($siteinfo['player']['stream'], $content_urls)) {
				$contenttype = self::getContentType($siteinfo['player']['stream']);
				if (!empty($contenttype[0]) && in_array($contenttype[0], ['audio', 'video'])) {
					$media = ['content' => $siteinfo['player']['stream']];

					if (!empty($siteinfo['player']['embed'])) {
						$media['embed'] = $siteinfo['player']['embed'];
					}

					$siteinfo[$contenttype[0]][] = $media;
				}
			}
		}

		if (!empty($siteinfo['image'])) {
			$siteinfo['images'] = $siteinfo['images'] ?? [];
			array_unshift($siteinfo['images'], ['url' => $siteinfo['image']]);
			unset($siteinfo['image']);
		}

		$siteinfo = self::checkMedia($url, $siteinfo);

		if (!empty($siteinfo['text']) && mb_strlen($siteinfo['text']) > self::MAX_DESC_COUNT) {
			$siteinfo['text'] = mb_substr($siteinfo['text'], 0, self::MAX_DESC_COUNT) . '…';
			$pos = mb_strrpos($siteinfo['text'], '.');
			if ($pos > self::MIN_DESC_COUNT) {
				$siteinfo['text'] = mb_substr($siteinfo['text'], 0, $pos + 1);
			}
		}

		Logger::info('Siteinfo fetched', ['url' => $url, 'siteinfo' => $siteinfo]);

		Hook::callAll('getsiteinfo', $siteinfo);

		ksort($siteinfo);

		return $siteinfo;
	}

	/**
	 * Check the attached media elements.
	 * Fix existing data and add missing data.
	 *
	 * @param string $page_url
	 * @param array $siteinfo
	 * @return array
	 */
	private static function checkMedia(string $page_url, array $siteinfo) : array
	{
		if (!empty($siteinfo['images'])) {
			array_walk($siteinfo['images'], function (&$image) use ($page_url) {
				/*
				 * According to the specifications someone could place a picture
				 * URL into the content field as well. But this doesn't seem to
				 * happen in the wild, so we don't cover it here.
				 */
				if (!empty($image['url'])) {
					$image['url'] = self::completeUrl($image['url'], $page_url);
					$photodata = Images::getInfoFromURLCached($image['url']);
					if (($photodata) && ($photodata[0] > 50) && ($photodata[1] > 50)) {
						$image['src'] = $image['url'];
						$image['width'] = $photodata[0];
						$image['height'] = $photodata[1];
						$image['contenttype'] = $photodata['mime'];
						$image['blurhash'] = $photodata['blurhash'] ?? null;
						unset($image['url']);
						ksort($image);
					} else {
						$image = [];
					}
				} else {
					$image = [];
				}
			});

			$siteinfo['images'] = array_values(array_filter($siteinfo['images']));
		}

		foreach (['audio', 'video'] as $element) {
			if (!empty($siteinfo[$element])) {
				array_walk($siteinfo[$element], function (&$media) use ($page_url, &$siteinfo) {
					$url = '';
					$embed = '';
					$content = '';
					$contenttype = '';
					foreach (['embed', 'content', 'url'] as $field) {
						if (!empty($media[$field])) {
							$media[$field] = self::completeUrl($media[$field], $page_url);
							$type = self::getContentType($media[$field]);
							if (($type[0] ?? '') == 'text') {
								if ($field == 'embed') {
									$embed = $media[$field];
								} else {
									$url = $media[$field];
								}
							} elseif (!empty($type[0])) {
								$content = $media[$field];
								$contenttype = implode('/', $type);
							}
						}
						unset($media[$field]);
					}

					foreach (['image', 'preview'] as $field) {
						if (!empty($media[$field])) {
							$media[$field] = self::completeUrl($media[$field], $page_url);
						}
					}

					if (!empty($url)) {
						$media['url'] = $url;
					}
					if (!empty($embed)) {
						$media['embed'] = $embed;
						if (empty($siteinfo['player']['embed'])) {
							$siteinfo['player']['embed'] = $embed;
						}
					}
					if (!empty($content)) {
						$media['src'] = $content;
					}
					if (!empty($contenttype)) {
						$media['contenttype'] = $contenttype;
					}
					if (empty($url) && empty($content) && empty($embed)) {
						$media = [];
					}
					ksort($media);
				});

				$siteinfo[$element] = array_values(array_filter($siteinfo[$element]));
			}
			if (empty($siteinfo[$element])) {
				unset($siteinfo[$element]);
			}
		}
		return $siteinfo;
	}

	/**
	 * Convert tags from CSV to an array
	 *
	 * @param string $string Tags
	 *
	 * @return array with formatted Hashtags
	 */
	public static function convertTagsToArray(string $string): array
	{
		$arr_tags = str_getcsv($string);
		if (count($arr_tags)) {
			// add the # sign to every tag
			array_walk($arr_tags, ['self', 'arrAddHashes']);

			return $arr_tags;
		}
		return [];
	}

	/**
	 * Add a hasht sign to a string
	 *
	 * This method is used as callback function
	 *
	 * @param string $tag The pure tag name
	 * @param int    $k   Counter for internal use
	 *
	 * @return void
	 */
	private static function arrAddHashes(string &$tag, int $k)
	{
		$tag = '#' . $tag;
	}

	/**
	 * Add a scheme to an url
	 *
	 * The src attribute of some html elements (e.g. images)
	 * can miss the scheme so we need to add the correct
	 * scheme
	 *
	 * @param string $url    The url which possibly does have
	 *                       a missing scheme (a link to an image)
	 * @param string $scheme The url with a correct scheme
	 *                       (e.g. the url from the webpage which does contain the image)
	 *
	 * @return string The url with a scheme
	 */
	private static function completeUrl(string $url, string $scheme): string
	{
		$urlarr = parse_url($url);

		// If the url does allready have an scheme
		// we can stop the process here
		if (isset($urlarr['scheme'])) {
			return $url;
		}

		$schemearr = parse_url($scheme);

		$complete = $schemearr['scheme'] . '://' . $schemearr['host'];

		if (!empty($schemearr['port'])) {
			$complete .= ':' . $schemearr['port'];
		}

		if (!empty($urlarr['path'])) {
			if (strpos($urlarr['path'], '/') !== 0) {
				$complete .= '/';
			}

			$complete .= $urlarr['path'];
		}

		if (!empty($urlarr['query'])) {
			$complete .= '?' . $urlarr['query'];
		}

		if (!empty($urlarr['fragment'])) {
			$complete .= '#' . $urlarr['fragment'];
		}

		return $complete;
	}

	/**
	 * Parse the Json-Ld parts of a web page
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseParts(array $siteinfo, array $jsonld): array
	{
		if (!empty($jsonld['@graph']) && is_array($jsonld['@graph'])) {
			foreach ($jsonld['@graph'] as $part) {
				if (!empty($part) && is_array($part)) {
					$siteinfo = self::parseParts($siteinfo, $part);
				}
			}
		} elseif (!empty($jsonld['@type'])) {
			$siteinfo = self::parseJsonLd($siteinfo, $jsonld);
		} elseif (!empty($jsonld)) {
			$keys = array_keys($jsonld);
			$numeric_keys = true;
			foreach ($keys as $key) {
				if (!is_int($key)) {
					$numeric_keys = false;
				}
			}
			if ($numeric_keys) {
				foreach ($jsonld as $part) {
					if (!empty($part) && is_array($part)) {
						$siteinfo = self::parseParts($siteinfo, $part);
					}
				}
			}
		}

		array_walk_recursive($siteinfo, function (&$element) {
			if (is_string($element)) {
				$element = trim(strip_tags(html_entity_decode($element, ENT_COMPAT, 'UTF-8')));
			}
		});

		return $siteinfo;
	}

	/**
	 * Improve the siteinfo with information from the provided JSON-LD information
	 * @see https://jsonld.com/
	 * @see https://schema.org/
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLd(array $siteinfo, array $jsonld): array
	{
		$type = JsonLD::fetchElement($jsonld, '@type');
		if (empty($type)) {
			Logger::info('Empty type', ['url' => $siteinfo['url']]);
			return $siteinfo;
		}

		// Silently ignore some types that aren't processed
		if (in_array($type, ['SiteNavigationElement', 'JobPosting', 'CreativeWork', 'MusicAlbum',
			'WPHeader', 'WPSideBar', 'WPFooter', 'LegalService', 'MusicRecording',
			'ItemList', 'BreadcrumbList', 'Blog', 'Dataset', 'Product'])) {
			return $siteinfo;
		}

		switch ($type) {
			case 'Article':
			case 'AdvertiserContentArticle':
			case 'NewsArticle':
			case 'Report':
			case 'SatiricalArticle':
			case 'ScholarlyArticle':
			case 'SocialMediaPosting':
			case 'TechArticle':
			case 'ReportageNewsArticle':
			case 'SocialMediaPosting':
			case 'BlogPosting':
			case 'LiveBlogPosting':
			case 'DiscussionForumPosting':
				return self::parseJsonLdArticle($siteinfo, $jsonld);
			case 'WebPage':
			case 'AboutPage':
			case 'CheckoutPage':
			case 'CollectionPage':
			case 'ContactPage':
			case 'FAQPage':
			case 'ItemPage':
			case 'MedicalWebPage':
			case 'ProfilePage':
			case 'QAPage':
			case 'RealEstateListing':
			case 'SearchResultsPage':
			case 'MediaGallery':
			case 'ImageGallery':
			case 'VideoGallery':
			case 'RadioEpisode':
			case 'Event':
				return self::parseJsonLdWebPage($siteinfo, $jsonld);
			case 'WebSite':
				return self::parseJsonLdWebSite($siteinfo, $jsonld);
			case 'Organization':
			case 'Airline':
			case 'Consortium':
			case 'Corporation':
			case 'EducationalOrganization':
			case 'FundingScheme':
			case 'GovernmentOrganization':
			case 'LibrarySystem':
			case 'LocalBusiness':
			case 'MedicalOrganization':
			case 'NGO':
			case 'NewsMediaOrganization':
			case 'Project':
			case 'SportsOrganization':
			case 'WorkersUnion':
				return self::parseJsonLdWebOrganization($siteinfo, $jsonld);
			case 'Person':
			case 'Patient':
			case 'PerformingGroup':
			case 'DanceGroup';
			case 'MusicGroup':
			case 'TheaterGroup':
				return self::parseJsonLdWebPerson($siteinfo, $jsonld);
			case 'AudioObject':
			case 'Audio':
				return self::parseJsonLdMediaObject($siteinfo, $jsonld, 'audio');
			case 'VideoObject':
				return self::parseJsonLdMediaObject($siteinfo, $jsonld, 'video');
			case 'ImageObject':
				return self::parseJsonLdMediaObject($siteinfo, $jsonld, 'images');
			default:
				Logger::info('Unknown type', ['type' => $type, 'url' => $siteinfo['url']]);
				return $siteinfo;
		}
	}

	/**
	 * Fetch author and publisher data
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdAuthor(array $siteinfo, array $jsonld): array
	{
		$jsonldinfo = [];

		if (!empty($jsonld['publisher']) && is_array($jsonld['publisher'])) {
			$content = JsonLD::fetchElement($jsonld, 'publisher', 'name');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['publisher_name'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'publisher', 'url');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['publisher_url'] = trim($content);
			}

			$brand = JsonLD::fetchElement($jsonld, 'publisher', 'brand', '@type', 'Organization');
			if (!empty($brand) && is_array($brand)) {
				$content = JsonLD::fetchElement($brand, 'name');
				if (!empty($content) && is_string($content)) {
					$jsonldinfo['publisher_name'] = trim($content);
				}

				$content = JsonLD::fetchElement($brand, 'url');
				if (!empty($content) && is_string($content)) {
					$jsonldinfo['publisher_url'] = trim($content);
				}

				$content = JsonLD::fetchElement($brand, 'logo', 'url');
				if (!empty($content) && is_string($content)) {
					$jsonldinfo['publisher_img'] = trim($content);
				}
			}

			$logo = JsonLD::fetchElement($jsonld, 'publisher', 'logo');
			if (!empty($logo) && is_array($logo)) {
				$content = JsonLD::fetchElement($logo, 'url');
				if (!empty($content) && is_string($content)) {
					$jsonldinfo['publisher_img'] = trim($content);
				}
			}
		} elseif (!empty($jsonld['publisher']) && is_string($jsonld['publisher'])) {
			$jsonldinfo['publisher_name'] = trim($jsonld['publisher']);
		}

		if (!empty($jsonld['author']) && is_array($jsonld['author'])) {
			$content = JsonLD::fetchElement($jsonld, 'author', 'name');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['author_name'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'author', 'sameAs');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['author_url'] = trim($content);
			}

			$content = JsonLD::fetchElement($jsonld, 'author', 'url');
			if (!empty($content) && is_string($content)) {
				$jsonldinfo['author_url'] = trim($content);
			}

			$logo = JsonLD::fetchElement($jsonld, 'author', 'logo');
			if (!empty($logo) && is_array($logo)) {
				$content = JsonLD::fetchElement($logo, 'url');
				if (!empty($content) && is_string($content)) {
					$jsonldinfo['author_img'] = trim($content);
				}
			}
		} elseif (!empty($jsonld['author']) && is_string($jsonld['author'])) {
			$jsonldinfo['author_name'] = trim($jsonld['author']);
		}

		Logger::info('Fetched Author information', ['fetched' => $jsonldinfo]);

		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Fetch data from the provided JSON-LD Article type
	 * @see https://schema.org/Article
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdArticle(array $siteinfo, array $jsonld): array
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'headline');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['title'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'alternativeHeadline');
		if (!empty($content) && is_string($content) && (($jsonldinfo['title'] ?? '') != trim($content))) {
			$jsonldinfo['alternative_title'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['text'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'thumbnailUrl');
		if (!empty($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'image', 'url', '@type', 'ImageObject');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		if (!empty($jsonld['keywords']) && !is_array($jsonld['keywords'])) {
			$content = JsonLD::fetchElement($jsonld, 'keywords');
			if (!empty($content)) {
				$siteinfo['keywords'] = [];
				$keywords = explode(',', $content);
				foreach ($keywords as $keyword) {
					$siteinfo['keywords'][] = trim($keyword);
				}
			}
		} elseif (!empty($jsonld['keywords'])) {
			$content = JsonLD::fetchElementArray($jsonld, 'keywords');
			if (!empty($content) && is_array($content)) {
				$jsonldinfo['keywords'] = $content;
			}
		}

		$content = JsonLD::fetchElement($jsonld, 'datePublished');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['published'] = DateTimeFormat::utc($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'dateModified');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['modified'] = DateTimeFormat::utc($content);
		}

		$jsonldinfo = self::parseJsonLdAuthor($jsonldinfo, $jsonld);

		Logger::info('Fetched article information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);

		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Fetch data from the provided JSON-LD WebPage type
	 * @see https://schema.org/WebPage
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebPage(array $siteinfo, array $jsonld): array
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content)) {
			$jsonldinfo['title'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['text'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'image');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'thumbnailUrl');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		$jsonldinfo = self::parseJsonLdAuthor($jsonldinfo, $jsonld);

		Logger::info('Fetched WebPage information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);

		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Fetch data from the provided JSON-LD WebSite type
	 * @see https://schema.org/WebSite
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebSite(array $siteinfo, array $jsonld): array
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_description'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'thumbnailUrl');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['image'] = trim($content);
		}

		$jsonldinfo = self::parseJsonLdAuthor($jsonldinfo, $jsonld);

		Logger::info('Fetched WebSite information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);
		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Fetch data from the provided JSON-LD Organization type
	 * @see https://schema.org/Organization
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebOrganization(array $siteinfo, array $jsonld): array
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_description'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'logo', 'url', '@type', 'ImageObject');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_img'] = trim($content);
		} elseif (!empty($content) && is_array($content)) {
			$jsonldinfo['publisher_img'] = trim($content[0]);
		}

		$content = JsonLD::fetchElement($jsonld, 'brand', 'name', '@type', 'Organization');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'brand', 'url', '@type', 'Organization');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['publisher_url'] = trim($content);
		}

		Logger::info('Fetched Organization information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);
		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Fetch data from the provided JSON-LD Person type
	 * @see https://schema.org/Person
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdWebPerson(array $siteinfo, array $jsonld): array
	{
		$jsonldinfo = [];

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['author_name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['author_description'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'sameAs');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['author_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content) && is_string($content)) {
			$jsonldinfo['author_url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'image', 'url', '@type', 'ImageObject');
		if (!empty($content) && !is_string($content)) {
			Logger::notice('Unexpected return value for the author image', ['content' => $content]);
		}

		if (!empty($content) && is_string($content)) {
			$jsonldinfo['author_img'] = trim($content);
		}

		Logger::info('Fetched Person information', ['url' => $siteinfo['url'], 'fetched' => $jsonldinfo]);
		return array_merge($siteinfo, $jsonldinfo);
	}

	/**
	 * Fetch data from the provided JSON-LD MediaObject type
	 * @see https://schema.org/MediaObject
	 *
	 * @param array $siteinfo
	 * @param array $jsonld
	 *
	 * @return array siteinfo
	 */
	private static function parseJsonLdMediaObject(array $siteinfo, array $jsonld, string $name): array
	{
		$media = [];

		$content = JsonLD::fetchElement($jsonld, 'caption');
		if (!empty($content) && is_string($content)) {
			$media['caption'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'url');
		if (!empty($content) && is_string($content)) {
			$media['url'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'mainEntityOfPage');
		if (!empty($content) && is_string($content)) {
			$media['main'] = Strings::compareLink($content, $siteinfo['url']);
		}

		$content = JsonLD::fetchElement($jsonld, 'description');
		if (!empty($content) && is_string($content)) {
			$media['description'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'name');
		if (!empty($content) && (($media['description'] ?? '') != trim($content))) {
			$media['name'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'contentUrl');
		if (!empty($content) && is_string($content)) {
			$media['content'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'embedUrl');
		if (!empty($content) && is_string($content)) {
			$media['embed'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'height');
		if (!empty($content) && is_string($content)) {
			$media['height'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'width');
		if (!empty($content) && is_string($content)) {
			$media['width'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'image');
		if (!empty($content) && is_string($content)) {
			$media['image'] = trim($content);
		}

		$content = JsonLD::fetchElement($jsonld, 'thumbnailUrl');
		if (!empty($content) && (($media['image'] ?? '') != trim($content))) {
			if (!empty($media['image'])) {
				$media['preview'] = trim($content);
			} else {
				$media['image'] = trim($content);
			}
		}

		Logger::info('Fetched Media information', ['url' => $siteinfo['url'], 'fetched' => $media]);
		$siteinfo[$name][] = $media;
		return $siteinfo;
	}
}
