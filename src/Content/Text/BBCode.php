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

namespace Friendica\Content\Text;

use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\Content\ContactSelector;
use Friendica\Content\Item;
use Friendica\Content\OEmbed;
use Friendica\Content\PageInfo;
use Friendica\Content\Smilies;
use Friendica\Content\Text\HTMLPurifier_URIScheme_cid;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\Photo;
use Friendica\Model\Tag;
use Friendica\Object\Image;
use Friendica\Protocol\Activity;
use Friendica\Util\Images;
use Friendica\Util\Map;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\XML;

class BBCode
{
	// Update this value to the current date whenever changes are made to BBCode::convert
	const VERSION = '2020-12-18-video-embeds';

	const INTERNAL = 0;
	const API = 2;
	const DIASPORA = 3;
	const CONNECTORS = 4;
	const OSTATUS = 7;
	const TWITTER = 8;
	const BACKLINK = 8;
	const ACTIVITYPUB = 9;

	/**
	 * Fetches attachment data that were generated the old way
	 *
	 * @param string $body Message body
	 * @return array
	 *                     'type' -> Message type ('link', 'video', 'photo')
	 *                     'text' -> Text before the shared message
	 *                     'after' -> Text after the shared message
	 *                     'image' -> Preview image of the message
	 *                     'url' -> Url to the attached message
	 *                     'title' -> Title of the attachment
	 *                     'description' -> Description of the attachment
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getOldAttachmentData($body)
	{
		$post = [];

		// Simplify image codes
		$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

		if (preg_match_all("(\[class=(.*?)\](.*?)\[\/class\])ism", $body, $attached, PREG_SET_ORDER)) {
			foreach ($attached as $data) {
				if (!in_array($data[1], ['type-link', 'type-video', 'type-photo'])) {
					continue;
				}

				$post['type'] = substr($data[1], 5);

				$pos = strpos($body, $data[0]);
				if ($pos > 0) {
					$post['text'] = trim(substr($body, 0, $pos));
					$post['after'] = trim(substr($body, $pos + strlen($data[0])));
				} else {
					$post['text'] = trim(str_replace($data[0], '', $body));
					$post['after'] = '';
				}

				$attacheddata = $data[2];

				if (preg_match("/\[img\](.*?)\[\/img\]/ism", $attacheddata, $matches)) {

					$picturedata = Images::getInfoFromURLCached($matches[1]);

					if ($picturedata) {
						if (($picturedata[0] >= 500) && ($picturedata[0] >= $picturedata[1])) {
							$post['image'] = $matches[1];
						} else {
							$post['preview'] = $matches[1];
						}
					}
				}

				if (preg_match("/\[bookmark\=(.*?)\](.*?)\[\/bookmark\]/ism", $attacheddata, $matches)) {
					$post['url'] = $matches[1];
					$post['title'] = $matches[2];
				}
				if (!empty($post['url']) && (in_array($post['type'], ['link', 'video']))
					&& preg_match("/\[url\=(.*?)\](.*?)\[\/url\]/ism", $attacheddata, $matches)) {
					$post['url'] = $matches[1];
				}

				// Search for description
				if (preg_match("/\[quote\](.*?)\[\/quote\]/ism", $attacheddata, $matches)) {
					$post['description'] = $matches[1];
				}
			}
		}
		return $post;
	}

	/**
	 * Fetches attachment data that were generated with the "attachment" element
	 *
	 * @param string $body Message body
	 * @return array
	 *                     'type' -> Message type ('link', 'video', 'photo')
	 *                     'text' -> Text before the shared message
	 *                     'after' -> Text after the shared message
	 *                     'image' -> Preview image of the message
	 *                     'url' -> Url to the attached message
	 *                     'title' -> Title of the attachment
	 *                     'description' -> Description of the attachment
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getAttachmentData($body)
	{
		$data = [
			'type'          => '',
			'text'          => '',
			'after'         => '',
			'image'         => null,
			'url'           => '',
			'provider_name' => '',
			'provider_url'  => '',
			'title'         => '',
			'description'   => '',
		];

		if (!preg_match("/(.*)\[attachment(.*?)\](.*?)\[\/attachment\](.*)/ism", $body, $match)) {
			return self::getOldAttachmentData($body);
		}

		$attributes = $match[2];

		$data['text'] = trim($match[1]);

		$type = '';
		preg_match("/type='(.*?)'/ism", $attributes, $matches);
		if (!empty($matches[1])) {
			$type = strtolower($matches[1]);
		}

		preg_match('/type="(.*?)"/ism', $attributes, $matches);
		if (!empty($matches[1])) {
			$type = strtolower($matches[1]);
		}

		if ($type == '') {
			return [];
		}

		if (!in_array($type, ['link', 'audio', 'photo', 'video'])) {
			return [];
		}

		if ($type != '') {
			$data['type'] = $type;
		}

		$url = '';
		preg_match("/url='(.*?)'/ism", $attributes, $matches);
		if (!empty($matches[1])) {
			$url = $matches[1];
		}

		preg_match('/url="(.*?)"/ism', $attributes, $matches);
		if (!empty($matches[1])) {
			$url = $matches[1];
		}

		if ($url != '') {
			$data['url'] = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
		}

		$title = '';
		preg_match("/title='(.*?)'/ism", $attributes, $matches);
		if (!empty($matches[1])) {
			$title = $matches[1];
		}

		preg_match('/title="(.*?)"/ism', $attributes, $matches);
		if (!empty($matches[1])) {
			$title = $matches[1];
		}

		if ($title != '') {
			$title = self::convert(html_entity_decode($title, ENT_QUOTES, 'UTF-8'), false, true);
			$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
			$title = str_replace(['[', ']'], ['&#91;', '&#93;'], $title);
			$data['title'] = $title;
		}

		$image = '';
		preg_match("/image='(.*?)'/ism", $attributes, $matches);
		if (!empty($matches[1])) {
			$image = $matches[1];
		}

		preg_match('/image="(.*?)"/ism', $attributes, $matches);
		if (!empty($matches[1])) {
			$image = $matches[1];
		}

		if ($image != '') {
			$data['image'] = html_entity_decode($image, ENT_QUOTES, 'UTF-8');
		}

		$preview = '';
		preg_match("/preview='(.*?)'/ism", $attributes, $matches);
		if (!empty($matches[1])) {
			$preview = $matches[1];
		}

		preg_match('/preview="(.*?)"/ism', $attributes, $matches);
		if (!empty($matches[1])) {
			$preview = $matches[1];
		}

		if ($preview != '') {
			$data['preview'] = html_entity_decode($preview, ENT_QUOTES, 'UTF-8');
		}

		$data['description'] = trim($match[3]);

		$data['after'] = trim($match[4]);

		$parts = parse_url($data['url']);
		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			$data['provider_name'] = $parts['host'];
			$data['provider_url'] = $parts['scheme'] . '://' . $parts['host'];

			if (!empty($parts['port'])) {
				$data['provider_url'] .= ':' . $parts['port'];
			}
		}

		return $data;
	}

	public static function getAttachedData($body, $item = [])
	{
		/*
		- text:
		- type: link, video, photo
		- title:
		- url:
		- image:
		- description:
		- (thumbnail)
		*/

		$has_title = !empty($item['title']);
		$plink = $item['plink'] ?? '';
		$post = self::getAttachmentData($body);

		// Get all linked images with alternative image description
		if (preg_match_all("/\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (Photo::isLocal($picture[1])) {
					$post['images'][] = ['url' => str_replace('-1.', '-0.', $picture[1]), 'description' => $picture[2]];
				}
			}
			if (!empty($post['images']) && !empty($post['images'][0]['description'])) {
				$post['image_description'] = $post['images'][0]['description'];
			}
		}

		if (preg_match_all("/\[img\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (Photo::isLocal($picture[1])) {
					$post['images'][] = ['url' => str_replace('-1.', '-0.', $picture[1]), 'description' => ''];
				}
			}
		}

		// if nothing is found, it maybe having an image.
		if (!isset($post['type'])) {
			// Simplify image codes
			$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);
			$body = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", '[img]$1[/img]', $body);
			$post['text'] = $body;

			if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
				if ((count($pictures) == 1) && !$has_title) {
					if (!empty($item['object-type']) && ($item['object-type'] == Activity\ObjectType::IMAGE)) {
						// Replace the preview picture with the real picture
						$url = str_replace('-1.', '-0.', $pictures[0][2]);
						$data = ['url' => $url, 'type' => 'photo'];
					} else {
						// Checking, if the link goes to a picture
						$data = ParseUrl::getSiteinfoCached($pictures[0][1], true);
					}

					// Workaround:
					// Sometimes photo posts to the own album are not detected at the start.
					// So we seem to cannot use the cache for these cases. That's strange.
					if (($data['type'] != 'photo') && strstr($pictures[0][1], "/photos/")) {
						$data = ParseUrl::getSiteinfo($pictures[0][1], true);
					}

					if ($data['type'] == 'photo') {
						$post['type'] = 'photo';
						if (isset($data['images'][0])) {
							$post['image'] = $data['images'][0]['src'];
							$post['url'] = $data['url'];
						} else {
							$post['image'] = $data['url'];
						}

						$post['preview'] = $pictures[0][2];
						$post['text'] = trim(str_replace($pictures[0][0], '', $body));
					} else {
						$imgdata = Images::getInfoFromURLCached($pictures[0][1]);
						if ($imgdata && substr($imgdata['mime'], 0, 6) == 'image/') {
							$post['type'] = 'photo';
							$post['image'] = $pictures[0][1];
							$post['preview'] = $pictures[0][2];
							$post['text'] = trim(str_replace($pictures[0][0], '', $body));
						}
					}
				} elseif (count($pictures) > 0) {
					$post['type'] = 'link';
					$post['url'] = $plink;
					$post['image'] = $pictures[0][2];
					$post['text'] = $body;

					foreach ($pictures as $picture) {
						$post['text'] = trim(str_replace($picture[0], '', $post['text']));
					}
				}
			} elseif (preg_match_all("(\[img\](.*?)\[\/img\])ism", $body, $pictures, PREG_SET_ORDER)) {
				if ((count($pictures) == 1) && !$has_title) {
					$post['type'] = 'photo';
					$post['image'] = $pictures[0][1];
					$post['text'] = str_replace($pictures[0][0], '', $body);
				} elseif (count($pictures) > 0) {
					$post['type'] = 'link';
					$post['url'] = $plink;
					$post['image'] = $pictures[0][1];
					$post['text'] = $body;

					foreach ($pictures as $picture) {
						$post['text'] = trim(str_replace($picture[0], '', $post['text']));
					}
				}
			}

			// Test for the external links
			preg_match_all("(\[url\](.*?)\[\/url\])ism", $post['text'], $links1, PREG_SET_ORDER);
			preg_match_all("(\[url\=(.*?)\].*?\[\/url\])ism", $post['text'], $links2, PREG_SET_ORDER);

			$links = array_merge($links1, $links2);

			// If there is only a single one, then use it.
			// This should cover link posts via API.
			if ((count($links) == 1) && !isset($post['preview']) && !$has_title) {
				$post['type'] = 'link';
				$post['url'] = $links[0][1];
			}

			// Simplify "video" element
			$post['text'] = preg_replace('(\[video.*?\ssrc\s?=\s?([^\s\]]+).*?\].*?\[/video\])ism', '[video]$1[/video]', $post['text']);

			// Now count the number of external media links
			preg_match_all("(\[vimeo\](.*?)\[\/vimeo\])ism", $post['text'], $links1, PREG_SET_ORDER);
			preg_match_all("(\[youtube\\](.*?)\[\/youtube\\])ism", $post['text'], $links2, PREG_SET_ORDER);
			preg_match_all("(\[video\\](.*?)\[\/video\\])ism", $post['text'], $links3, PREG_SET_ORDER);
			preg_match_all("(\[audio\\](.*?)\[\/audio\\])ism", $post['text'], $links4, PREG_SET_ORDER);

			// Add them to the other external links
			$links = array_merge($links, $links1, $links2, $links3, $links4);

			// Are there more than one?
			if (count($links) > 1) {
				// The post will be the type "text", which means a blog post
				unset($post['type']);
				$post['url'] = $plink;
			}

			if (!isset($post['type'])) {
				$post['type'] = "text";
				$post['text'] = trim($body);
			}
		} elseif (isset($post['url']) && ($post['type'] == 'video')) {
			$data = ParseUrl::getSiteinfoCached($post['url'], true);

			if (isset($data['images'][0])) {
				$post['image'] = $data['images'][0]['src'];
			}
		}

		return $post;
	}

	/**
	 * Remove [attachment] BBCode and replaces it with a regular [url]
	 *
	 * @param string  $body
	 * @param boolean $no_link_desc No link description
	 *
	 * @return string with replaced body
	 */
	public static function removeAttachment($body, $no_link_desc = false)
	{
		return preg_replace_callback("/\s*\[attachment (.*)\](.*?)\[\/attachment\]\s*/ism",
			function ($match) use ($no_link_desc) {
				$attach_data = self::getAttachmentData($match[0]);
				if (empty($attach_data['url'])) {
					return $match[0];
				} elseif (empty($attach_data['title']) || $no_link_desc) {
					return "\n[url]" . $attach_data['url'] . "[/url]\n";
				} else {
					return "\n[url=" . $attach_data['url'] . ']' . $attach_data['title'] . "[/url]\n";
				}
		}, $body);
	}

	/**
	 * Converts a BBCode text into plaintext
	 *
	 * @param      $text
	 * @param bool $keep_urls Whether to keep URLs in the resulting plaintext
	 *
	 * @return string
	 */
	public static function toPlaintext($text, $keep_urls = true)
	{
		$naked_text = HTML::toPlaintext(self::convert($text, false, 0, true), 0, !$keep_urls);

		return $naked_text;
	}

	private static function proxyUrl($image, $simplehtml = self::INTERNAL)
	{
		// Only send proxied pictures to API and for internal display
		if (in_array($simplehtml, [self::INTERNAL, self::API])) {
			return ProxyUtils::proxifyUrl($image);
		} else {
			return $image;
		}
	}

	/**
	 * This function changing the visual size (not the real size) of images.
	 * The function does not work for pictures with an alternate text description.
	 * This could only be changed by using some new "img" BBCode format.
	 *
	 * @param string $srctext The body with images
	 * @return string The body with possibly scaled images
	 */
	public static function scaleExternalImages(string $srctext)
	{
		$s = $srctext;

		// Simplify image links
		$s = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $s);

		$matches = null;
		$c = preg_match_all('/\[img.*?\](.*?)\[\/img\]/ism', $s, $matches, PREG_SET_ORDER);
		if ($c) {
			foreach ($matches as $mtch) {
				Logger::log('scale_external_image: ' . $mtch[1]);

				$hostname = str_replace('www.', '', substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3));
				if (stristr($mtch[1], $hostname)) {
					continue;
				}

				$curlResult = DI::httpRequest()->get($mtch[1]);
				if (!$curlResult->isSuccess()) {
					continue;
				}

				$i = $curlResult->getBody();
				$type = $curlResult->getContentType();
				$type = Images::getMimeTypeByData($i, $mtch[1], $type);

				if ($i) {
					$Image = new Image($i, $type);
					if ($Image->isValid()) {
						$orig_width = $Image->getWidth();
						$orig_height = $Image->getHeight();

						if ($orig_width > 640 || $orig_height > 640) {
							$Image->scaleDown(640);
							$new_width = $Image->getWidth();
							$new_height = $Image->getHeight();
							Logger::info('External images scaled', ['orig_width' => $orig_width, 'new_width' => $new_width, 'orig_height' => $orig_height, 'new_height' => $new_height, 'match' => $mtch[0]]);
							$s = str_replace(
								$mtch[0],
								'[img=' . $new_width . 'x' . $new_height. ']' . $mtch[1] . '[/img]'
								. "\n",
								$s
							);
							Logger::info('New string', ['image' => $s]);
						}
					}
				}
			}
		}

		return $s;
	}

	/**
	 * Truncates imported message body string length to max_import_size
	 *
	 * The purpose of this function is to apply system message length limits to
	 * imported messages without including any embedded photos in the length
	 *
	 * @param string $body
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function limitBodySize($body)
	{
		$maxlen = DI::config()->get('config', 'max_import_size', 0);

		// If the length of the body, including the embedded images, is smaller
		// than the maximum, then don't waste time looking for the images
		if ($maxlen && (strlen($body) > $maxlen)) {

			Logger::info('the total body length exceeds the limit', ['maxlen' => $maxlen, 'body_len' => strlen($body)]);

			$orig_body = $body;
			$new_body = '';
			$textlen = 0;

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
			while (($img_st_close !== false) && ($img_end !== false)) {

				$img_st_close++; // make it point to AFTER the closing bracket
				$img_end += $img_start;
				$img_end += strlen('[/img]');

				if (!strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
					// This is an embedded image

					if (($textlen + $img_start) > $maxlen) {
						if ($textlen < $maxlen) {
							Logger::info('the limit happens before an embedded image');
							$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
							$textlen = $maxlen;
						}
					} else {
						$new_body = $new_body . substr($orig_body, 0, $img_start);
						$textlen += $img_start;
					}

					$new_body = $new_body . substr($orig_body, $img_start, $img_end - $img_start);
				} else {

					if (($textlen + $img_end) > $maxlen) {
						if ($textlen < $maxlen) {
							Logger::info('the limit happens before the end of a non-embedded image');
							$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
							$textlen = $maxlen;
						}
					} else {
						$new_body = $new_body . substr($orig_body, 0, $img_end);
						$textlen += $img_end;
					}
				}
				$orig_body = substr($orig_body, $img_end);

				if ($orig_body === false) {
					// in case the body ends on a closing image tag
					$orig_body = '';
				}

				$img_start = strpos($orig_body, '[img');
				$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
				$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
			}

			if (($textlen + strlen($orig_body)) > $maxlen) {
				if ($textlen < $maxlen) {
					Logger::info('the limit happens after the end of the last image');
					$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
				}
			} else {
				Logger::info('the text size with embedded images extracted did not violate the limit');
				$new_body = $new_body . $orig_body;
			}

			return $new_body;
		} else {
			return $body;
		}
	}

	/**
	 * Processes [attachment] tags
	 *
	 * Note: Can produce a [bookmark] tag in the returned string
	 *
	 * @param string  $text
	 * @param integer $simplehtml
	 * @param bool    $tryoembed
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function convertAttachment($text, $simplehtml = self::INTERNAL, $tryoembed = true)
	{
		$data = self::getAttachmentData($text);
		if (empty($data) || empty($data['url'])) {
			return $text;
		}

		if (isset($data['title'])) {
			$data['title'] = strip_tags($data['title']);
			$data['title'] = str_replace(['http://', 'https://'], '', $data['title']);
		} else {
			$data['title'] = null;
		}

		if (((strpos($data['text'], "[img=") !== false) || (strpos($data['text'], "[img]") !== false) || DI::config()->get('system', 'always_show_preview')) && !empty($data['image'])) {
			$data['preview'] = $data['image'];
			$data['image'] = '';
		}

		$return = '';
		try {
			if ($tryoembed && OEmbed::isAllowedURL($data['url'])) {
				$return = OEmbed::getHTML($data['url'], $data['title']);
			} else {
				throw new Exception('OEmbed is disabled for this attachment.');
			}
		} catch (Exception $e) {
			$data['title'] = ($data['title'] ?? '') ?: $data['url'];

			if ($simplehtml != self::CONNECTORS) {
				$return = sprintf('<div class="type-%s">', $data['type']);
			}

			if (!empty($data['title']) && !empty($data['url'])) {
				if (!empty($data['image']) && empty($data['text']) && ($data['type'] == 'photo')) {
					$return .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="" title="%s" class="attachment-image" /></a>', $data['url'], self::proxyUrl($data['image'], $simplehtml), $data['title']);
				} else {
					if (!empty($data['image'])) {
						$return .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="" title="%s" class="attachment-image" /></a><br>', $data['url'], self::proxyUrl($data['image'], $simplehtml), $data['title']);
					} elseif (!empty($data['preview'])) {
						$return .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="" title="%s" class="attachment-preview" /></a><br>', $data['url'], self::proxyUrl($data['preview'], $simplehtml), $data['title']);
					}
					$return .= sprintf('<h4><a href="%s">%s</a></h4>', $data['url'], $data['title']);
				}
			}

			if (!empty($data['description']) && $data['description'] != $data['title']) {
				// Sanitize the HTML by converting it to BBCode
				$bbcode = HTML::toBBCode($data['description']);
				$return .= sprintf('<blockquote>%s</blockquote>', trim(self::convert($bbcode)));
			}

			if (!empty($data['url'])) {
				$return .= sprintf('<sup><a href="%s">%s</a></sup>', $data['url'], parse_url($data['url'], PHP_URL_HOST));
			}

			if ($simplehtml != self::CONNECTORS) {
				$return .= '</div>';
			}
		}

		return trim(($data['text'] ?? '') . ' ' . $return . ' ' . ($data['after'] ?? ''));
	}

	public static function removeShareInformation($Text, $plaintext = false, $nolink = false)
	{
		$data = self::getAttachmentData($Text);

		if (!$data) {
			return $Text;
		} elseif ($nolink) {
			return $data['text'] . ($data['after'] ?? '');
		}

		$title = htmlentities($data['title'] ?? '', ENT_QUOTES, 'UTF-8', false);
		$text = htmlentities($data['text'], ENT_QUOTES, 'UTF-8', false);
		if ($plaintext || (($title != '') && strstr($text, $title))) {
			$data['title'] = $data['url'];
		} elseif (($text != '') && strstr($title, $text)) {
			$data['text'] = $data['title'];
			$data['title'] = $data['url'];
		}

		if (empty($data['text']) && !empty($data['title']) && empty($data['url'])) {
			return $data['title'] . $data['after'];
		}

		// If the link already is included in the post, don't add it again
		if (!empty($data['url']) && strpos($data['text'], $data['url'])) {
			return $data['text'] . $data['after'];
		}

		$text = $data['text'];

		if (!empty($data['url']) && !empty($data['title'])) {
			$text .= "\n[url=" . $data['url'] . ']' . $data['title'] . '[/url]';
		} elseif (!empty($data['url'])) {
			$text .= "\n[url]" . $data['url'] . '[/url]';
		}

		return $text . "\n" . $data['after'];
	}

	/**
	 * Converts [url] BBCodes in a format that looks fine on Mastodon. (callback function)
	 *
	 * @param array $match Array with the matching values
	 * @return string reformatted link including HTML codes
	 */
	private static function convertUrlForActivityPubCallback($match)
	{
		$url = $match[1];

		if (isset($match[2]) && ($match[1] != $match[2])) {
			return $match[0];
		}

		$parts = parse_url($url);
		if (!isset($parts['scheme'])) {
			return $match[0];
		}

		return self::convertUrlForActivityPub($url);
	}

	/**
	 * Converts [url] BBCodes in a format that looks fine on ActivityPub systems.
	 *
	 * @param string $url URL that is about to be reformatted
	 * @return string reformatted link including HTML codes
	 */
	private static function convertUrlForActivityPub($url)
	{
		$html = '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>';
		return sprintf($html, $url, self::getStyledURL($url));
	}

	/**
	 * Converts an URL in a nicer format (without the scheme and possibly shortened)
	 *
	 * @param string $url URL that is about to be reformatted
	 * @return string reformatted link
	 */
	private static function getStyledURL($url)
	{
		$parts = parse_url($url);
		$scheme = $parts['scheme'] . '://';
		$styled_url = str_replace($scheme, '', $url);

		if (strlen($styled_url) > 30) {
			$styled_url = substr($styled_url, 0, 30) . "…";
		}

		return $styled_url;
	}

	/*
	 * [noparse][i]italic[/i][/noparse] turns into
	 * [noparse][ i ]italic[ /i ][/noparse],
	 * to hide them from parser.
	 */
	private static function escapeNoparseCallback($match)
	{
		$whole_match = $match[0];
		$captured = $match[1];
		$spacefied = preg_replace("/\[(.*?)\]/", "[ $1 ]", $captured);
		$new_str = str_replace($captured, $spacefied, $whole_match);
		return $new_str;
	}

	/*
	 * The previously spacefied [noparse][ i ]italic[ /i ][/noparse],
	 * now turns back and the [noparse] tags are trimed
	 * returning [i]italic[/i]
	 */
	private static function unescapeNoparseCallback($match)
	{
		$captured = $match[1];
		$unspacefied = preg_replace("/\[ (.*?)\ ]/", "[$1]", $captured);
		return $unspacefied;
	}

	/**
	 * Returns the bracket character positions of a set of opening and closing BBCode tags, optionally skipping first
	 * occurrences
	 *
	 * @param string $text        Text to search
	 * @param string $name        Tag name
	 * @param int    $occurrences Number of first occurrences to skip
	 * @return boolean|array
	 */
	public static function getTagPosition($text, $name, $occurrences = 0)
	{
		if ($occurrences < 0) {
			$occurrences = 0;
		}

		$start_open = -1;
		for ($i = 0; $i <= $occurrences; $i++) {
			if ($start_open !== false) {
				$start_open = strpos($text, '[' . $name, $start_open + 1); // allow [name= type tags
			}
		}

		if ($start_open === false) {
			return false;
		}

		$start_equal = strpos($text, '=', $start_open);
		$start_close = strpos($text, ']', $start_open);

		if ($start_close === false) {
			return false;
		}

		$start_close++;

		$end_open = strpos($text, '[/' . $name . ']', $start_close);

		if ($end_open === false) {
			return false;
		}

		$res = [
			'start' => [
				'open' => $start_open,
				'close' => $start_close
			],
			'end' => [
				'open' => $end_open,
				'close' => $end_open + strlen('[/' . $name . ']')
			],
		];

		if ($start_equal !== false) {
			$res['start']['equal'] = $start_equal + 1;
		}

		return $res;
	}

	/**
	 * Performs a preg_replace within the boundaries of all named BBCode tags in a text
	 *
	 * @param string $pattern Preg pattern string
	 * @param string $replace Preg replace string
	 * @param string $name    BBCode tag name
	 * @param string $text    Text to search
	 * @return string
	 */
	public static function pregReplaceInTag($pattern, $replace, $name, $text)
	{
		$occurrences = 0;
		$pos = self::getTagPosition($text, $name, $occurrences);
		while ($pos !== false && $occurrences++ < 1000) {
			$start = substr($text, 0, $pos['start']['open']);
			$subject = substr($text, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
			$end = substr($text, $pos['end']['close']);
			if ($end === false) {
				$end = '';
			}

			$subject = preg_replace($pattern, $replace, $subject);
			$text = $start . $subject . $end;

			$pos = self::getTagPosition($text, $name, $occurrences);
		}

		return $text;
	}

	private static function extractImagesFromItemBody($body)
	{
		$saved_image = [];
		$orig_body = $body;
		$new_body = '';

		$cnt = 0;
		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		while (($img_st_close !== false) && ($img_end !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$img_end += $img_start;

			if (!strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
				// This is an embedded image
				$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
				$new_body = $new_body . substr($orig_body, 0, $img_start) . '[$#saved_image' . $cnt . '#$]';

				$cnt++;
			} else {
				$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));
			}

			$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

			if ($orig_body === false) {
				// in case the body ends on a closing image tag
				$orig_body = '';
			}

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		}

		$new_body = $new_body . $orig_body;

		return ['body' => $new_body, 'images' => $saved_image];
	}

	private static function interpolateSavedImagesIntoItemBody($body, array $images)
	{
		$newbody = $body;

		$cnt = 0;
		foreach ($images as $image) {
			// We're depending on the property of 'foreach' (specified on the PHP website) that
			// it loops over the array starting from the first element and going sequentially
			// to the last element
			$newbody = str_replace('[$#saved_image' . $cnt . '#$]',
				'<img src="' . self::proxyUrl($image) . '" alt="' . DI::l10n()->t('Image/photo') . '" />', $newbody);
			$cnt++;
		}

		return $newbody;
	}

	/**
	 * This function converts a [share] block to text according to a provided callback function whose signature is:
	 *
	 * function(array $attributes, array $author_contact, string $content, boolean $is_quote_share): string
	 *
	 * Where:
	 * - $attributes is an array of attributes of the [share] block itself. Missing keys will be completed by the contact
	 * data lookup
	 * - $author_contact is a contact record array
	 * - $content is the inner content of the [share] block
	 * - $is_quote_share indicates whether there's any content before the [share] block
	 * - Return value is the string that should replace the [share] block in the provided text
	 *
	 * This function is intended to be used by addon connector to format a share block like the target network is expecting it.
	 *
	 * @param  string   $text     A BBCode string
	 * @param  callable $callback
	 * @return string The BBCode string with all [share] blocks replaced
	 */
	public static function convertShare($text, callable $callback)
	{
		$return = preg_replace_callback(
			"/(.*?)\[share(.*?)\](.*)\[\/share\]/ism",
			function ($match) use ($callback) {
				$attribute_string = $match[2];
				$attributes = [];
				foreach (['author', 'profile', 'avatar', 'link', 'posted', 'guid'] as $field) {
					preg_match("/$field=(['\"])(.+?)\\1/ism", $attribute_string, $matches);
					$attributes[$field] = html_entity_decode($matches[2] ?? '', ENT_QUOTES, 'UTF-8');
				}

				$author_contact = Contact::getByURL($attributes['profile'], false, ['url', 'addr', 'name', 'micro']);
				$author_contact['url'] = ($author_contact['url'] ?? $attributes['profile']);
				$author_contact['addr'] = ($author_contact['addr'] ?? '') ?: Protocol::getAddrFromProfileUrl($attributes['profile']);

				$attributes['author']   = ($author_contact['name']  ?? '') ?: $attributes['author'];
				$attributes['avatar']   = ($author_contact['micro'] ?? '') ?: $attributes['avatar'];
				$attributes['profile']  = ($author_contact['url']   ?? '') ?: $attributes['profile'];

				if ($attributes['avatar']) {
					$attributes['avatar'] = ProxyUtils::proxifyUrl($attributes['avatar'], false, ProxyUtils::SIZE_THUMB);
				}

				$content = preg_replace(Strings::autoLinkRegEx(), '<a href="$1">$1</a>', $match[3]);

				return $match[1] . $callback($attributes, $author_contact, $content, trim($match[1]) != '');
			},
			$text
		);

		return $return;
	}

	/**
	 * Default [share] tag conversion callback
	 *
	 * Note: Can produce a [bookmark] tag in the output
	 *
	 * @see BBCode::convertShare()
	 * @param array   $attributes     [share] block attribute values
	 * @param array   $author_contact Contact row of the shared author
	 * @param string  $content        Inner content of the [share] block
	 * @param boolean $is_quote_share Whether there is content before the [share] block
	 * @param integer $simplehtml     Mysterious integer value depending on the target network/formatting style
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function convertShareCallback(array $attributes, array $author_contact, $content, $is_quote_share, $simplehtml)
	{
		$mention = Protocol::formatMention($attributes['profile'], $attributes['author']);

		switch ($simplehtml) {
			case self::API:
				$text = ($is_quote_share? '<br>' : '') . '<p>' . html_entity_decode('&#x2672; ', ENT_QUOTES, 'UTF-8') . ' ' . $author_contact['addr'] . ': </p>' . "\n" . $content;
				break;
			case self::DIASPORA:
				if (stripos(Strings::normaliseLink($attributes['link']), 'http://twitter.com/') === 0) {
					$text = ($is_quote_share? '<hr />' : '') . '<p><a href="' . $attributes['link'] . '">' . $attributes['link'] . '</a></p>' . "\n";
				} else {
					$headline = '<p><b>♲ <a href="' . $attributes['profile'] . '">' . $attributes['author'] . '</a>:</b></p>' . "\n";

					if (!empty($attributes['posted']) && !empty($attributes['link'])) {
						$headline = '<p><b>♲ <a href="' . $attributes['profile'] . '">' . $attributes['author'] . '</a></b> - <a href="' . $attributes['link'] . '">' . $attributes['posted'] . ' GMT</a></p>' . "\n";
					}

					$text = ($is_quote_share? '<hr />' : '') . $headline . '<blockquote>' . trim($content) . '</blockquote>' . "\n";

					if (empty($attributes['posted']) && !empty($attributes['link'])) {
						$text .= '<p><a href="' . $attributes['link'] . '">[Source]</a></p>' . "\n";
					}
				}

				break;
			case self::CONNECTORS:
				$headline = '<p><b>' . html_entity_decode('&#x2672; ', ENT_QUOTES, 'UTF-8');
				$headline .= DI::l10n()->t('<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s', $attributes['link'], $mention, $attributes['posted']);
				$headline .= ':</b></p>' . "\n";

				$text = ($is_quote_share? '<hr />' : '') . $headline . '<blockquote class="shared_content">' . trim($content) . '</blockquote>' . "\n";

				break;
			case self::OSTATUS:
				$text = ($is_quote_share? '<br>' : '') . '<p>' . html_entity_decode('&#x2672; ', ENT_QUOTES, 'UTF-8') . ' @' . $author_contact['addr'] . ': ' . $content . '</p>' . "\n";
				break;
			case self::ACTIVITYPUB:
				$author = '@<span class="vcard"><a href="' . $author_contact['url'] . '" class="url u-url mention" title="' . $author_contact['addr'] . '"><span class="fn nickname mention">' . $author_contact['addr'] . '</span></a>:</span>';
				$text = '<div><a href="' . $attributes['link'] . '">' . html_entity_decode('&#x2672;', ENT_QUOTES, 'UTF-8') . '</a> ' . $author . '<blockquote>' . $content . '</blockquote></div>' . "\n";
				break;
			default:
				$text = ($is_quote_share? "\n" : '');

				$contact = Contact::getByURL($attributes['profile'], false, ['network']);
				$network = $contact['network'] ?? Protocol::PHANTOM;

				$tpl = Renderer::getMarkupTemplate('shared_content.tpl');
				$text .= Renderer::replaceMacros($tpl, [
					'$profile'      => $attributes['profile'],
					'$avatar'       => $attributes['avatar'],
					'$author'       => $attributes['author'],
					'$link'         => $attributes['link'],
					'$link_title'   => DI::l10n()->t('link to source'),
					'$posted'       => $attributes['posted'],
					'$guid'         => $attributes['guid'],
					'$network_name' => ContactSelector::networkToName($network, $attributes['profile']),
					'$network_icon' => ContactSelector::networkToIcon($network, $attributes['profile']),
					'$content'      => self::setMentions(trim($content), 0, $network),
				]);
				break;
		}

		return $text;
	}

	private static function removePictureLinksCallback($match)
	{
		$cache_key = 'remove:' . $match[1];
		$text = DI::cache()->get($cache_key);

		if (is_null($text)) {
			$a = DI::app();

			$stamp1 = microtime(true);

			$ch = @curl_init($match[1]);
			@curl_setopt($ch, CURLOPT_NOBODY, true);
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			@curl_setopt($ch, CURLOPT_USERAGENT, DI::httpRequest()->getUserAgent());
			@curl_exec($ch);
			$curl_info = @curl_getinfo($ch);

			DI::profiler()->saveTimestamp($stamp1, "network");

			if (substr($curl_info['content_type'], 0, 6) == 'image/') {
				$text = "[url=" . $match[1] . ']' . $match[1] . "[/url]";
			} else {
				$text = "[url=" . $match[2] . ']' . $match[2] . "[/url]";

				// if its not a picture then look if its a page that contains a picture link
				$body = DI::httpRequest()->fetch($match[1]);

				$doc = new DOMDocument();
				@$doc->loadHTML($body);
				$xpath = new DOMXPath($doc);
				$list = $xpath->query("//meta[@name]");
				foreach ($list as $node) {
					$attr = [];

					if ($node->attributes->length) {
						foreach ($node->attributes as $attribute) {
							$attr[$attribute->name] = $attribute->value;
						}
					}

					if (strtolower($attr['name']) == 'twitter:image') {
						$text = '[url=' . $attr['content'] . ']' . $attr['content'] . '[/url]';
					}
				}
			}
			DI::cache()->set($cache_key, $text);
		}

		return $text;
	}

	private static function expandLinksCallback($match)
	{
		if (($match[3] == '') || ($match[2] == $match[3]) || stristr($match[2], $match[3])) {
			return ($match[1] . "[url]" . $match[2] . "[/url]");
		} else {
			return ($match[1] . $match[3] . " [url]" . $match[2] . "[/url]");
		}
	}

	private static function cleanPictureLinksCallback($match)
	{
		$a = DI::app();

		// When the picture link is the own photo path then we can avoid fetching the link
		$own_photo_url = preg_quote(Strings::normaliseLink(DI::baseUrl()->get()) . '/photos/');
		if (preg_match('|' . $own_photo_url . '.*?/image/|', Strings::normaliseLink($match[1]))) {
			if (!empty($match[3])) {
				$text = '[img=' . str_replace('-1.', '-0.', $match[2]) . ']' . $match[3] . '[/img]';
			} else {
				$text = '[img]' . str_replace('-1.', '-0.', $match[2]) . '[/img]';
			}
			return $text;
		}

		$cache_key = 'clean:' . $match[1];
		$text = DI::cache()->get($cache_key);
		if (!is_null($text)) {
			return $text;
		}

		// Only fetch the header, not the content
		$stamp1 = microtime(true);

		$ch = @curl_init($match[1]);
		@curl_setopt($ch, CURLOPT_NOBODY, true);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_USERAGENT, DI::httpRequest()->getUserAgent());
		@curl_exec($ch);
		$curl_info = @curl_getinfo($ch);

		DI::profiler()->saveTimestamp($stamp1, "network");

		// if its a link to a picture then embed this picture
		if (substr($curl_info['content_type'], 0, 6) == 'image/') {
			$text = '[img]' . $match[1] . '[/img]';
		} else {
			if (!empty($match[3])) {
				$text = '[img=' . $match[2] . ']' . $match[3] . '[/img]';
			} else {
				$text = '[img]' . $match[2] . '[/img]';
			}

			// if its not a picture then look if its a page that contains a picture link
			$body = DI::httpRequest()->fetch($match[1]);

			$doc = new DOMDocument();
			@$doc->loadHTML($body);
			$xpath = new DOMXPath($doc);
			$list = $xpath->query("//meta[@name]");
			foreach ($list as $node) {
				$attr = [];
				if ($node->attributes->length) {
					foreach ($node->attributes as $attribute) {
						$attr[$attribute->name] = $attribute->value;
					}
				}

				if (strtolower($attr['name']) == "twitter:image") {
					if (!empty($match[3])) {
						$text = "[img=" . $attr['content'] . "]" . $match[3] . "[/img]";
					} else {
						$text = "[img]" . $attr['content'] . "[/img]";
					}
				}
			}
		}
		DI::cache()->set($cache_key, $text);

		return $text;
	}

	public static function cleanPictureLinks($text)
	{
		$return = preg_replace_callback("&\[url=([^\[\]]*)\]\[img=(.*)\](.*)\[\/img\]\[\/url\]&Usi", 'self::cleanPictureLinksCallback', $text);
		$return = preg_replace_callback("&\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]&Usi", 'self::cleanPictureLinksCallback', $return);
		return $return;
	}

	public static function removeLinks(string $bbcode)
	{
		$bbcode = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", ' $1 ', $bbcode);
		$bbcode = preg_replace("/\[img.*?\[\/img\]/ism", ' ', $bbcode);

		$bbcode = preg_replace('/[@!#]\[url\=.*?\].*?\[\/url\]/ism', '', $bbcode);
		$bbcode = preg_replace("/\[url=[^\[\]]*\](.*)\[\/url\]/Usi", ' $1 ', $bbcode);
		$bbcode = preg_replace('/[@!#]?\[url.*?\[\/url\]/ism', '', $bbcode);
		return $bbcode;
	}

	/**
	 * Converts a BBCode message to HTML message
	 *
	 * BBcode 2 HTML was written by WAY2WEB.net
	 * extended to work with Mistpark/Friendica - Mike Macgirvin
	 *
	 * Simple HTML values meaning:
	 * - 0: Friendica display
	 * - 1: Unused
	 * - 2: Used for Windows Phone push, Friendica API
	 * - 3: Used before converting to Markdown in bb2diaspora.php
	 * - 4: Used for WordPress, Libertree (before Markdown), pump.io and tumblr
	 * - 5: Unused
	 * - 6: Unused
	 * - 7: Used for dfrn, OStatus
	 * - 8: Used for Twitter, WP backlink text setting
	 * - 9: ActivityPub
	 *
	 * @param string $text
	 * @param bool   $try_oembed
	 * @param int    $simple_html
	 * @param bool   $for_plaintext
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function convert(string $text = null, $try_oembed = true, $simple_html = self::INTERNAL, $for_plaintext = false)
	{
		// Accounting for null default column values
		if (is_null($text) || $text === '') {
			return '';
		}

		Hook::callAll('bbcode', $text);

		$a = DI::app();

		$text = self::performWithEscapedTags($text, ['code'], function ($text) use ($try_oembed, $simple_html, $for_plaintext, $a) {
			$text = self::performWithEscapedTags($text, ['noparse', 'nobb', 'pre'], function ($text) use ($try_oembed, $simple_html, $for_plaintext, $a) {
				/*
				 * preg_match_callback function to replace potential Oembed tags with Oembed content
				 *
				 * $match[0] = [tag]$url[/tag] or [tag=$url]$title[/tag]
				 * $match[1] = $url
				 * $match[2] = $title or absent
				 */
				$try_oembed_callback = function ($match)
				{
					$url = $match[1];
					$title = $match[2] ?? null;

					try {
						$return = OEmbed::getHTML($url, $title);
					} catch (Exception $ex) {
						$return = $match[0];
					}

					return $return;
				};

				// Remove the abstract element. It is a non visible element.
				$text = self::stripAbstract($text);

				// Line ending normalisation
				$text = str_replace("\r\n", "\n", $text);

				// Move new lines outside of tags
				$text = preg_replace("#\[(\w*)](\n*)#ism", '$2[$1]', $text);
				$text = preg_replace("#(\n*)\[/(\w*)]#ism", '[/$2]$1', $text);

				// Extract the private images which use data urls since preg has issues with
				// large data sizes. Stash them away while we do bbcode conversion, and then put them back
				// in after we've done all the regex matching. We cannot use any preg functions to do this.

				$extracted = self::extractImagesFromItemBody($text);
				$text = $extracted['body'];
				$saved_image = $extracted['images'];

				// If we find any event code, turn it into an event.
				// After we're finished processing the bbcode we'll
				// replace all of the event code with a reformatted version.

				$ev = Event::fromBBCode($text);

				// Replace any html brackets with HTML Entities to prevent executing HTML or script
				// Don't use strip_tags here because it breaks [url] search by replacing & with amp

				$text = str_replace("<", "&lt;", $text);
				$text = str_replace(">", "&gt;", $text);

				// remove some newlines before the general conversion
				$text = preg_replace("/\s?\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "[share$1]$2[/share]", $text);
				$text = preg_replace("/\s?\[quote(.*?)\]\s?(.*?)\s?\[\/quote\]\s?/ism", "[quote$1]$2[/quote]", $text);

				// when the content is meant exporting to other systems then remove the avatar picture since this doesn't really look good on these systems
				if (!$try_oembed) {
					$text = preg_replace("/\[share(.*?)avatar\s?=\s?'.*?'\s?(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "\n[share$1$2]$3[/share]", $text);
				}

				// Remove linefeeds inside of the table elements. See issue #6799
				$search = ["\n[th]", "[th]\n", " [th]", "\n[/th]", "[/th]\n", "[/th] ",
					"\n[td]", "[td]\n", " [td]", "\n[/td]", "[/td]\n", "[/td] ",
					"\n[tr]", "[tr]\n", " [tr]", "[tr] ", "\n[/tr]", "[/tr]\n", " [/tr]", "[/tr] ",
					"[table]\n", "[table] ", " [table]", "\n[/table]", " [/table]", "[/table] "];
				$replace = ["[th]", "[th]", "[th]", "[/th]", "[/th]", "[/th]",
					"[td]", "[td]", "[td]", "[/td]", "[/td]", "[/td]",
					"[tr]", "[tr]", "[tr]", "[tr]", "[/tr]", "[/tr]", "[/tr]", "[/tr]",
					"[table]", "[table]", "[table]", "[/table]", "[/table]", "[/table]"];
				do {
					$oldtext = $text;
					$text = str_replace($search, $replace, $text);
				} while ($oldtext != $text);

				// Replace these here only once
				$search = ["\n[table]", "[/table]\n"];
				$replace = ["[table]", "[/table]"];
				$text = str_replace($search, $replace, $text);

				// Trim new lines regardless of the system.remove_multiplicated_lines config value
				$text = trim($text, "\n");

				// removing multiplicated newlines
				if (DI::config()->get('system', 'remove_multiplicated_lines')) {
					$search = ["\n\n\n", "\n ", " \n", "[/quote]\n\n", "\n[/quote]", "[/li]\n", "\n[li]", "\n[*]", "\n[ul]", "[/ul]\n", "\n\n[share ", "[/attachment]\n",
							"\n[h1]", "[/h1]\n", "\n[h2]", "[/h2]\n", "\n[h3]", "[/h3]\n", "\n[h4]", "[/h4]\n", "\n[h5]", "[/h5]\n", "\n[h6]", "[/h6]\n"];
					$replace = ["\n\n", "\n", "\n", "[/quote]\n", "[/quote]", "[/li]", "[li]", "[*]", "[ul]", "[/ul]", "\n[share ", "[/attachment]",
							"[h1]", "[/h1]", "[h2]", "[/h2]", "[h3]", "[/h3]", "[h4]", "[/h4]", "[h5]", "[/h5]", "[h6]", "[/h6]"];
					do {
						$oldtext = $text;
						$text = str_replace($search, $replace, $text);
					} while ($oldtext != $text);
				}

				// Add HTML new lines
				$text = str_replace("\n", '<br>', $text);

				/// @todo Have a closer look at the different html modes
				// Handle attached links or videos
				if ($simple_html == self::ACTIVITYPUB) {
					$text = self::removeAttachment($text);
				} elseif (!in_array($simple_html, [self::INTERNAL, self::CONNECTORS])) {
					$text = self::removeAttachment($text, true);
				} else {
					$text = self::convertAttachment($text, $simple_html, $try_oembed);
				}

				$nosmile = strpos($text, '[nosmile]') !== false;
				$text = str_replace('[nosmile]', '', $text);

				// Replace non graphical smilies for external posts
				if (!$nosmile && !$for_plaintext) {
					$text = self::performWithEscapedTags($text, ['img'], function ($text) {
						return Smilies::replace($text);
					});
				}

				// leave open the posibility of [map=something]
				// this is replaced in Item::prepareBody() which has knowledge of the item location
				if (strpos($text, '[/map]') !== false) {
					$text = preg_replace_callback(
						"/\[map\](.*?)\[\/map\]/ism",
						function ($match) use ($simple_html) {
							return str_replace($match[0], '<p class="map">' . Map::byLocation($match[1], $simple_html) . '</p>', $match[0]);
						},
						$text
					);
				}

				if (strpos($text, '[map=') !== false) {
					$text = preg_replace_callback(
						"/\[map=(.*?)\]/ism",
						function ($match) use ($simple_html) {
							return str_replace($match[0], '<p class="map">' . Map::byCoordinates(str_replace('/', ' ', $match[1]), $simple_html) . '</p>', $match[0]);
						},
						$text
					);
				}

				if (strpos($text, '[map]') !== false) {
					$text = preg_replace("/\[map\]/", '<p class="map"></p>', $text);
				}

				// Check for headers
				$text = preg_replace("(\[h1\](.*?)\[\/h1\])ism", '<h1>$1</h1>', $text);
				$text = preg_replace("(\[h2\](.*?)\[\/h2\])ism", '<h2>$1</h2>', $text);
				$text = preg_replace("(\[h3\](.*?)\[\/h3\])ism", '<h3>$1</h3>', $text);
				$text = preg_replace("(\[h4\](.*?)\[\/h4\])ism", '<h4>$1</h4>', $text);
				$text = preg_replace("(\[h5\](.*?)\[\/h5\])ism", '<h5>$1</h5>', $text);
				$text = preg_replace("(\[h6\](.*?)\[\/h6\])ism", '<h6>$1</h6>', $text);

				// Check for paragraph
				$text = preg_replace("(\[p\](.*?)\[\/p\])ism", '<p>$1</p>', $text);

				// Check for bold text
				$text = preg_replace("(\[b\](.*?)\[\/b\])ism", '<strong>$1</strong>', $text);

				// Check for Italics text
				$text = preg_replace("(\[i\](.*?)\[\/i\])ism", '<em>$1</em>', $text);

				// Check for Underline text
				$text = preg_replace("(\[u\](.*?)\[\/u\])ism", '<u>$1</u>', $text);

				// Check for strike-through text
				$text = preg_replace("(\[s\](.*?)\[\/s\])ism", '<s>$1</s>', $text);

				// Check for over-line text
				$text = preg_replace("(\[o\](.*?)\[\/o\])ism", '<span class="overline">$1</span>', $text);

				// Check for colored text
				$text = preg_replace("(\[color=(.*?)\](.*?)\[\/color\])ism", "<span style=\"color: $1;\">$2</span>", $text);

				// Check for sized text
				// [size=50] --> font-size: 50px (with the unit).
				if ($simple_html != self::DIASPORA) {
					$text = preg_replace("(\[size=(\d*?)\](.*?)\[\/size\])ism", '<span style="font-size:$1px;line-height:normal;">$2</span>', $text);
					$text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])ism", '<span style="font-size:$1;line-height:normal;">$2</span>', $text);
				} else {
					// Issue 2199: Diaspora doesn't interpret the construct above, nor the <small> or <big> element
					$text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])ism", "$2", $text);
				}


				// Check for centered text
				$text = preg_replace("(\[center\](.*?)\[\/center\])ism", '<div style="text-align:center;">$1</div>', $text);

				// Check for list text
				$text = str_replace("[*]", "<li>", $text);

				// Check for style sheet commands
				$text = preg_replace("(\[style=(.*?)\](.*?)\[\/style\])ism", '<span style="$1">$2</span>', $text);

				// Check for CSS classes
				$text = preg_replace("(\[class=(.*?)\](.*?)\[\/class\])ism", '<span class="$1">$2</span>', $text);

				// handle nested lists
				$endlessloop = 0;

				while ((((strpos($text, "[/list]") !== false) && (strpos($text, "[list") !== false)) ||
						((strpos($text, "[/ol]") !== false) && (strpos($text, "[ol]") !== false)) ||
						((strpos($text, "[/ul]") !== false) && (strpos($text, "[ul]") !== false)) ||
						((strpos($text, "[/li]") !== false) && (strpos($text, "[li]") !== false))) && (++$endlessloop < 20)) {
					$text = preg_replace("/\[list\](.*?)\[\/list\]/ism", '<ul class="listbullet" style="list-style-type: circle;">$1</ul>', $text);
					$text = preg_replace("/\[list=\](.*?)\[\/list\]/ism", '<ul class="listnone" style="list-style-type: none;">$1</ul>', $text);
					$text = preg_replace("/\[list=1\](.*?)\[\/list\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>', $text);
					$text = preg_replace("/\[list=((?-i)i)\](.*?)\[\/list\]/ism", '<ul class="listlowerroman" style="list-style-type: lower-roman;">$2</ul>', $text);
					$text = preg_replace("/\[list=((?-i)I)\](.*?)\[\/list\]/ism", '<ul class="listupperroman" style="list-style-type: upper-roman;">$2</ul>', $text);
					$text = preg_replace("/\[list=((?-i)a)\](.*?)\[\/list\]/ism", '<ul class="listloweralpha" style="list-style-type: lower-alpha;">$2</ul>', $text);
					$text = preg_replace("/\[list=((?-i)A)\](.*?)\[\/list\]/ism", '<ul class="listupperalpha" style="list-style-type: upper-alpha;">$2</ul>', $text);
					$text = preg_replace("/\[ul\](.*?)\[\/ul\]/ism", '<ul class="listbullet" style="list-style-type: circle;">$1</ul>', $text);
					$text = preg_replace("/\[ol\](.*?)\[\/ol\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>', $text);
					$text = preg_replace("/\[li\](.*?)\[\/li\]/ism", '<li>$1</li>', $text);
				}

				$text = preg_replace("/\[th\](.*?)\[\/th\]/sm", '<th>$1</th>', $text);
				$text = preg_replace("/\[td\](.*?)\[\/td\]/sm", '<td>$1</td>', $text);
				$text = preg_replace("/\[tr\](.*?)\[\/tr\]/sm", '<tr>$1</tr>', $text);
				$text = preg_replace("/\[table\](.*?)\[\/table\]/sm", '<table>$1</table>', $text);

				$text = preg_replace("/\[table border=1\](.*?)\[\/table\]/sm", '<table border="1" >$1</table>', $text);
				$text = preg_replace("/\[table border=0\](.*?)\[\/table\]/sm", '<table border="0" >$1</table>', $text);

				$text = str_replace('[hr]', '<hr />', $text);

				if (!$for_plaintext) {
					$text = self::performWithEscapedTags($text, ['url', 'img', 'audio', 'video', 'youtube', 'vimeo', 'share', 'attachment', 'iframe', 'bookmark'], function ($text) {
						return preg_replace(Strings::autoLinkRegEx(), '[url]$1[/url]', $text);
					});
				}

				// Check for font change text
				$text = preg_replace("/\[font=(.*?)\](.*?)\[\/font\]/sm", "<span style=\"font-family: $1;\">$2</span>", $text);

				// Declare the format for [spoiler] layout
				$SpoilerLayout = '<details class="spoiler"><summary>' . DI::l10n()->t('Click to open/close') . '</summary>$1</details>';

				// Check for [spoiler] text
				// handle nested quotes
				$endlessloop = 0;
				while ((strpos($text, "[/spoiler]") !== false) && (strpos($text, "[spoiler]") !== false) && (++$endlessloop < 20)) {
					$text = preg_replace("/\[spoiler\](.*?)\[\/spoiler\]/ism", $SpoilerLayout, $text);
				}

				// Check for [spoiler=Title] text

				// handle nested quotes
				$endlessloop = 0;
				while ((strpos($text, "[/spoiler]")!== false)  && (strpos($text, "[spoiler=") !== false) && (++$endlessloop < 20)) {
					$text = preg_replace("/\[spoiler=[\"\']*(.*?)[\"\']*\](.*?)\[\/spoiler\]/ism",
						'<details class="spoiler"><summary>$1</summary>$2</details>',
						$text);
				}

				// Declare the format for [quote] layout
				$QuoteLayout = '<blockquote>$1</blockquote>';

				// Check for [quote] text
				// handle nested quotes
				$endlessloop = 0;
				while ((strpos($text, "[/quote]") !== false) && (strpos($text, "[quote]") !== false) && (++$endlessloop < 20)) {
					$text = preg_replace("/\[quote\](.*?)\[\/quote\]/ism", "$QuoteLayout", $text);
				}

				// Check for [quote=Author] text

				$t_wrote = DI::l10n()->t('$1 wrote:');

				// handle nested quotes
				$endlessloop = 0;
				while ((strpos($text, "[/quote]")!== false)  && (strpos($text, "[quote=") !== false) && (++$endlessloop < 20)) {
					$text = preg_replace("/\[quote=[\"\']*(.*?)[\"\']*\](.*?)\[\/quote\]/ism",
						"<p><strong class=".'"author"'.">" . $t_wrote . "</strong></p><blockquote>$2</blockquote>",
						$text);
				}


				// [img=widthxheight]image source[/img]
				$text = preg_replace_callback(
					"/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism",
					function ($matches) use ($simple_html) {
						if (strpos($matches[3], "data:image/") === 0) {
							return $matches[0];
						}

						$matches[3] = self::proxyUrl($matches[3], $simple_html);
						return "[img=" . $matches[1] . "x" . $matches[2] . "]" . $matches[3] . "[/img]";
					},
					$text
				);

				$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="width: $1px;" >', $text);
				$text = preg_replace("/\[zmg\=([0-9]*)x([0-9]*)\](.*?)\[\/zmg\]/ism", '<img class="zrl" src="$3" style="width: $1px;" >', $text);

				$text = preg_replace_callback("/\[img\=(.*?)\](.*?)\[\/img\]/ism",
					function ($matches) use ($simple_html) {
						$matches[1] = self::proxyUrl($matches[1], $simple_html);
						$matches[2] = htmlspecialchars($matches[2], ENT_COMPAT);
						return '<img src="' . $matches[1] . '" alt="' . $matches[2] . '" title="' . $matches[2] . '">';
					},
					$text);

				// Images
				// [img]pathtoimage[/img]
				$text = preg_replace_callback(
					"/\[img\](.*?)\[\/img\]/ism",
					function ($matches) use ($simple_html) {
						if (strpos($matches[1], "data:image/") === 0) {
							return $matches[0];
						}

						$matches[1] = self::proxyUrl($matches[1], $simple_html);
						return "[img]" . $matches[1] . "[/img]";
					},
					$text
				);

				$text = preg_replace("/\[img\](.*?)\[\/img\]/ism", '<img src="$1" alt="' . DI::l10n()->t('Image/photo') . '" />', $text);
				$text = preg_replace("/\[zmg\](.*?)\[\/zmg\]/ism", '<img src="$1" alt="' . DI::l10n()->t('Image/photo') . '" />', $text);

				$text = preg_replace("/\[crypt\](.*?)\[\/crypt\]/ism", '<br><img src="' .DI::baseUrl() . '/images/lock_icon.gif" alt="' . DI::l10n()->t('Encrypted content') . '" title="' . DI::l10n()->t('Encrypted content') . '" /><br>', $text);
				$text = preg_replace("/\[crypt(.*?)\](.*?)\[\/crypt\]/ism", '<br><img src="' .DI::baseUrl() . '/images/lock_icon.gif" alt="' . DI::l10n()->t('Encrypted content') . '" title="' . '$1' . ' ' . DI::l10n()->t('Encrypted content') . '" /><br>', $text);
				//$Text = preg_replace("/\[crypt=(.*?)\](.*?)\[\/crypt\]/ism", '<br><img src="' .DI::baseUrl() . '/images/lock_icon.gif" alt="' . DI::l10n()->t('Encrypted content') . '" title="' . '$1' . ' ' . DI::l10n()->t('Encrypted content') . '" /><br>', $Text);

				// Simplify "video" element
				$text = preg_replace('(\[video.*?\ssrc\s?=\s?([^\s\]]+).*?\].*?\[/video\])ism', '[video]$1[/video]', $text);

				if ($try_oembed) {
					// html5 video and audio
					$text = preg_replace("/\[video\](.*?\.(ogg|ogv|oga|ogm|webm|mp4).*?)\[\/video\]/ism",
						'<video src="$1" controls width="' . $a->videowidth . '" height="' . $a->videoheight . '" loop="true"><a href="$1">$1</a></video>', $text);

					$text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", $try_oembed_callback, $text);
					$text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", $try_oembed_callback, $text);

					$text = preg_replace("/\[video\](.*?)\[\/video\]/ism",
						'<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
					$text = preg_replace("/\[audio\](.*?)\[\/audio\]/ism", '<audio src="$1" controls><a href="$1">$1</a></audio>', $text);
				} else {
					$text = preg_replace("/\[video\](.*?)\[\/video\]/ism",
						'<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
					$text = preg_replace("/\[audio\](.*?)\[\/audio\]/ism",
						'<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
				}

				// Backward compatibility, [iframe] support has been removed in version 2020.12
				$text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/ism", '<a href="$1">$1</a>', $text);

				// Youtube extensions
				if ($try_oembed) {
					$text = preg_replace_callback("/\[youtube\](https?:\/\/www.youtube.com\/watch\?v\=.*?)\[\/youtube\]/ism", $try_oembed_callback, $text);
					$text = preg_replace_callback("/\[youtube\](www.youtube.com\/watch\?v\=.*?)\[\/youtube\]/ism", $try_oembed_callback, $text);
					$text = preg_replace_callback("/\[youtube\](https?:\/\/youtu.be\/.*?)\[\/youtube\]/ism", $try_oembed_callback, $text);
				}

				$text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);
				$text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/embed\/(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);
				$text = preg_replace("/\[youtube\]https?:\/\/youtu.be\/(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);

				if ($try_oembed) {
					$text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="https://www.youtube.com/embed/$1" frameborder="0" ></iframe>', $text);
				} else {
					$text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism",
						'<a href="https://www.youtube.com/watch?v=$1" target="_blank" rel="noopener noreferrer">https://www.youtube.com/watch?v=$1</a>', $text);
				}

				if ($try_oembed) {
					$text = preg_replace_callback("/\[vimeo\](https?:\/\/player.vimeo.com\/video\/[0-9]+).*?\[\/vimeo\]/ism", $try_oembed_callback, $text);
					$text = preg_replace_callback("/\[vimeo\](https?:\/\/vimeo.com\/[0-9]+).*?\[\/vimeo\]/ism", $try_oembed_callback, $text);
				}

				$text = preg_replace("/\[vimeo\]https?:\/\/player.vimeo.com\/video\/([0-9]+)(.*?)\[\/vimeo\]/ism", '[vimeo]$1[/vimeo]', $text);
				$text = preg_replace("/\[vimeo\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/vimeo\]/ism", '[vimeo]$1[/vimeo]', $text);

				if ($try_oembed) {
					$text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="https://player.vimeo.com/video/$1" frameborder="0" ></iframe>', $text);
				} else {
					$text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism",
						'<a href="https://vimeo.com/$1" target="_blank" rel="noopener noreferrer">https://vimeo.com/$1</a>', $text);
				}

				// oembed tag
				$text = OEmbed::BBCode2HTML($text);

				// Avoid triple linefeeds through oembed
				$text = str_replace("<br style='clear:left'></span><br><br>", "<br style='clear:left'></span><br>", $text);

				// If we found an event earlier, strip out all the event code and replace with a reformatted version.
				// Replace the event-start section with the entire formatted event. The other bbcode is stripped.
				// Summary (e.g. title) is required, earlier revisions only required description (in addition to
				// start which is always required). Allow desc with a missing summary for compatibility.

				if ((!empty($ev['desc']) || !empty($ev['summary'])) && !empty($ev['start'])) {
					$sub = Event::getHTML($ev, $simple_html);

					$text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/ism", '', $text);
					$text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/ism", '', $text);
					$text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/ism", $sub, $text);
					$text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/ism", '', $text);
					$text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/ism", '', $text);
					$text = preg_replace("/\[event\-adjust\](.*?)\[\/event\-adjust\]/ism", '', $text);
					$text = preg_replace("/\[event\-id\](.*?)\[\/event\-id\]/ism", '', $text);
				}

				if (!$for_plaintext && DI::config()->get('system', 'big_emojis') && ($simple_html != self::DIASPORA)) {
					$conv = html_entity_decode(str_replace([' ', "\n", "\r"], '', $text));
					// Emojis are always 4 byte Unicode characters
					if (!empty($conv) && (strlen($conv) / mb_strlen($conv) == 4)) {
						$text = '<span style="font-size: xx-large; line-height: normal;">' . $text . '</span>';
					}
				}

				if (!$for_plaintext) {
					if (in_array($simple_html, [self::OSTATUS, self::ACTIVITYPUB])) {
						$text = preg_replace_callback("/\[url\](.*?)\[\/url\]/ism", 'self::convertUrlForActivityPubCallback', $text);
						$text = preg_replace_callback("/\[url\=(.*?)\](.*?)\[\/url\]/ism", 'self::convertUrlForActivityPubCallback', $text);
					}
				} else {
					$text = preg_replace("(\[url\](.*?)\[\/url\])ism", " $1 ", $text);
					$text = preg_replace_callback("&\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]&Usi", 'self::removePictureLinksCallback', $text);
				}

				// Remove all hashtag addresses
				if ($simple_html && !in_array($simple_html, [self::DIASPORA, self::OSTATUS, self::ACTIVITYPUB])) {
					$text = preg_replace("/([#@!])\[url\=(.*?)\](.*?)\[\/url\]/ism", '$1$3', $text);
				} elseif ($simple_html == self::DIASPORA) {
					// The ! is converted to @ since Diaspora only understands the @
					$text = preg_replace("/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
						'@<a href="$2">$3</a>',
						$text);
				} elseif (in_array($simple_html, [self::OSTATUS, self::ACTIVITYPUB])) {
					$text = preg_replace("/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
						'$1<span class="vcard"><a href="$2" class="url u-url mention" title="$3"><span class="fn nickname mention">$3</span></a></span>',
						$text);
				} elseif (!$simple_html) {
					$text = preg_replace("/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
						'$1<a href="$2" class="userinfo mention" title="$3">$3</a>',
						$text);
				}

				// Bookmarks in red - will be converted to bookmarks in friendica
				$text = preg_replace("/#\^\[url\](.*?)\[\/url\]/ism", '[bookmark=$1]$1[/bookmark]', $text);
				$text = preg_replace("/#\^\[url\=(.*?)\](.*?)\[\/url\]/ism", '[bookmark=$1]$2[/bookmark]', $text);
				$text = preg_replace("/#\[url\=.*?\]\^\[\/url\]\[url\=(.*?)\](.*?)\[\/url\]/i",
							"[bookmark=$1]$2[/bookmark]", $text);

				if (in_array($simple_html, [self::API, self::OSTATUS, self::TWITTER])) {
					$text = preg_replace_callback("/([^#@!])\[url\=([^\]]*)\](.*?)\[\/url\]/ism", "self::expandLinksCallback", $text);
					//$Text = preg_replace("/[^#@!]\[url\=([^\]]*)\](.*?)\[\/url\]/ism", ' $2 [url]$1[/url]', $Text);
					$text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", ' $2 [url]$1[/url]',$text);
				}

				// Perform URL Search
				if ($try_oembed) {
					$text = preg_replace_callback("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $try_oembed_callback, $text);
				}

				$text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", '[url=$1]$2[/url]', $text);

				// Handle Diaspora posts
				$text = preg_replace_callback(
					"&\[url=/?posts/([^\[\]]*)\](.*)\[\/url\]&Usi",
					function ($match) {
						return "[url=" . DI::baseUrl() . "/display/" . $match[1] . "]" . $match[2] . "[/url]";
					}, $text
				);

				$text = preg_replace_callback(
					"&\[url=/people\?q\=(.*)\](.*)\[\/url\]&Usi",
					function ($match) {
						return "[url=" . DI::baseUrl() . "/search?search=%40" . $match[1] . "]" . $match[2] . "[/url]";
					}, $text
				);

				// Server independent link to posts and comments
				// See issue: https://github.com/diaspora/diaspora_federation/issues/75
				$expression = "=diaspora://.*?/post/([0-9A-Za-z\-_@.:]{15,254}[0-9A-Za-z])=ism";
				$text = preg_replace($expression, DI::baseUrl()."/display/$1", $text);

				/* Tag conversion
				 * Supports:
				 * - #[url=<anything>]<term>[/url]
				 * - [url=<anything>]#<term>[/url]
				 */
				$text = preg_replace_callback("/(?:#\[url\=[^\[\]]*\]|\[url\=[^\[\]]*\]#)(.*?)\[\/url\]/ism", function($matches) use ($simple_html) {
					if ($simple_html == BBCode::ACTIVITYPUB) {
						return '<a href="' . DI::baseUrl() . '/search?tag=' . rawurlencode($matches[1])
							. '" data-tag="' . XML::escape($matches[1]) . '" rel="tag ugc">#'
							. XML::escape($matches[1]) . '</a>';
					} else {
						return '#<a href="' . DI::baseUrl() . '/search?tag=' . rawurlencode($matches[1])
							. '" class="tag" rel="tag" title="' . XML::escape($matches[1]) . '">'
							. XML::escape($matches[1]) . '</a>';
					}
				}, $text);

				// We need no target="_blank" rel="noopener noreferrer" for local links
				// convert links start with DI::baseUrl() as local link without the target="_blank" rel="noopener noreferrer" attribute
				$escapedBaseUrl = preg_quote(DI::baseUrl(), '/');
				$text = preg_replace("/\[url\](".$escapedBaseUrl.".*?)\[\/url\]/ism", '<a href="$1">$1</a>', $text);
				$text = preg_replace("/\[url\=(".$escapedBaseUrl.".*?)\](.*?)\[\/url\]/ism", '<a href="$1">$2</a>', $text);

				$text = preg_replace("/\[url\](.*?)\[\/url\]/ism", '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
				$text = preg_replace("/\[url\=(.*?)\](.*?)\[\/url\]/ism", '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>', $text);

				// Red compatibility, though the link can't be authenticated on Friendica
				$text = preg_replace("/\[zrl\=(.*?)\](.*?)\[\/zrl\]/ism", '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>', $text);


				// we may need to restrict this further if it picks up too many strays
				// link acct:user@host to a webfinger profile redirector

				$text = preg_replace('/acct:([^@]+)@((?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63})/', '<a href="' . DI::baseUrl() . '/acctlink?addr=$1@$2" target="extlink">acct:$1@$2</a>', $text);

				// Perform MAIL Search
				$text = preg_replace("/\[mail\](.*?)\[\/mail\]/", '<a href="mailto:$1">$1</a>', $text);
				$text = preg_replace("/\[mail\=(.*?)\](.*?)\[\/mail\]/", '<a href="mailto:$1">$2</a>', $text);

				/// @todo What is the meaning of these lines?
				$text = preg_replace('/\[\&amp\;([#a-z0-9]+)\;\]/', '&$1;', $text);
				$text = preg_replace('/\&\#039\;/', '\'', $text);

				// Currently deactivated, it made problems with " inside of alt texts.
				//$text = preg_replace('/\&quot\;/', '"', $text);

				// fix any escaped ampersands that may have been converted into links
				$text = preg_replace('/\<([^>]*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism', '<$1$2=$3&$4>', $text);

				// sanitizes src attributes (http and redir URLs for displaying in a web page, cid used for inline images in emails)
				$allowed_src_protocols = ['//', 'http://', 'https://', 'redir/', 'cid:'];

				array_walk($allowed_src_protocols, function(&$value) { $value = preg_quote($value, '#');});

				$text = preg_replace('#<([^>]*?)(src)="(?!' . implode('|', $allowed_src_protocols) . ')(.*?)"(.*?)>#ism',
							 '<$1$2=""$4 data-original-src="$3" class="invalid-src" title="' . DI::l10n()->t('Invalid source protocol') . '">', $text);

				// sanitize href attributes (only allowlisted protocols URLs)
				// default value for backward compatibility
				$allowed_link_protocols = DI::config()->get('system', 'allowed_link_protocols', []);

				// Always allowed protocol even if config isn't set or not including it
				$allowed_link_protocols[] = '//';
				$allowed_link_protocols[] = 'http://';
				$allowed_link_protocols[] = 'https://';
				$allowed_link_protocols[] = 'redir/';

				array_walk($allowed_link_protocols, function(&$value) { $value = preg_quote($value, '#');});

				$regex = '#<([^>]*?)(href)="(?!' . implode('|', $allowed_link_protocols) . ')(.*?)"(.*?)>#ism';
				$text = preg_replace($regex, '<$1$2="javascript:void(0)"$4 data-original-href="$3" class="invalid-href" title="' . DI::l10n()->t('Invalid link protocol') . '">', $text);

				// Shared content
				$text = self::convertShare(
					$text,
					function (array $attributes, array $author_contact, $content, $is_quote_share) use ($simple_html) {
						return self::convertShareCallback($attributes, $author_contact, $content, $is_quote_share, $simple_html);
					}
				);

				$text = self::interpolateSavedImagesIntoItemBody($text, $saved_image);

				return $text;
			}); // Escaped noparse, nobb, pre

			// Remove escaping tags and replace new lines that remain
			$text = preg_replace_callback('/\[(noparse|nobb)](.*?)\[\/\1]/ism', function ($match) {
				return str_replace("\n", "<br>", $match[2]);
			}, $text);

			// Additionally, [pre] tags preserve spaces
			$text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", function ($match) {
				return str_replace([' ', "\n"], ['&nbsp;', "<br>"], htmlentities($match[1], ENT_NOQUOTES,'UTF-8'));
			}, $text);

			return $text;
		}); // Escaped code

		$text = preg_replace_callback("#\[code(?:=([^\]]*))?\](.*?)\[\/code\]#ism",
			function ($matches) {
				if (strpos($matches[2], "\n") !== false) {
					$return = '<pre><code class="language-' . trim($matches[1]) . '">' . htmlentities(trim($matches[2], "\n\r"), ENT_NOQUOTES, 'UTF-8') . '</code></pre>';
				} else {
					$return = '<code>' . htmlentities($matches[2], ENT_NOQUOTES, 'UTF-8') . '</code>';
				}

				return $return;
			},
			$text
		);

		\HTMLPurifier_URISchemeRegistry::instance()->register('cid', new HTMLPurifier_URIScheme_cid());

		$config = \HTMLPurifier_HTML5Config::createDefault();
		$config->set('HTML.Doctype', 'HTML5');
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', self::getIFrameFilter());
		$config->set('Attr.AllowedRel', [
			'noreferrer' => true,
			'noopener' => true,
		]);
		$config->set('Attr.AllowedFrameTargets', [
			'_blank' => true,
		]);

		$HTMLPurifier = new \HTMLPurifier($config);
		$text = $HTMLPurifier->purify($text);

		return $text;
	}

	/**
	 * Get IFrame filter text
	 *
	 * @return string IFrame filter text
	 */
	private static function getIFrameFilter()
	{
		$filter = '%^(?:https://www.youtube.com/embed/|https://player.vimeo.com/video/';
	
		if (!DI::config()->get('system', 'no_oembed_rich_content')) {
			$filter .= '|https:// ';
		}
		
		$str_allowed = DI::config()->get('system', 'allowed_oembed', '');
		if (!empty($str_allowed)) {
			$allowed = explode(',', $str_allowed);
			foreach ($allowed as $site) {
				$filter .= '|https://.*' . trim($site);
			}
		}
		
		$filter .= '|' . DI::baseUrl() . '/oembed/)%xi';

		return $filter;
	}

	/**
	 * Strips the "abstract" tag from the provided text
	 *
	 * @param string $text The text with BBCode
	 * @return string The same text - but without "abstract" element
	 */
	public static function stripAbstract($text)
	{
		$text = preg_replace("/[\s|\n]*\[abstract\].*?\[\/abstract\][\s|\n]*/ism", '', $text);
		$text = preg_replace("/[\s|\n]*\[abstract=.*?\].*?\[\/abstract][\s|\n]*/ism", '', $text);

		return $text;
	}

	/**
	 * Returns the value of the "abstract" element
	 *
	 * @param string $text The text that maybe contains the element
	 * @param string $addon The addon for which the abstract is meant for
	 * @return string The abstract
	 */
	public static function getAbstract($text, $addon = '')
	{
		$abstract = '';
		$abstracts = [];
		$addon = strtolower($addon);

		if (preg_match_all("/\[abstract=(.*?)\](.*?)\[\/abstract\]/ism", $text, $results, PREG_SET_ORDER)) {
			foreach ($results AS $result) {
				$abstracts[strtolower($result[1])] = $result[2];
			}
		}

		if (isset($abstracts[$addon])) {
			$abstract = $abstracts[$addon];
		}

		if ($abstract == '' && preg_match("/\[abstract\](.*?)\[\/abstract\]/ism", $text, $result)) {
			$abstract = $result[1];
		}

		return $abstract;
	}

	/**
	 * Callback function to replace a Friendica style mention in a mention for Diaspora
	 *
	 * @param array $match Matching values for the callback
	 *                     [1] = Mention type (! or @)
	 *                     [2] = Name
	 *                     [3] = Address
	 * @return string Replaced mention
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function bbCodeMention2DiasporaCallback($match)
	{
		$contact = Contact::getByURL($match[3], false, ['addr']);
		if (empty($contact['addr'])) {
			return $match[0];
		}

		$mention = $match[1] . '{' . $match[2] . '; ' . $contact['addr'] . '}';
		return $mention;
	}

	/**
	 * Converts a BBCode text into Markdown
	 *
	 * This function converts a BBCode item body to be sent to Markdown-enabled
	 * systems like Diaspora and Libertree
	 *
	 * @param string $text
	 * @param bool   $for_diaspora Diaspora requires more changes than Libertree
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function toMarkdown($text, $for_diaspora = true)
	{
		$original_text = $text;

		// Since Diaspora is creating a summary for links, this function removes them before posting
		if ($for_diaspora) {
			$text = self::removeShareInformation($text);
		}

		/**
		 * Transform #tags, strip off the [url] and replace spaces with underscore
		 */
		$url_search_string = "^\[\]";
		$text = preg_replace_callback("/#\[url\=([$url_search_string]*)\](.*?)\[\/url\]/i",
			function ($matches) {
				return '#' . str_replace(' ', '_', $matches[2]);
			},
			$text
		);

		// Converting images with size parameters to simple images. Markdown doesn't know it.
		$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $text);

		// Convert it to HTML - don't try oembed
		if ($for_diaspora) {
			$text = self::convert($text, false, self::DIASPORA);

			// Add all tags that maybe were removed
			if (preg_match_all("/#\[url\=([$url_search_string]*)\](.*?)\[\/url\]/ism", $original_text, $tags)) {
				$tagline = '';
				foreach ($tags[2] as $tag) {
					$tag = html_entity_decode($tag, ENT_QUOTES, 'UTF-8');
					if (!strpos(html_entity_decode($text, ENT_QUOTES, 'UTF-8'), '#' . $tag)) {
						$tagline .= '#' . $tag . ' ';
					}
				}
				$text = $text . " " . $tagline;
			}
		} else {
			$text = self::convert($text, false, self::CONNECTORS);
		}

		// If a link is followed by a quote then there should be a newline before it
		// Maybe we should make this newline at every time before a quote.
		$text = str_replace(['</a><blockquote>'], ['</a><br><blockquote>'], $text);

		$stamp1 = microtime(true);

		// Now convert HTML to Markdown
		$text = HTML::toMarkdown($text);

		DI::profiler()->saveTimestamp($stamp1, "parser");

		// Libertree has a problem with escaped hashtags.
		$text = str_replace(['\#'], ['#'], $text);

		// Remove any leading or trailing whitespace, as this will mess up
		// the Diaspora signature verification and cause the item to disappear
		$text = trim($text);

		if ($for_diaspora) {
			$url_search_string = "^\[\]";
			$text = preg_replace_callback(
				"/([@!])\[(.*?)\]\(([$url_search_string]*?)\)/ism",
				['self', 'bbCodeMention2DiasporaCallback'],
				$text
			);
		}

		Hook::callAll('bb2diaspora', $text);

		return $text;
	}

	/**
	 * Pull out all #hashtags and @person tags from $string.
	 *
	 * We also get @person@domain.com - which would make
	 * the regex quite complicated as tags can also
	 * end a sentence. So we'll run through our results
	 * and strip the period from any tags which end with one.
	 * Returns array of tags found, or empty array.
	 *
	 * @param string $string Post content
	 *
	 * @return array List of tag and person names
	 */
	public static function getTags($string)
	{
		$ret = [];

		BBCode::performWithEscapedTags($string, ['noparse', 'pre', 'code', 'img'], function ($string) use (&$ret) {
			// Convert hashtag links to hashtags
			$string = preg_replace('/#\[url\=([^\[\]]*)\](.*?)\[\/url\]/ism', '#$2 ', $string);

			// Force line feeds at bbtags
			$string = str_replace(['[', ']'], ["\n[", "]\n"], $string);

			// ignore anything in a bbtag
			$string = preg_replace('/\[(.*?)\]/sm', '', $string);

			// Match full names against @tags including the space between first and last
			// We will look these up afterward to see if they are full names or not recognisable.

			if (preg_match_all('/(@[^ \x0D\x0A,:?]+ [^ \x0D\x0A@,:?]+)([ \x0D\x0A@,:?]|$)/', $string, $matches)) {
				foreach ($matches[1] as $match) {
					if (strstr($match, ']')) {
						// we might be inside a bbcode color tag - leave it alone
						continue;
					}

					if (substr($match, -1, 1) === '.') {
						$ret[] = substr($match, 0, -1);
					} else {
						$ret[] = $match;
					}
				}
			}

			// Otherwise pull out single word tags. These can be @nickname, @first_last
			// and #hash tags.

			if (preg_match_all('/([!#@][^\^ \x0D\x0A,;:?\']*[^\^ \x0D\x0A,;:?!\'.])/', $string, $matches)) {
				foreach ($matches[1] as $match) {
					if (strstr($match, ']')) {
						// we might be inside a bbcode color tag - leave it alone
						continue;
					}

					// try not to catch url fragments
					if (strpos($string, $match) && preg_match('/[a-zA-z0-9\/]/', substr($string, strpos($string, $match) - 1, 1))) {
						continue;
					}

					$ret[] = $match;
				}
			}
		});

		return array_unique($ret);
	}

	/**
	 * Perform a custom function on a text after having escaped blocks enclosed in the provided tag list.
	 *
	 * @param string   $text
	 * @param array    $tagList A list of tag names, e.g ['noparse', 'nobb', 'pre']
	 * @param callable $callback
	 * @return string
	 * @throws Exception
	 *@see Strings::performWithEscapedBlocks
	 *
	 */
	public static function performWithEscapedTags(string $text, array $tagList, callable $callback)
	{
		$tagList = array_map('preg_quote', $tagList);

		return Strings::performWithEscapedBlocks($text, '#\[(?:' . implode('|', $tagList) . ').*?\[/(?:' . implode('|', $tagList) . ')]#ism', $callback);
	}

	/**
	 * Replaces mentions in the provided message body for the provided user and network if any
	 *
	 * @param $body
	 * @param $profile_uid
	 * @param $network
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function setMentions($body, $profile_uid = 0, $network = '')
	{
		BBCode::performWithEscapedTags($body, ['noparse', 'pre', 'code', 'img'], function ($body) use ($profile_uid, $network) {
			$tags = BBCode::getTags($body);

			$tagged = [];
			$inform = '';

			foreach ($tags as $tag) {
				$tag_type = substr($tag, 0, 1);

				if ($tag_type == Tag::TAG_CHARACTER[Tag::HASHTAG]) {
					continue;
				}

				/*
				 * If we already tagged 'Robert Johnson', don't try and tag 'Robert'.
				 * Robert Johnson should be first in the $tags array
				 */
				foreach ($tagged as $nextTag) {
					if (stristr($nextTag, $tag . ' ')) {
						continue 2;
					}
				}

				$success = Item::replaceTag($body, $inform, $profile_uid, $tag, $network);

				if ($success['replaced']) {
					$tagged[] = $tag;
				}
			}

			return $body;
		});

		return $body;
	}

	/**
	 * @param string      $author  Author display name
	 * @param string      $profile Author profile URL
	 * @param string      $avatar  Author profile picture URL
	 * @param string      $link    Post source URL
	 * @param string      $posted  Post created date
	 * @param string|null $guid    Post guid (if any)
	 * @return string
	 * @TODO Rewrite to handle over whole record array
	 */
	public static function getShareOpeningTag(string $author, string $profile, string $avatar, string $link, string $posted, string $guid = null)
	{
		$header = "[share author='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $author) .
			"' profile='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $profile) .
			"' avatar='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $avatar) .
			"' link='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $link) .
			"' posted='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $posted);

		if ($guid) {
			$header .= "' guid='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $guid);
		}

		$header  .= "']";

		return $header;
	}

	/**
	 * Returns the BBCode relevant to embed the provided URL in a post body.
	 * For media type, it will return [img], [video] and [audio] tags.
	 * For regular web pages, it will either output a [bookmark] tag if title and description were provided,
	 * an [attachment] tag or a simple [url] tag depending on $tryAttachment.
	 *
	 * @param string      $url
	 * @param bool        $tryAttachment
	 * @param string|null $title
	 * @param string|null $description
	 * @param string|null $tags
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 *@see ParseUrl::getSiteinfoCached
	 *
	 */
	public static function embedURL(string $url, bool $tryAttachment = true, string $title = null, string $description = null, string $tags = null): string
	{
		DI::logger()->info($url);

		// If there is already some content information submitted we don't
		// need to parse the url for content.
		if (!empty($title) && !empty($description)) {
			$title = str_replace(["\r", "\n"], ['', ''], $title);

			$description = '[quote]' . trim($description) . '[/quote]' . "\n";

			$str_tags = '';
			if (!empty($tags)) {
				$arr_tags = ParseUrl::convertTagsToArray($tags);
				if (count($arr_tags)) {
					$str_tags = "\n" . implode(' ', $arr_tags) . "\n";
				}
			}

			$result = sprintf('[bookmark=%s]%s[/bookmark]%s', $url, ($title) ? $title : $url, $description) . $str_tags;

			DI::logger()->info('(unparsed): returns: ' . $result);

			return $result;
		}

		$siteinfo = ParseUrl::getSiteinfoCached($url);

		if (in_array($siteinfo['type'], ['image', 'video', 'audio'])) {
			switch ($siteinfo['type']) {
				case 'video':
					$bbcode = "\n" . '[video]' . $url . '[/video]' . "\n";
					break;
				case 'audio':
					$bbcode = "\n" . '[audio]' . $url . '[/audio]' . "\n";
					break;
				default:
					$bbcode = "\n" . '[img]' . $url . '[/img]' . "\n";
					break;
			}

			return $bbcode;
		}

		unset($siteinfo['keywords']);

		// Bypass attachment if parse url for a comment
		if (!$tryAttachment) {
			return "\n" . '[url=' . $url . ']' . $siteinfo['title'] . '[/url]';
		}

		// Format it as BBCode attachment
		return "\n" . PageInfo::getFooterFromData($siteinfo);
	}
}
