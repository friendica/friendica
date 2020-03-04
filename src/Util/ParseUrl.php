<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Database\DBA;

/**
 * Get information about a given URL
 *
 * Class with methods for extracting certain content from an url
 */
class ParseUrl
{
	/**
	 * Maximum number of characters for the description
	 */
	const MAX_DESC_COUNT = 250;

	/**
	 * Minimum number of characters for the description
	 */
	const MIN_DESC_COUNT = 100;

	/**
	 * Search for chached embeddable data of an url otherwise fetch it
	 *
	 * @param string $url         The url of the page which should be scraped
	 * @param bool   $no_guessing If true the parse doens't search for
	 *                            preview pictures
	 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
	 *                            to avoid endless loops
	 *
	 * @return array which contains needed data for embedding
	 *    string 'url' => The url of the parsed page
	 *    string 'type' => Content type
	 *    string 'title' => The title of the content
	 *    string 'text' => The description for the content
	 *    string 'image' => A preview image of the content (only available
	 *                if $no_geuessing = false
	 *    array'images' = Array of preview pictures
	 *    string 'keywords' => The tags which belong to the content
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   ParseUrl::getSiteinfo() for more information about scraping
	 * embeddable content
	 */
	public static function getSiteinfoCached($url, $no_guessing = false, $do_oembed = true)
	{
		if ($url == "") {
			return false;
		}

		$parsed_url = DBA::selectFirst('parsed_url', ['content'],
			['url' => Strings::normaliseLink($url), 'guessing' => !$no_guessing, 'oembed' => $do_oembed]
		);
		if (!empty($parsed_url['content'])) {
			$data = unserialize($parsed_url['content']);
			return $data;
		}

		$data = self::getSiteinfo($url, $no_guessing, $do_oembed);

		DBA::insert(
			'parsed_url',
			[
				'url' => Strings::normaliseLink($url), 'guessing' => !$no_guessing,
				'oembed' => $do_oembed, 'content' => serialize($data),
				'created' => DateTimeFormat::utcNow()
			],
			true
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
	 * @param bool   $no_guessing If true the parse doens't search for
	 *                            preview pictures
	 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
	 *                            to avoid endless loops
	 * @param int    $count       Internal counter to avoid endless loops
	 *
	 * @return array which contains needed data for embedding
	 *    string 'url' => The url of the parsed page
	 *    string 'type' => Content type
	 *    string 'title' => The title of the content
	 *    string 'text' => The description for the content
	 *    string 'image' => A preview image of the content (only available
	 *                if $no_geuessing = false
	 *    array'images' = Array of preview pictures
	 *    string 'keywords' => The tags which belong to the content
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
	public static function getSiteinfo($url, $no_guessing = false, $do_oembed = true, $count = 1)
	{
		$siteinfo = [];

		// Check if the URL does contain a scheme
		$scheme = parse_url($url, PHP_URL_SCHEME);

		if ($scheme == '') {
			$url = 'http://' . trim($url, '/');
		}

		if ($count > 10) {
			Logger::log('Endless loop detected for ' . $url, Logger::DEBUG);
			return $siteinfo;
		}

		$url = trim($url, "'");
		$url = trim($url, '"');

		$url = Network::stripTrackingQueryParams($url);

		$siteinfo['url'] = $url;
		$siteinfo['type'] = 'link';

		$curlResult = DI::httpRequest()->curl($url);
		if (!$curlResult->isSuccess()) {
			return $siteinfo;
		}

		// If the file is too large then exit
		if (($curlResult->getInfo()['download_content_length'] ?? 0) > 1000000) {
			return $siteinfo;
		}

		// If it isn't a HTML file then exit
		if (($curlResult->getContentType() != '') && !strstr(strtolower($curlResult->getContentType()), 'html')) {
			return $siteinfo;
		}

		$header = $curlResult->getHeader();
		$body = $curlResult->getBody();

		if ($do_oembed) {
			$oembed_data = OEmbed::fetchURL($url);

			if (!empty($oembed_data->type)) {
				if (!in_array($oembed_data->type, ['error', 'rich', ''])) {
					$siteinfo['type'] = $oembed_data->type;
				}

				// See https://github.com/friendica/friendica/pull/5763#discussion_r217913178
				if ($siteinfo['type'] != 'photo') {
					if (isset($oembed_data->title)) {
						$siteinfo['title'] = trim($oembed_data->title);
					}
					if (isset($oembed_data->description)) {
						$siteinfo['text'] = trim($oembed_data->description);
					}
					if (isset($oembed_data->thumbnail_url)) {
						$siteinfo['image'] = $oembed_data->thumbnail_url;
					}
				}
			}
		}

		// Fetch the first mentioned charset. Can be in body or header
		$charset = '';
		if (preg_match('/charset=(.*?)[\'"\s\n]/', $header, $matches)) {
			$charset = trim(trim(trim(array_pop($matches)), ';,'));
		}

		if ($charset && strtoupper($charset) != 'UTF-8') {
			// See https://github.com/friendica/friendica/issues/5470#issuecomment-418351211
			$charset = str_ireplace('latin-1', 'latin1', $charset);

			Logger::log('detected charset ' . $charset, Logger::DEBUG);
			$body = iconv($charset, 'UTF-8//TRANSLIT', $body);
		}

		$body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		XML::deleteNode($doc, 'style');
		XML::deleteNode($doc, 'script');
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
					$siteinfo = self::getSiteinfo($content, $no_guessing, $do_oembed, ++$count);
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
				case 'twitter:card':
					// Detect photo pages
					if ($meta_tag['content'] == 'summary_large_image') {
						$siteinfo['type'] = 'photo';
					}
					break;
				case 'twitter:description':
					$siteinfo['text'] = trim($meta_tag['content']);
					break;
				case 'twitter:title':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'dc.title':
					$siteinfo['title'] = trim($meta_tag['content']);
					break;
				case 'dc.description':
					$siteinfo['text'] = trim($meta_tag['content']);
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
					case 'og:title':
						$siteinfo['title'] = trim($meta_tag['content']);
						break;
					case 'og:description':
						$siteinfo['text'] = trim($meta_tag['content']);
						break;
				}
			}
		}

		// Prevent to have a photo type without an image
		if ((empty($siteinfo['image']) || !empty($siteinfo['text'])) && ($siteinfo['type'] == 'photo')) {
			$siteinfo['type'] = 'link';
		}

		if (!empty($siteinfo['image'])) {
			$src = self::completeUrl($siteinfo['image'], $url);

			unset($siteinfo['image']);

			$photodata = Images::getInfoFromURLCached($src);

			if (($photodata) && ($photodata[0] > 10) && ($photodata[1] > 10)) {
				$siteinfo['images'][] = ['src' => $src,
					'width' => $photodata[0],
					'height' => $photodata[1]];
			}
		}

		if (!empty($siteinfo['text']) && mb_strlen($siteinfo['text']) > self::MAX_DESC_COUNT) {
			$siteinfo['text'] = mb_substr($siteinfo['text'], 0, self::MAX_DESC_COUNT) . '…';
			$pos = mb_strrpos($siteinfo['text'], '.');
			if ($pos > self::MIN_DESC_COUNT) {
				$siteinfo['text'] = mb_substr($siteinfo['text'], 0, $pos + 1);
			}
		}

		Logger::info('Siteinfo fetched', ['url' => $url, 'siteinfo' => $siteinfo]);

		Hook::callAll('getsiteinfo', $siteinfo);

		return $siteinfo;
	}

	/**
	 * Convert tags from CSV to an array
	 *
	 * @param string $string Tags
	 * @return array with formatted Hashtags
	 */
	public static function convertTagsToArray($string)
	{
		$arr_tags = str_getcsv($string);
		if (count($arr_tags)) {
			// add the # sign to every tag
			array_walk($arr_tags, ["self", "arrAddHashes"]);

			return $arr_tags;
		}
	}

	/**
	 * Add a hasht sign to a string
	 *
	 * This method is used as callback function
	 *
	 * @param string $tag The pure tag name
	 * @param int    $k   Counter for internal use
	 * @return void
	 */
	private static function arrAddHashes(&$tag, $k)
	{
		$tag = "#" . $tag;
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
	private static function completeUrl($url, $scheme)
	{
		$urlarr = parse_url($url);

		// If the url does allready have an scheme
		// we can stop the process here
		if (isset($urlarr["scheme"])) {
			return($url);
		}

		$schemearr = parse_url($scheme);

		$complete = $schemearr["scheme"]."://".$schemearr["host"];

		if (!empty($schemearr["port"])) {
			$complete .= ":".$schemearr["port"];
		}

		if (!empty($urlarr["path"])) {
			if (strpos($urlarr["path"], "/") !== 0) {
				$complete .= "/";
			}

			$complete .= $urlarr["path"];
		}

		if (!empty($urlarr["query"])) {
			$complete .= "?".$urlarr["query"];
		}

		if (!empty($urlarr["fragment"])) {
			$complete .= "#".$urlarr["fragment"];
		}

		return($complete);
	}
}
