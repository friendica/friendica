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

namespace Friendica\Content;

use DOMDocument;
use DOMNode;
use DOMText;
use DOMXPath;
use Exception;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

/**
 * Handles all OEmbed content fetching and replacement
 *
 * OEmbed is a standard used to allow an embedded representation of a URL on
 * third party sites
 *
 * @see https://oembed.com
 */
class OEmbed
{
	public static function replaceCallback($matches)
	{
		$embedurl = $matches[1];
		$j = self::fetchURL($embedurl, !self::isAllowedURL($embedurl));
		$s = self::formatObject($j);

		return $s;
	}

	/**
	 * Get data from an URL to embed its content.
	 *
	 * @param string $embedurl     The URL from which the data should be fetched.
	 * @param bool   $no_rich_type If set to true rich type content won't be fetched.
	 *
	 * @return \Friendica\Object\OEmbed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchURL($embedurl, $no_rich_type = false)
	{
		$embedurl = trim($embedurl, '\'"');

		$a = DI::app();

		$cache_key = 'oembed:' . $a->videowidth . ':' . $embedurl;

		$condition = ['url' => Strings::normaliseLink($embedurl), 'maxwidth' => $a->videowidth];
		$oembed_record = DBA::selectFirst('oembed', ['content'], $condition);
		if (DBA::isResult($oembed_record)) {
			$json_string = $oembed_record['content'];
		} else {
			$json_string = DI::cache()->get($cache_key);
		}

		// These media files should now be caught in bbcode.php
		// left here as a fallback in case this is called from another source
		$noexts = ['mp3', 'mp4', 'ogg', 'ogv', 'oga', 'ogm', 'webm'];
		$ext = pathinfo(strtolower($embedurl), PATHINFO_EXTENSION);

		$oembed = new \Friendica\Object\OEmbed($embedurl);

		if ($json_string) {
			$oembed->parseJSON($json_string);
		} else {
			$json_string = '';

			if (!in_array($ext, $noexts)) {
				// try oembed autodiscovery
				$html_text = DI::httpRequest()->fetch($embedurl, 15, 'text/*');
				if ($html_text) {
					$dom = new DOMDocument();
					if (@$dom->loadHTML($html_text)) {
						$xpath = new DOMXPath($dom);
						foreach (
							$xpath->query("//link[@type='application/json+oembed'] | //link[@type='text/json+oembed']")
							as $link)
						{
							$href = $link->getAttributeNode('href')->nodeValue;
							// Both Youtube and Vimeo output OEmbed endpoint URL with HTTP
							// but their OEmbed endpoint is only accessible by HTTPS ¯\_(ツ)_/¯
							$href = str_replace(['http://www.youtube.com/', 'http://player.vimeo.com/'],
								['https://www.youtube.com/', 'https://player.vimeo.com/'], $href);
							$result = DI::httpRequest()->fetchFull($href . '&maxwidth=' . $a->videowidth);
							if ($result->getReturnCode() === 200) {
								$json_string = $result->getBody();
								break;
							}
						}
					}
				}
			}

			$json_string = trim($json_string);

			if (!$json_string || $json_string[0] != '{') {
				$json_string = '{"type":"error"}';
			}

			$oembed->parseJSON($json_string);

			if (!empty($oembed->type) && $oembed->type != 'error') {
				DBA::insert('oembed', [
					'url' => Strings::normaliseLink($embedurl),
					'maxwidth' => $a->videowidth,
					'content' => $json_string,
					'created' => DateTimeFormat::utcNow()
				], Database::INSERT_UPDATE);
				$cache_ttl = Duration::DAY;
			} else {
				$cache_ttl = Duration::FIVE_MINUTES;
			}

			DI::cache()->set($cache_key, $json_string, $cache_ttl);
		}

		if ($oembed->type == 'error') {
			return $oembed;
		}

		// Always embed the SSL version
		$oembed->html = str_replace(['http://www.youtube.com/', 'http://player.vimeo.com/'], ['https://www.youtube.com/', 'https://player.vimeo.com/'], $oembed->html);

		// Improve the OEmbed data with OpenGraph
		$data = ParseUrl::getSiteinfoCached($embedurl, true, false);
		if ($no_rich_type) {
			unset($oembed->html);
			$oembed->type = $data['type'];

			if ($oembed->type == 'photo') {
				$oembed->url = $data['url'];
			}
		}

		if (!empty($data['title']) && empty($oembed->title)) {
			$oembed->title = $data['title'];
		}

		if (!empty($data['text']) && empty($oembed->description)) {
			$oembed->description = $data['text'];
		}

		if (!empty($data['images']) && empty($oembed->thumbnail_url)) {
			$oembed->thumbnail_url = $data['images'][0]['src'];
			$oembed->thumbnail_width = $data['images'][0]['width'];
			$oembed->thumbnail_height = $data['images'][0]['height'];
		}

		Hook::callAll('oembed_fetch_url', $embedurl, $oembed);

		return $oembed;
	}

	private static function formatObject(\Friendica\Object\OEmbed $oembed)
	{
		$ret = '<div class="oembed ' . $oembed->type . '">';

		switch ($oembed->type) {
			case "video":
				if ($oembed->thumbnail_url) {
					$tw = (isset($oembed->thumbnail_width) && intval($oembed->thumbnail_width)) ? $oembed->thumbnail_width : 200;
					$th = (isset($oembed->thumbnail_height) && intval($oembed->thumbnail_height)) ? $oembed->thumbnail_height : 180;
					// make sure we don't attempt divide by zero, fallback is a 1:1 ratio
					$tr = (($th) ? $tw / $th : 1);

					$th = 120;
					$tw = $th * $tr;
					$tpl = Renderer::getMarkupTemplate('oembed_video.tpl');
					$ret .= Renderer::replaceMacros($tpl, [
						'$embedurl' => $oembed->embed_url,
						'$escapedhtml' => base64_encode($oembed->html),
						'$tw' => $tw,
						'$th' => $th,
						'$turl' => $oembed->thumbnail_url,
					]);
				} else {
					$ret = $oembed->html;
				}
				break;

			case "photo":
				$ret .= '<img width="' . $oembed->width . '" src="' . ProxyUtils::proxifyUrl($oembed->url) . '">';
				break;

			case "link":
				break;

			case "rich":
				$ret .= ProxyUtils::proxifyHtml($oembed->html);
				break;
		}

		// add link to source if not present in "rich" type
		if ($oembed->type != 'rich' || !strpos($oembed->html, $oembed->embed_url)) {
			$ret .= '<h4>';
			if (!empty($oembed->title)) {
				if (!empty($oembed->provider_name)) {
					$ret .= $oembed->provider_name . ": ";
				}

				$ret .= '<a href="' . $oembed->embed_url . '" rel="oembed">' . $oembed->title . '</a>';
				if (!empty($oembed->author_name)) {
					$ret .= ' (' . $oembed->author_name . ')';
				}
			} elseif (!empty($oembed->provider_name) || !empty($oembed->author_name)) {
				$embedlink = "";
				if (!empty($oembed->provider_name)) {
					$embedlink .= $oembed->provider_name;
				}

				if (!empty($oembed->author_name)) {
					if ($embedlink != "") {
						$embedlink .= ": ";
					}

					$embedlink .= $oembed->author_name;
				}
				if (trim($embedlink) == "") {
					$embedlink = $oembed->embed_url;
				}

				$ret .= '<a href="' . $oembed->embed_url . '" rel="oembed">' . $embedlink . '</a>';
			} else {
				$ret .= '<a href="' . $oembed->embed_url . '" rel="oembed">' . $oembed->embed_url . '</a>';
			}
			$ret .= "</h4>";
		} elseif (!strpos($oembed->html, $oembed->embed_url)) {
			// add <a> for html2bbcode conversion
			$ret .= '<a href="' . $oembed->embed_url . '" rel="oembed">' . $oembed->title . '</a>';
		}

		$ret .= '</div>';

		return str_replace("\n", "", $ret);
	}

	public static function BBCode2HTML($text)
	{
		$stopoembed = DI::config()->get("system", "no_oembed");
		if ($stopoembed == true) {
			return preg_replace("/\[embed\](.+?)\[\/embed\]/is", "<!-- oembed $1 --><i>" . DI::l10n()->t('Embedding disabled') . " : $1</i><!-- /oembed $1 -->", $text);
		}
		return preg_replace_callback("/\[embed\](.+?)\[\/embed\]/is", ['self', 'replaceCallback'], $text);
	}

	/**
	 * Find <span class='oembed'>..<a href='url' rel='oembed'>..</a></span>
	 * and replace it with [embed]url[/embed]
	 *
	 * @param $text
	 * @return string
	 */
	public static function HTML2BBCode($text)
	{
		// start parser only if 'oembed' is in text
		if (strpos($text, "oembed")) {

			// convert non ascii chars to html entities
			$html_text = mb_convert_encoding($text, 'HTML-ENTITIES', mb_detect_encoding($text));

			// If it doesn't parse at all, just return the text.
			$dom = @DOMDocument::loadHTML($html_text);
			if (!$dom) {
				return $text;
			}
			$xpath = new DOMXPath($dom);

			$xattr = self::buildXPath("class", "oembed");
			$entries = $xpath->query("//div[$xattr]");

			$xattr = "@rel='oembed'"; //oe_build_xpath("rel","oembed");
			foreach ($entries as $e) {
				$href = $xpath->evaluate("a[$xattr]/@href", $e)->item(0)->nodeValue;
				if (!is_null($href)) {
					$e->parentNode->replaceChild(new DOMText("[embed]" . $href . "[/embed]"), $e);
				}
			}
			return self::getInnerHTML($dom->getElementsByTagName("body")->item(0));
		} else {
			return $text;
		}
	}

	/**
	 * Determines if rich content OEmbed is allowed for the provided URL
	 *
	 * @param string $url
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isAllowedURL($url)
	{
		if (!DI::config()->get('system', 'no_oembed_rich_content')) {
			return true;
		}

		$domain = parse_url($url, PHP_URL_HOST);
		if (empty($domain)) {
			return false;
		}

		$str_allowed = DI::config()->get('system', 'allowed_oembed', '');
		if (empty($str_allowed)) {
			return false;
		}

		$allowed = explode(',', $str_allowed);

		return Network::isDomainAllowed($domain, $allowed);
	}

	public static function getHTML($url, $title = null)
	{
		$o = self::fetchURL($url, !self::isAllowedURL($url));

		if (!is_object($o) || property_exists($o, 'type') && $o->type == 'error') {
			throw new Exception('OEmbed failed for URL: ' . $url);
		}

		if (!empty($title)) {
			$o->title = $title;
		}

		$html = self::formatObject($o);

		return $html;
	}

	/**
	 * Generates the iframe HTML for an oembed attachment.
	 *
	 * Width and height are given by the remote, and are regularly too small for
	 * the generated iframe.
	 *
	 * The width is entirely discarded for the actual width of the post, while fixed
	 * height is used as a starting point before the inevitable resizing.
	 *
	 * Since the iframe is automatically resized on load, there are no need for ugly
	 * and impractical scrollbars.
	 *
	 * @todo  This function is currently unused until someone™ adds support for a separate OEmbed domain
	 *
	 * @param string $src Original remote URL to embed
	 * @param string $width
	 * @param string $height
	 * @return string formatted HTML
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   oembed_format_object()
	 */
	private static function iframe($src, $width, $height)
	{
		if (!$height || strstr($height, '%')) {
			$height = '200';
		}
		$width = '100%';

		$src = DI::baseUrl() . '/oembed/' . Strings::base64UrlEncode($src);
		return '<iframe onload="resizeIframe(this);" class="embed_rich" height="' . $height . '" width="' . $width . '" src="' . $src . '" allowfullscreen scrolling="no" frameborder="no">' . DI::l10n()->t('Embedded content') . '</iframe>';
	}

	/**
	 * Generates attribute search XPath string
	 *
	 * Generates an XPath query to select elements whose provided attribute contains
	 * the provided value in a space-separated list.
	 *
	 * @param string $attr Name of the attribute to seach
	 * @param string $value Value to search in a space-separated list
	 * @return string
	 */
	private static function buildXPath($attr, $value)
	{
		// https://www.westhoffswelt.de/blog/2009/6/9/select-html-elements-with-more-than-one-css-class-using-xpath
		return "contains(normalize-space(@$attr), ' $value ') or substring(normalize-space(@$attr), 1, string-length('$value') + 1) = '$value ' or substring(normalize-space(@$attr), string-length(@$attr) - string-length('$value')) = ' $value' or @$attr = '$value'";
	}

	/**
	 * Returns the inner XML string of a provided DOMNode
	 *
	 * @param DOMNode $node
	 * @return string
	 */
	private static function getInnerHTML(DOMNode $node)
	{
		$innerHTML = '';
		$children = $node->childNodes;
		foreach ($children as $child) {
			$innerHTML .= $child->ownerDocument->saveXML($child);
		}
		return $innerHTML;
	}

}
