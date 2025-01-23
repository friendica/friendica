<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\Content\ContactSelector;
use Friendica\Content\Item;
use Friendica\Content\OEmbed;
use Friendica\Content\PageInfo;
use Friendica\Content\Smilies;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Util\Images;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use GuzzleHttp\Psr7\Uri;

class BBCode
{
	// Update this value to the current date whenever changes are made to BBCode::convert
	const VERSION = '2024-04-07';

	const INTERNAL     = 0;
	const EXTERNAL     = 1;
	const MASTODON_API = 2;
	const DIASPORA     = 3;
	const CONNECTORS   = 4;
	const TWITTER_API  = 5;
	const NPF          = 6;
	const TWITTER      = 8;
	const BACKLINK     = 8;
	const ACTIVITYPUB  = 9;
	const BLUESKY      = 10;

	const SHARED_ANCHOR = '<hr class="shared-anchor">';
	const TOP_ANCHOR    = '<br class="top-anchor">';
	const BOTTOM_ANCHOR = '<br class="button-anchor">';

	const PREVIEW_NONE     = 0;
	const PREVIEW_NO_IMAGE = 1;
	const PREVIEW_LARGE    = 2;
	const PREVIEW_SMALL    = 3;

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
	public static function getAttachmentData(string $body): array
	{
		DI::profiler()->startRecording('rendering');
		$data = [
			'type'          => '',
			'text'          => '',
			'after'         => '',
			'image'         => null,
			'url'           => '',
			'author_name'   => '',
			'author_url'    => '',
			'provider_name' => '',
			'provider_url'  => '',
			'title'         => '',
			'description'   => '',
		];

		if (!preg_match("/(.*)\[attachment(.*?)\](.*?)\[\/attachment\](.*)/ism", $body, $match)) {
			DI::profiler()->stopRecording();
			return [];
		}

		$attributes = $match[2];

		$data['text'] = trim($match[1]);

		foreach (['type', 'url', 'title', 'image', 'preview', 'publisher_name', 'publisher_url', 'author_name', 'author_url'] as $field) {
			preg_match('/' . preg_quote($field, '/') . '=("|\')(.*?)\1/ism', $attributes, $matches);
			$value = $matches[2] ?? '';

			if ($value != '') {
				switch ($field) {
					case 'publisher_name':
						$data['provider_name'] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
						break;

					case 'publisher_url':
						$data['provider_url'] = Network::sanitizeUrl(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
						break;

					case 'author_name':
						$data['author_name'] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
						if ($data['provider_name'] == $data['author_name']) {
							$data['author_name'] = '';
						}
						break;

					case 'author_url':
						$data['author_url'] = Network::sanitizeUrl(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
						if ($data['provider_url'] == $data['author_url']) {
							$data['author_url'] = '';
						}
						break;

					case 'title':
						$value         = self::toPlaintext(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
						$value         = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
						$data['title'] = self::escapeContent($value);

						// no break
					default:
						$data[$field] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
						break;
				}
			}
		}

		if (!in_array($data['type'], ['link', 'audio', 'photo', 'video'])) {
			DI::profiler()->stopRecording();
			return [];
		}

		$data['description'] = trim($match[3]);

		$data['after'] = trim($match[4]);

		$parts = parse_url($data['url']);
		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			if (empty($data['provider_name'])) {
				$data['provider_name'] = $parts['host'];
			}
			if (empty($data['provider_url']) || empty(parse_url($data['provider_url'], PHP_URL_SCHEME))) {
				$data['provider_url'] = $parts['scheme'] . '://' . $parts['host'];

				if (!empty($parts['port'])) {
					$data['provider_url'] .= ':' . $parts['port'];
				}
			}
		}

		DI::profiler()->stopRecording();
		return $data;
	}

	/**
	 * Remove [attachment] BBCode and replaces it with a regular [url]
	 *
	 * @param string  $body
	 * @param boolean $no_link_desc No link description
	 * @return string with replaced body
	 */
	public static function replaceAttachment(string $body, bool $no_link_desc = false): string
	{
		return preg_replace_callback(
			"/\s*\[attachment (.*?)\](.*?)\[\/attachment\]\s*/ism",
			function ($match) use ($body, $no_link_desc) {
				$attach_data = self::getAttachmentData($match[0]);
				if (empty($attach_data['url'])) {
					return $match[0];
				} elseif (strpos(str_replace($match[0], '', $body), $attach_data['url']) !== false) {
					return '';
				} elseif (empty($attach_data['title']) || $no_link_desc) {
					return " \n[url]" . $attach_data['url'] . "[/url]\n";
				} else {
					return " \n[url=" . $attach_data['url'] . ']' . $attach_data['title'] . "[/url]\n";
				}
			},
			$body
		);
	}

	/**
	 * Remove [attachment] BBCode
	 *
	 * @param string  $body
	 * @return string with removed attachment
	 */
	public static function removeAttachment(string $body): string
	{
		return trim(preg_replace("/\s*\[attachment .*?\].*?\[\/attachment\]\s*/ism", '', $body));
	}

	/**
	 * Converts a BBCode text into plaintext
	 *
	 * @param string $text
	 * @param bool $keep_urls Whether to keep URLs in the resulting plaintext
	 * @return string
	 */
	public static function toPlaintext(string $text, bool $keep_urls = true): string
	{
		DI::profiler()->startRecording('rendering');
		// Remove pictures in advance to avoid unneeded proxy calls
		$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", ' ', $text);
		$text = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", ' $2 ', $text);
		$text = preg_replace("/\[img.*?\[\/img\]/ism", ' ', $text);

		// Remove attachment
		$text = self::replaceAttachment($text);

		$naked_text = HTML::toPlaintext(self::convert($text, false, self::EXTERNAL, true), 0, !$keep_urls);

		DI::profiler()->stopRecording();
		return $naked_text;
	}

	/**
	 * Converts text into a format that can be used for the channel search and the language detection.
	 *
	 * @param string $text
	 * @param integer $uri_id
	 * @return string
	 */
	public static function toSearchText(string $text, int $uri_id): string
	{
		// Removes attachments
		$text = self::removeAttachment($text);

		// Add text from attached media
		if (!empty($uri_id)) {
			foreach (Post\Media::getByURIId($uri_id) as $media) {
				if (!empty($media['description']) && (stripos($text, $media['description']) === false)) {
					$text .= ' ' . $media['description'];
				}
				if (in_array($media['type'], [Post\Media::HTML, Post\Media::ACTIVITY])) {
					foreach (['name', 'author-name', 'publisher-name'] as $key) {
						if (!empty($media[$key] && stripos($text, $media[$key]) === false)) {
							$text .= ' ' . $media[$key];
						}
					}
				}
			}
		}

		if (empty($text)) {
			return '';
		}

		// Remove links without a link description
		$text = preg_replace("~\[url\=.*\]https?:.*\[\/url\]~", ' ', $text);

		// Remove pictures
		$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", ' ', $text);

		// Replace picture with the alt description
		$text = preg_replace("/\[img\=.*?\](.*?)\[\/img\]/ism", ' $1 ', $text);

		// Remove the other pictures
		$text = preg_replace("/\[img.*?\[\/img\]/ism", ' ', $text);

		// Removes mentions, remove links from hashtags
		$text = preg_replace('/[@!]\[url\=.*?\].*?\[\/url\]/ism', ' ', $text);
		$text = preg_replace('/[#]\[url\=.*?\](.*?)\[\/url\]/ism', ' #$1 ', $text);
		$text = preg_replace('/[@!#]+?\[url.*?\[\/url\]/ism', ' ', $text);
		$text = preg_replace("/\[url=[^\[\]]*\](.*)\[\/url\]/Usi", ' $1 ', $text);

		// Convert it to plain text
		$text = self::toPlaintext($text, false);

		// Remove possibly remaining links
		$text = preg_replace(Strings::autoLinkRegEx(), '', $text);

		// Remove all unneeded white space
		do {
			$oldtext = $text;
			$text    = str_replace(['  ', "\n", "\r", '"'], ' ', $text);
		} while ($oldtext != $text);

		return trim($text);
	}

	public static function proxyUrl(string $image, int $simplehtml = self::INTERNAL, int $uriid = 0, string $size = ''): string
	{
		$image = self::idnUrl($image);
		// Only send proxied pictures to API and for internal display
		if (!in_array($simplehtml, [self::INTERNAL, self::MASTODON_API, self::TWITTER_API])) {
			return $image;
		} elseif ($uriid > 0) {
			return Post\Link::getByLink($uriid, $image, $size);
		} else {
			return $image;
		}
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
	public static function limitBodySize(string $body): string
	{
		DI::profiler()->startRecording('rendering');
		$maxlen = DI::config()->get('config', 'max_import_size', 0);

		// If the length of the body, including the embedded images, is smaller
		// than the maximum, then don't waste time looking for the images
		if ($maxlen && (strlen($body) > $maxlen)) {

			DI::logger()->info('the total body length exceeds the limit', ['maxlen' => $maxlen, 'body_len' => strlen($body)]);

			$orig_body = $body;
			$new_body  = '';
			$textlen   = 0;

			$img_start    = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end      = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
			while (($img_st_close !== false) && ($img_end !== false)) {

				$img_st_close++; // make it point to AFTER the closing bracket
				$img_end += $img_start;
				$img_end += strlen('[/img]');

				if (!strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
					// This is an embedded image

					if (($textlen + $img_start) > $maxlen) {
						if ($textlen < $maxlen) {
							DI::logger()->debug('the limit happens before an embedded image');
							$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
							$textlen  = $maxlen;
						}
					} else {
						$new_body = $new_body . substr($orig_body, 0, $img_start);
						$textlen += $img_start;
					}

					$new_body = $new_body . substr($orig_body, $img_start, $img_end - $img_start);
				} else {

					if (($textlen + $img_end) > $maxlen) {
						if ($textlen < $maxlen) {
							DI::logger()->debug('the limit happens before the end of a non-embedded image');
							$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
							$textlen  = $maxlen;
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

				$img_start    = strpos($orig_body, '[img');
				$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
				$img_end      = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
			}

			if (($textlen + strlen($orig_body)) > $maxlen) {
				if ($textlen < $maxlen) {
					DI::logger()->debug('the limit happens after the end of the last image');
					$new_body = $new_body . substr($orig_body, 0, $maxlen - $textlen);
				}
			} else {
				DI::logger()->debug('the text size with embedded images extracted did not violate the limit');
				$new_body = $new_body . $orig_body;
			}

			DI::profiler()->stopRecording();
			return $new_body;
		} else {
			DI::profiler()->stopRecording();
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
	 * @param array   $data
	 * @param int     $uriid
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function convertAttachment(string $text, int $simplehtml = self::INTERNAL, bool $tryoembed = true, array $data = [], int $uriid = 0, int $preview_mode = self::PREVIEW_LARGE): string
	{
		DI::profiler()->startRecording('rendering');
		$data = $data ?: self::getAttachmentData($text);
		if (empty($data) || empty($data['url'])) {
			DI::profiler()->stopRecording();
			return $text;
		}

		$data['url'] = Network::sanitizeUrl($data['url']);

		if (isset($data['title'])) {
			$data['title'] = strip_tags($data['title']);
			$data['title'] = str_replace(['http://', 'https://'], '', $data['title']);
		} else {
			$data['title'] = '';
		}

		if (((strpos($data['text'], '[img=') !== false) || (strpos($data['text'], '[img]') !== false) || DI::config()->get('system', 'always_show_preview')) && !empty($data['image'])) {
			$data['preview'] = $data['image'];
			$data['image']   = '';
		}

		$return = '';
		try {
			if ($tryoembed && OEmbed::isAllowedURL($data['url'])) {
				$return = OEmbed::getHTML($data['url'], $data['title'], $uriid);
			} else {
				throw new Exception('OEmbed is disabled for this attachment.');
			}
		} catch (Exception $e) {
			$data['title'] = ($data['title'] ?? '') ?: $data['url'];

			if ($simplehtml != self::CONNECTORS) {
				$return = sprintf('<div class="type-%s">', $data['type']);
			}

			if ($preview_mode == self::PREVIEW_NO_IMAGE) {
				unset($data['image']);
				unset($data['preview']);
			}

			if (!empty($data['title']) && !empty($data['url'])) {
				$preview_class = $preview_mode == self::PREVIEW_LARGE ? 'attachment-image' : 'attachment-preview';
				if (!empty($data['image']) && empty($data['text']) && ($data['type'] == 'photo')) {
					$return .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="" title="%s" class="' . $preview_class . '" /></a>', $data['url'], self::proxyUrl($data['image'], $simplehtml, $uriid), $data['title']);
				} else {
					if (!empty($data['image'])) {
						$return .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="" title="%s" class="' . $preview_class . '" /></a><br>', $data['url'], self::proxyUrl($data['image'], $simplehtml, $uriid), $data['title']);
					} elseif (!empty($data['preview'])) {
						$return .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="" title="%s" class="attachment-preview" /></a><br>', $data['url'], self::proxyUrl($data['preview'], $simplehtml, $uriid), $data['title']);
					}
					$return .= sprintf('<h4><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></h4>', $data['url'], $data['title']);
				}
			}

			if (!empty($data['description']) && $data['description'] != $data['title']) {
				// Sanitize the HTML
				$return .= sprintf('<blockquote>%s</blockquote>', trim(HTML::purify($data['description'])));
			}

			if (!empty($data['provider_url']) && !empty($data['provider_name'])) {
				$data['provider_url'] = Network::sanitizeUrl($data['provider_url']);
				if (!empty($data['author_name'])) {
					$return .= sprintf('<sup><a href="%s" target="_blank" rel="noopener noreferrer">%s (%s)</a></sup>', $data['provider_url'], $data['author_name'], $data['provider_name']);
				} else {
					$return .= sprintf('<sup><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></sup>', $data['provider_url'], $data['provider_name']);
				}
			}

			if ($simplehtml != self::CONNECTORS) {
				$return .= '</div>';
			}
		}

		DI::profiler()->stopRecording();
		return trim(($data['text'] ?? '') . ' ' . $return . ' ' . ($data['after'] ?? ''));
	}

	public static function removeShareInformation(string $text, bool $plaintext = false, bool $nolink = false): string
	{
		DI::profiler()->startRecording('rendering');
		$data = self::getAttachmentData($text);

		if (!$data) {
			DI::profiler()->stopRecording();
			return $text;
		} elseif ($nolink) {
			DI::profiler()->stopRecording();
			return $data['text'] . ($data['after'] ?? '');
		}

		$title = htmlentities($data['title'] ?? '', ENT_QUOTES, 'UTF-8', false);
		$text  = htmlentities($data['text'], ENT_QUOTES, 'UTF-8', false);
		if ($plaintext || (($title != '') && strstr($text, $title))) {
			$data['title'] = $data['url'];
		} elseif (($text != '') && strstr($title, $text)) {
			$data['text']  = $data['title'];
			$data['title'] = $data['url'];
		}

		if (empty($data['text']) && !empty($data['title']) && empty($data['url'])) {
			DI::profiler()->stopRecording();
			return $data['title'] . $data['after'];
		}

		// If the link already is included in the post, don't add it again
		if (!empty($data['url']) && strpos($data['text'], $data['url'])) {
			DI::profiler()->stopRecording();
			return $data['text'] . $data['after'];
		}

		$text = $data['text'];

		if (!empty($data['url']) && !empty($data['title'])) {
			$text .= "\n[url=" . $data['url'] . ']' . $data['title'] . '[/url]';
		} elseif (!empty($data['url'])) {
			$text .= "\n[url]" . $data['url'] . '[/url]';
		}

		DI::profiler()->stopRecording();
		return $text . "\n" . $data['after'];
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
	public static function getTagPosition(string $text, string $name, int $occurrences = 0)
	{
		DI::profiler()->startRecording('rendering');
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
			DI::profiler()->stopRecording();
			return false;
		}

		$start_equal = strpos($text, '=', $start_open);
		$start_close = strpos($text, ']', $start_open);

		if ($start_close === false) {
			DI::profiler()->stopRecording();
			return false;
		}

		$start_close++;

		$end_open = strpos($text, '[/' . $name . ']', $start_close);

		if ($end_open === false) {
			DI::profiler()->stopRecording();
			return false;
		}

		$res = [
			'start' => [
				'open'  => $start_open,
				'close' => $start_close
			],
			'end' => [
				'open'  => $end_open,
				'close' => $end_open + strlen('[/' . $name . ']')
			],
		];

		if ($start_equal !== false) {
			$res['start']['equal'] = $start_equal + 1;
		}

		DI::profiler()->stopRecording();
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
	public static function pregReplaceInTag(string $pattern, string $replace, string $name, string $text): string
	{
		DI::profiler()->startRecording('rendering');
		$occurrences = 0;
		$pos         = self::getTagPosition($text, $name, $occurrences);
		while ($pos !== false && $occurrences++ < 1000) {
			$start   = substr($text, 0, $pos['start']['open']);
			$subject = substr($text, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
			$end     = substr($text, $pos['end']['close']);
			if ($end === false) {
				$end = '';
			}

			$subject = preg_replace($pattern, $replace, $subject);
			$text    = $start . $subject . $end;

			$pos = self::getTagPosition($text, $name, $occurrences);
		}

		DI::profiler()->stopRecording();
		return $text;
	}

	private static function extractImagesFromItemBody(string $body): array
	{
		$saved_image = [];
		$orig_body   = $body;
		$new_body    = '';

		$cnt          = 0;
		$img_start    = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end      = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		while (($img_st_close !== false) && ($img_end !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$img_end += $img_start;

			if (!strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
				// This is an embedded image
				$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
				$new_body          = $new_body . substr($orig_body, 0, $img_start) . '[$#saved_image' . $cnt . '#$]';

				$cnt++;
			} else {
				$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));
			}

			$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

			if ($orig_body === false) {
				// in case the body ends on a closing image tag
				$orig_body = '';
			}

			$img_start    = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_end      = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
		}

		$new_body = $new_body . $orig_body;

		return ['body' => $new_body, 'images' => $saved_image];
	}

	private static function interpolateSavedImagesIntoItemBody(int $uriid, string $body, array $images): string
	{
		$newbody = $body;

		$cnt = 0;
		foreach ($images as $image) {
			// We're depending on the property of 'foreach' (specified on the PHP website) that
			// it loops over the array starting from the first element and going sequentially
			// to the last element
			$newbody = str_replace(
				'[$#saved_image' . $cnt . '#$]',
				'<img src="' . self::proxyUrl($image, self::INTERNAL, $uriid) . '" alt="" class="empty-description"/>',
				$newbody
			);
			$cnt++;
		}

		return $newbody;
	}

	/**
	 * @param string $text A BBCode string
	 * @return array Empty array if no share tag is present or the following array, missing attributes end up empty strings:
	 *               - comment   : Text before the opening share tag
	 *               - shared    : Text inside the share tags
	 *               - author    : (Optional) Display name of the shared author
	 *               - profile   : (Optional) Profile page URL of the shared author
	 *               - avatar    : (Optional) Profile picture URL of the shared author
	 *               - link      : (Optional) Canonical URL of the shared post
	 *               - posted    : (Optional) Date the shared post was initially posted ("Y-m-d H:i:s" in GMT)
	 *               - message_id: (Optional) Shared post URI if any
	 *               - guid      : (Optional) Shared post GUID if any
	 */
	public static function fetchShareAttributes(string $text): array
	{
		DI::profiler()->startRecording('rendering');
		if (preg_match('~(.*?)\[share](.*)\[/share]~ism', $text, $matches)) {
			DI::profiler()->stopRecording();
			return [
				'author'     => '',
				'profile'    => '',
				'avatar'     => '',
				'link'       => '',
				'posted'     => '',
				'guid'       => '',
				'message_id' => trim($matches[2]),
				'comment'    => trim($matches[1]),
				'shared'     => '',
			];
		}
		// See Issue https://github.com/friendica/friendica/issues/10454
		// Hashtags in usernames are expanded to links. This here is a quick fix.
		$text = preg_replace('~([@!#])\[url=.*?](.*?)\[/url]~ism', '$1$2', $text);

		if (!preg_match('~(.*?)\[share(.*?)](.*)\[/share]~ism', $text, $matches)) {
			DI::profiler()->stopRecording();
			return [];
		}

		$attributes = self::extractShareAttributes($matches[2]);

		$attributes['comment'] = trim($matches[1]);
		$attributes['shared']  = trim($matches[3]);

		DI::profiler()->stopRecording();
		return $attributes;
	}

	/**
	 * @see BBCode::fetchShareAttributes()
	 * @param string $shareString Internal opening share tag string matched by the regular expression
	 * @return array A fixed attribute array where missing attribute are represented by empty strings
	 */
	private static function extractShareAttributes(string $shareString): array
	{
		$attributes = [];
		foreach (['author', 'profile', 'avatar', 'link', 'posted', 'guid', 'message_id'] as $field) {
			preg_match("/$field=(['\"])(.+?)\\1/ism", $shareString, $matches);
			$attributes[$field] = html_entity_decode($matches[2] ?? '', ENT_QUOTES, 'UTF-8');
		}

		return $attributes;
	}

	/**
	 * Remove the share block
	 *
	 * @param string $body
	 * @return string
	 */
	public static function removeSharedData(string $body): string
	{
		return trim(preg_replace("/\s*\[share.*?\].*?\[\/share\]\s*/ism", '', $body));
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
	public static function convertShare(string $text, callable $callback, int $uriid = 0): string
	{
		DI::profiler()->startRecording('rendering');
		$return = preg_replace_callback(
			'~(.*?)\[share(.*?)](.*)\[/share]~ism',
			function ($match) use ($callback, $uriid) {
				$attributes = self::extractShareAttributes($match[2]);

				$author_contact         = Contact::getByURL($attributes['profile'], false, ['id', 'url', 'addr', 'name', 'micro']);
				$author_contact['url']  = ($author_contact['url'] ?? $attributes['profile']);
				$author_contact['addr'] = ($author_contact['addr'] ?? '');

				$attributes['author']  = ($author_contact['name'] ?? '') ?: $attributes['author'];
				$attributes['avatar']  = ($author_contact['micro'] ?? '') ?: $attributes['avatar'];
				$attributes['profile'] = ($author_contact['url'] ?? '') ?: $attributes['profile'];

				if (!empty($author_contact['id'])) {
					$attributes['avatar'] = Contact::getAvatarUrlForId($author_contact['id'], Proxy::SIZE_THUMB);
				} elseif ($attributes['avatar']) {
					$attributes['avatar'] = self::proxyUrl($attributes['avatar'], self::INTERNAL, $uriid, Proxy::SIZE_THUMB);
				}

				$content = preg_replace(Strings::autoLinkRegEx(), '<a href="$1">$1</a>', $match[3]);

				return $match[1] . $callback($attributes, $author_contact, $content ?? '', trim($match[1]) != '');
			},
			$text
		);

		DI::profiler()->stopRecording();
		return trim($return);
	}

	/**
	 * Convert complex IMG and ZMG elements
	 */
	private static function convertImages(string $text, int $simplehtml, int $uriid = 0): string
	{
		DI::profiler()->startRecording('rendering');
		$return = preg_replace_callback(
			"/\[[zi]mg(.*?)\]([^\[\]]*)\[\/[zi]mg\]/ism",
			function ($match) use ($simplehtml, $uriid) {
				$attribute_string = $match[1];
				$attributes       = [];
				foreach (['alt', 'width', 'height'] as $field) {
					preg_match("/$field=(['\"])(.+?)\\1/ism", $attribute_string, $matches);
					$attributes[$field] = html_entity_decode($matches[2] ?? '', ENT_QUOTES, 'UTF-8');
				}

				$img_str = '<img src="' . self::proxyUrl($match[2], $simplehtml, $uriid) . '"';
				foreach ($attributes as $key => $value) {
					if (!empty($value)) {
						$img_str .= ' ' . $key . '="' . htmlspecialchars($value, ENT_COMPAT) . '"';
					}
				}
				$img_str .= ' ' . empty($attributes['alt']) ? 'class="empty-description"' : 'class="has-alt-description"';
				return $img_str . '>';
			},
			$text
		);

		DI::profiler()->stopRecording();
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
	private static function convertShareCallback(array $attributes, array $author_contact, string $content, bool $is_quote_share, int $simplehtml): string
	{
		DI::profiler()->startRecording('rendering');
		$mention = $attributes['author'] . ' (' . ($author_contact['addr'] ?? '') . ')';

		switch ($simplehtml) {
			case self::MASTODON_API:
			case self::TWITTER_API:
				$text = ($is_quote_share ? '<br>' : '') .
					'<b><a href="' . $attributes['link'] . '">' . html_entity_decode('&#x2672;', ENT_QUOTES, 'UTF-8') . ' ' . $author_contact['addr'] . "</a>:</b><br>\n" .
					'<blockquote class="shared_content" dir="auto">' . $content . '</blockquote>';
				break;
			case self::DIASPORA:
				if (stripos(Strings::normaliseLink($attributes['link']), 'http://twitter.com/') === 0) {
					$text = ($is_quote_share ? '<hr />' : '') . '<p><a href="' . $attributes['link'] . '">' . $attributes['link'] . '</a></p>' . "\n";
				} else {
					$headline = '<p><b>♲ <a href="' . $attributes['profile'] . '">' . $attributes['author'] . '</a>:</b></p>' . "\n";

					if (!empty($attributes['posted']) && !empty($attributes['link'])) {
						$headline = '<p><b>♲ <a href="' . $attributes['profile'] . '">' . $attributes['author'] . '</a></b> - <a href="' . $attributes['link'] . '">' . $attributes['posted'] . ' GMT</a></p>' . "\n";
					}

					$text = ($is_quote_share ? '<hr />' : '') . $headline . '<blockquote>' . trim($content) . '</blockquote>' . "\n";

					if (empty($attributes['posted']) && !empty($attributes['link'])) {
						$text .= '<p><a href="' . $attributes['link'] . '">[Source]</a></p>' . "\n";
					}
				}

				break;
			case self::CONNECTORS:
				$headline = '<p><b>' . html_entity_decode('&#x2672; ', ENT_QUOTES, 'UTF-8');
				$headline .= DI::l10n()->t('<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s', $attributes['link'], $mention, $attributes['posted']);
				$headline .= ':</b></p>' . "\n";

				$text = ($is_quote_share ? '<hr />' : '') . $headline . '<blockquote class="shared_content" dir="auto">' . trim($content) . '</blockquote>' . "\n";

				break;
			case self::ACTIVITYPUB:
				$author = '@<span class="vcard"><a href="' . $author_contact['url'] . '" class="url u-url mention" title="' . $author_contact['addr'] . '"><span class="fn nickname mention">' . $author_contact['addr'] . '</span></a>:</span>';
				$text   = '<div><a href="' . $attributes['link'] . '">' . html_entity_decode('&#x2672;', ENT_QUOTES, 'UTF-8') . '</a> ' . $author . '<blockquote>' . $content . '</blockquote></div>' . "\n";
				break;
			default:
				$text = ($is_quote_share ? "\n" : '');

				$contact = Contact::getByURL($attributes['profile'], false, ['network']);
				$network = $contact['network'] ?? Protocol::PHANTOM;

				$gsid = ContactSelector::getServerIdForProfile($attributes['profile']);
				$tpl  = Renderer::getMarkupTemplate('shared_content.tpl');
				$text .= self::SHARED_ANCHOR . Renderer::replaceMacros($tpl, [
					'$profile'      => $attributes['profile'],
					'$avatar'       => $attributes['avatar'],
					'$author'       => $attributes['author'],
					'$link'         => $attributes['link'],
					'$link_title'   => DI::l10n()->t('Link to source'),
					'$posted'       => $attributes['posted'],
					'$guid'         => $attributes['guid'],
					'$network_name' => ContactSelector::networkToName($network, '', $gsid),
					'$network_svg'  => ContactSelector::networkToSVG($network, $gsid),
					'$content'      => self::TOP_ANCHOR . self::setMentions(trim($content), 0, $network) . self::BOTTOM_ANCHOR,
				]);
				break;
		}

		return $text;
	}

	private static function removePictureLinksCallback(array $match): string
	{
		$cache_key = 'remove:' . $match[1];
		$text      = DI::cache()->get($cache_key);

		if (is_null($text)) {
			$curlResult = DI::httpClient()->head($match[1], [HttpClientOptions::TIMEOUT => DI::config()->get('system', 'xrd_timeout'), HttpClientOptions::REQUEST => HttpClientRequest::CONTENTTYPE]);
			if ($curlResult->isSuccess()) {
				$mimetype = $curlResult->getContentType() ?? '';
			} else {
				$mimetype = '';
			}

			if (Images::isSupportedMimeType($mimetype)) {
				$text = '[url=' . $match[1] . ']' . $match[1] . '[/url]';
			} else {
				$text = '[url=' . $match[2] . ']' . $match[2] . '[/url]';

				// if its not a picture then look if its a page that contains a picture link
				$body = DI::httpClient()->fetch($match[1], HttpClientAccept::HTML, 0, '', HttpClientRequest::SITEINFO);
				if (empty($body)) {
					DI::cache()->set($cache_key, $text);
					return $text;
				}

				$doc = new DOMDocument();
				@$doc->loadHTML($body);
				$xpath = new DOMXPath($doc);
				$list  = $xpath->query('//meta[@name]');
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

	/**
	 * Callback: Sanitize links from given $match array
	 *
	 * @param array $match Array with link match
	 * @return string BBCode
	 */
	private static function sanitizeLinksCallback(array $match): string
	{
		if (count($match) == 3) {
			return '[' . $match[1] . ']' . Network::sanitizeUrl($match[2]) . '[/' . $match[1] . ']';
		} else {
			return '[' . $match[1] . '=' . Network::sanitizeUrl($match[2]) . ']' . $match[3] . '[/' . $match[1] . ']';
		}
	}

	/**
	 * Callback: Expands links from given $match array
	 *
	 * @param array $match Array with link match
	 * @return string BBCode
	 */
	private static function expandLinksCallback(array $match): string
	{
		if (($match[3] == '') || ($match[2] == $match[3]) || stristr($match[2], $match[3])) {
			return ($match[1] . '[url]' . $match[2] . '[/url]');
		} else {
			return ($match[1] . $match[3] . ' [url]' . $match[2] . '[/url]');
		}
	}

	/**
	 * Callback: Cleans picture links
	 *
	 * @param array $match Array with link match
	 * @return string BBCode
	 */
	private static function cleanPictureLinksCallback(array $match): string
	{
		// When the picture link is the own photo path then we can avoid fetching the link
		$own_photo_url = preg_quote(Strings::normaliseLink(DI::baseUrl()) . '/photos/');
		if (preg_match('|' . $own_photo_url . '.*?/image/|', Strings::normaliseLink($match[1]))) {
			if (!empty($match[3])) {
				$text = '[img=' . str_replace('-1.', '-0.', $match[2]) . ']' . $match[3] . '[/img]';
			} else {
				$text = '[img]' . str_replace('-1.', '-0.', $match[2]) . '[/img]';
			}
			return $text;
		}

		$cache_key = 'clean:' . $match[1];
		$text      = DI::cache()->get($cache_key);
		if (!is_null($text)) {
			return $text;
		}

		$curlResult = DI::httpClient()->head($match[1], [HttpClientOptions::TIMEOUT => DI::config()->get('system', 'xrd_timeout'), HttpClientOptions::REQUEST => HttpClientRequest::CONTENTTYPE]);
		if ($curlResult->isSuccess()) {
			$mimetype = $curlResult->getContentType() ?? '';
		} else {
			$mimetype = '';
		}

		// if its a link to a picture then embed this picture
		if (Images::isSupportedMimeType($mimetype)) {
			$text = '[img]' . $match[1] . '[/img]';
		} else {
			if (!empty($match[3])) {
				$text = '[img=' . $match[2] . ']' . $match[3] . '[/img]';
			} else {
				$text = '[img]' . $match[2] . '[/img]';
			}

			// if its not a picture then look if its a page that contains a picture link
			$body = DI::httpClient()->fetch($match[1], HttpClientAccept::HTML, 0, '', HttpClientRequest::SITEINFO);
			if (empty($body)) {
				DI::cache()->set($cache_key, $text);
				return $text;
			}

			$doc = new DOMDocument();
			@$doc->loadHTML($body);
			$xpath = new DOMXPath($doc);
			$list  = $xpath->query('//meta[@name]');
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

	/**
	 * Cleans picture links
	 *
	 * @param string $text HTML/BBCode string
	 * @return string Cleaned HTML/BBCode
	 */
	public static function cleanPictureLinks(string $text): string
	{
		DI::profiler()->startRecording('rendering');
		$return = preg_replace_callback("&\[url=([^\[\]]*)\]\[img=(.*)\](.*)\[\/img\]\[\/url\]&Usi", [self::class, 'cleanPictureLinksCallback'], $text);
		$return = preg_replace_callback("&\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]&Usi", [self::class, 'cleanPictureLinksCallback'], $return);
		DI::profiler()->stopRecording();
		return $return;
	}

	/**
	 * Removes links
	 *
	 * @param string $bbcode HTML/BBCode string
	 * @return string Cleaned HTML/BBCode
	 */
	public static function removeLinks(string $bbcode): string
	{
		DI::profiler()->startRecording('rendering');
		$bbcode = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", ' ', $bbcode);
		$bbcode = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", ' $1 ', $bbcode);
		$bbcode = preg_replace("/\[img.*?\[\/img\]/ism", ' ', $bbcode);

		$bbcode = preg_replace('/[@!#]\[url\=.*?\].*?\[\/url\]/ism', '', $bbcode);
		$bbcode = preg_replace("/\[url=[^\[\]]*\](.*)\[\/url\]/Usi", ' $1 ', $bbcode);
		$bbcode = preg_replace('/[@!#]?\[url.*?\[\/url\]/ism', '', $bbcode);
		DI::profiler()->stopRecording();
		return $bbcode;
	}

	/**
	 * Replace names in mentions with nicknames
	 *
	 * @param string $body HTML/BBCode
	 * @return string Body with replaced mentions
	 */
	public static function setMentionsToNicknames(string $body): string
	{
		DI::profiler()->startRecording('rendering');
		$regexp = "/([@!])\[url\=([^\[\]]*)\].*?\[\/url\]/ism";
		$body   = preg_replace_callback($regexp, [self::class, 'mentionCallback'], $body);
		DI::profiler()->stopRecording();
		return $body;
	}

	/**
	 * Callback function to replace a Friendica style mention in a mention with the nickname
	 *
	 * @param array $match Matching values for the callback
	 * @return string Replaced mention or empty string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function mentionCallback(array $match): string
	{
		if (empty($match[2])) {
			return '';
		}

		$data = Contact::getByURL($match[2], false, ['url', 'nick']);
		if (empty($data['nick'])) {
			return $match[0];
		}

		return $match[1] . '[url=' . $data['url'] . ']' . $data['nick'] . '[/url]';
	}

	/**
	 * Replace mention links
	 *
	 * @param string $body HTML/BBCode
	 * @return string Body with replaced mentions
	 */
	public static function setMentionsToAddr(string $body): string
	{
		DI::profiler()->startRecording('rendering');
		$regexp = "/([@!])\[url\=([^\[\]]*)\].*?\[\/url\]/ism";
		$body   = preg_replace_callback($regexp, [self::class, 'mentionToAddrCallback'], $body);
		DI::profiler()->stopRecording();
		return $body;
	}

	/**
	 * Callback function to replace a Friendica style mention in a mention with the addr
	 *
	 * @param array $match Matching values for the callback
	 * @return string Replaced mention or empty string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function mentionToAddrCallback(array $match): string
	{
		if (empty($match[2])) {
			return '';
		}

		$data = Contact::getByURL($match[2], false, ['url', 'nick', 'addr']);
		if (empty($data['nick'])) {
			return $match[0];
		}

		return $match[1] . ($data['addr'] ?: $data['nick']);
	}

	/**
	 * Normalize links to Youtube and Vimeo to a unified format.
	 *
	 * @param string $text
	 * @return string
	 */
	private static function normalizeVideoLinks(string $text): string
	{
		$text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);
		$text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/embed\/(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);
		$text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/shorts\/(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);
		$text = preg_replace("/\[youtube\]https?:\/\/youtu.be\/(.*?)\[\/youtube\]/ism", '[youtube]$1[/youtube]', $text);

		$text = preg_replace("/\[vimeo\]https?:\/\/player.vimeo.com\/video\/([0-9]+)(.*?)\[\/vimeo\]/ism", '[vimeo]$1[/vimeo]', $text);
		$text = preg_replace("/\[vimeo\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/vimeo\]/ism", '[vimeo]$1[/vimeo]', $text);

		return $text;
	}

	/**
	 * Expand Youtube and Vimeo links to
	 *
	 * @param string $text
	 * @return string
	 */
	public static function expandVideoLinks(string $text): string
	{
		$text = self::normalizeVideoLinks($text);
		$text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '[url=https://www.youtube.com/watch?v=$1]https://www.youtube.com/watch?v=$1[/url]', $text);
		$text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '[url=https://vimeo.com/$1]https://vimeo.com/$1[/url]', $text);

		return $text;
	}

	/**
	 * Converts a BBCode message for a given URI-ID to a HTML message
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
	 * - 7: Used for dfrn
	 * - 8: Used for Twitter, WP backlink text setting
	 * - 9: ActivityPub
	 *
	 * @param int    $uriid
	 * @param string $text
	 * @param int    $simple_html
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function convertForUriId(int $uriid = null, string $text = null, int $simple_html = self::INTERNAL): string
	{
		$try_oembed = ($simple_html == self::INTERNAL);

		return self::convert($text ?? '', $try_oembed, $simple_html, false, $uriid ?? 0);
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
	 * - 7: Used for dfrn
	 * - 8: Used for Twitter, WP backlink text setting
	 * - 9: ActivityPub
	 *
	 * @param string $text
	 * @param bool   $try_oembed
	 * @param int    $simple_html
	 * @param bool   $for_plaintext
	 * @param int    $uriid
	 * @return string Converted code or empty string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function convert(string $text = null, bool $try_oembed = true, int $simple_html = self::INTERNAL, bool $for_plaintext = false, int $uriid = 0): string
	{
		// Accounting for null default column values
		if (is_null($text) || $text === '') {
			return '';
		}

		DI::profiler()->startRecording('rendering');

		Hook::callAll('bbcode', $text);

		$ev = Event::fromBBCode($text);

		$text = self::performWithEscapedTags($text, ['code'], function ($text) use ($try_oembed, $simple_html, $for_plaintext, $uriid, $ev) {
			$text = self::performWithEscapedTags($text, ['noparse', 'nobb', 'pre'], function ($text) use ($try_oembed, $simple_html, $for_plaintext, $uriid, $ev) {
				/*
				 * preg_match_callback function to replace potential Oembed tags with Oembed content
				 *
				 * $match[0] = [tag]$url[/tag] or [tag=$url]$title[/tag]
				 * $match[1] = $url
				 * $match[2] = $title or absent
				 */
				$try_oembed_callback = function (array $match) use ($uriid) {
					$url   = $match[1];
					$title = $match[2] ?? '';

					try {
						$return = OEmbed::getHTML($url, $title, $uriid);
					} catch (Exception $ex) {
						$return = $match[0];
					}

					return $return;
				};

				// Extract the private images which use data urls since preg has issues with
				// large data sizes. Stash them away while we do bbcode conversion, and then put them back
				// in after we've done all the regex matching. We cannot use any preg functions to do this.
				$extracted   = self::extractImagesFromItemBody($text);
				$saved_image = $extracted['images'];

				// General clean up of the content, for example unneeded blanks and new lines
				$text = self::normaliseInput($extracted['body']);

				// Now the structural elements are converted
				$text = self::convertHeaderToHtml($text, $simple_html);
				$text = self::convertStylesToHtml($text, $simple_html);
				$text = self::convertListsToHtml($text);
				$text = self::convertTablesToHtml($text);
				$text = self::convertSpoilersToHtml($text);
				$text = self::convertStructuresToHtml($text);

				// We add URL without a surrounding URL at this time, since at a earlier stage it would had been too early,
				// since the used regular expression won't touch URL inside of BBCode elements, but with the structural ones it should.
				// At a later stage we won't be able to exclude certain parts of the code.
				$text = self::performWithEscapedTags($text, ['url', 'img', 'audio', 'video', 'youtube', 'vimeo', 'share', 'attachment', 'iframe', 'bookmark', 'map', 'oembed'], function ($text) use ($simple_html, $for_plaintext) {
					if (!$for_plaintext) {
						$text = preg_replace(Strings::autoLinkRegEx(), '[url]$1[/url]', $text) ?? '';
					}
					return self::convertSmileysToHtml($text, $simple_html, $for_plaintext);
				});

				// Now for some more complex BBCode elements (mostly non standard ones)
				$text = self::convertAttachmentsToHtml($text, $simple_html, $try_oembed, $uriid);
				$text = self::convertMapsToHtml($text, $simple_html);
				$text = self::convertQuotesToHtml($text);
				$text = self::convertVideoPlatformsToHtml($text, $try_oembed);
				$text = self::convertOEmbedToHtml($text, $uriid);
				$text = self::convertEventsToHtml($text, $simple_html, $uriid, $ev);

				// Some simpler non standard elements
				$text = self::convertEmojisToHtml($text, $simple_html);
				$text = self::convertCryptToHtml($text);
				$text = self::convertIFramesToHtml($text);
				$text = self::convertMailToHtml($text);
				$text = self::convertAudioVideoToHtml($text, $simple_html, $try_oembed, $try_oembed_callback);

				// At last, some standard elements. URL has to go last,
				// since some previous conversions use URL elements.
				$text = self::convertImagesToHtml($text, $simple_html, $uriid);
				$text = self::convertUrlToHtml($text, $simple_html, $for_plaintext, $try_oembed, $try_oembed_callback);

				// If the post only consists of an emoji, we display it larger than normal.
				if (!$for_plaintext && DI::config()->get('system', 'big_emojis') && ($simple_html != self::DIASPORA) && Smilies::isEmojiPost($text)) {
					$text = '<span style="font-size: xx-large; line-height: normal;">' . $text . '</span>';
				}

				// Sanitize the created HTML.
				$text = self::cleanupHtml($text);

				// This needs to be called after the cleanup, since otherwise some links are invalidated
				$text = self::convertSharesToHtml($text, $simple_html, $try_oembed, $uriid);

				// Insert the previously extracted embedded image again.
				return self::interpolateSavedImagesIntoItemBody($uriid, $text, $saved_image);
			}); // Escaped noparse, nobb, pre

			// Remove escaping tags and replace new lines that remain
			$text = preg_replace_callback('/\[(noparse|nobb)](.*?)\[\/\1]/ism', function ($match) {
				return str_replace("\n", "<br>", $match[2]);
			}, $text);

			// Additionally, [pre] tags preserve spaces
			$text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", function ($match) {
				return str_replace([' ', "\n"], ['&nbsp;', "<br>"], htmlentities($match[1], ENT_NOQUOTES, 'UTF-8'));
			}, $text);

			return $text;
		}); // Escaped code

		$text = preg_replace_callback(
			"#\[code(?:=([^\]]*))?\](.*?)\[\/code\]#ism",
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

		// Default iframe allowed domains/path
		$allowedIframeDomains = DI::config()->get('system', 'no_oembed_rich_content') ? [] : ['www.youtube.com/embed/', 'player.vimeo.com/video/'];

		$allowedIframeDomains = array_merge(
			$allowedIframeDomains,
			DI::config()->get('system', 'allowed_oembed') ?
				explode(',', DI::config()->get('system', 'allowed_oembed'))
				: []
		);

		if (strpos($text, '<p>') !== false || strpos($text, '</p>') !== false) {
			$text = '<p>' . $text . '</p>';
		}

		$text = HTML::purify($text, $allowedIframeDomains);
		DI::profiler()->stopRecording();

		return trim($text);
	}

	private static function normaliseInput(string $text): string
	{
		// Remove the abstract element. It is a non visible element.
		$text = self::stripAbstract($text);

		// Line ending normalisation
		$text = str_replace("\r\n", "\n", $text);

		// Move new lines outside of tags
		$text = preg_replace("#\[(\w*)](\n*)#ism", '$2[$1]', $text);
		$text = preg_replace("#(\n*)\[/(\w*)]#ism", '[/$2]$1', $text);

		// Replace any html brackets with HTML Entities to prevent executing HTML or script
		// Don't use strip_tags here because it breaks [url] search by replacing & with amp

		$text = str_replace("<", "&lt;", $text);
		$text = str_replace(">", "&gt;", $text);

		// remove some newlines before the general conversion
		$text = preg_replace("/\s?\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "\n[share$1]$2[/share]\n", $text);
		$text = preg_replace("/\s?\[quote(.*?)\]\s?(.*?)\s?\[\/quote\]\s?/ism", "\n[quote$1]$2[/quote]\n", $text);

		// Remove linefeeds inside of the table elements. See issue #6799
		$search = [
			"\n[th]", "[th]\n", " [th]", "\n[/th]", "[/th]\n", "[/th] ",
			"\n[td]", "[td]\n", " [td]", "\n[/td]", "[/td]\n", "[/td] ",
			"\n[tr]", "[tr]\n", " [tr]", "[tr] ", "\n[/tr]", "[/tr]\n", " [/tr]", "[/tr] ",
			"\n[hr]", "[hr]\n", " [hr]", "[hr] ",
			"\n[attachment ", " [attachment ", "\n[/attachment]", "[/attachment]\n", " [/attachment]", "[/attachment] ",
			"[table]\n", "[table] ", " [table]", "\n[/table]", " [/table]", "[/table] ",
			" \n", "\t\n", "[/li]\n", "\n[li]", "\n[*]",
		];
		$replace = [
			"[th]", "[th]", "[th]", "[/th]", "[/th]", "[/th]",
			"[td]", "[td]", "[td]", "[/td]", "[/td]", "[/td]",
			"[tr]", "[tr]", "[tr]", "[tr]", "[/tr]", "[/tr]", "[/tr]", "[/tr]",
			"[hr]", "[hr]", "[hr]", "[hr]",
			"[attachment ", "[attachment ", "[/attachment]", "[/attachment]", "[/attachment]", "[/attachment]",
			"[table]", "[table]", "[table]", "[/table]", "[/table]", "[/table]",
			"\n", "\n", "[/li]", "[li]", "[*]",
		];
		do {
			$oldtext = $text;
			$text    = str_replace($search, $replace, $text);
		} while ($oldtext != $text);

		// Replace these here only once
		$search  = ["\n[table]", "[/table]\n"];
		$replace = ["[table]", "[/table]"];
		$text    = str_replace($search, $replace, $text);

		// Trim new lines regardless of the system.remove_multiplicated_lines config value
		$text = trim($text, "\n");

		// removing multiplicated newlines
		if (DI::config()->get('system', 'remove_multiplicated_lines')) {
			$search = [
				"\n\n\n", "[/quote]\n\n", "\n[/quote]", "\n[ul]", "[/ul]\n", "\n[ol]", "[/ol]\n", "\n\n[share ", "[/attachment]\n",
				"\n[h1]", "[/h1]\n", "\n[h2]", "[/h2]\n", "\n[h3]", "[/h3]\n", "\n[h4]", "[/h4]\n", "\n[h5]", "[/h5]\n", "\n[h6]", "[/h6]\n"
			];
			$replace = [
				"\n\n", "[/quote]\n", "[/quote]", "[ul]", "[/ul]", "[ol]", "[/ol]", "\n[share ", "[/attachment]",
				"[h1]", "[/h1]", "[h2]", "[/h2]", "[h3]", "[/h3]", "[h4]", "[/h4]", "[h5]", "[/h5]", "[h6]", "[/h6]"
			];
			do {
				$oldtext = $text;
				$text    = str_replace($search, $replace, $text);
			} while ($oldtext != $text);
		}

		return $text;
	}

	private static function convertEventsToHtml(string $text, int $simple_html, int $uriid, array $ev): string
	{
		// If we find any event code, turn it into an event.
		// After we're finished processing the bbcode we'll
		// replace all of the event code with a reformatted version.

		// If we found an event earlier, strip out all the event code and replace with a reformatted version.
		// Replace the event-start section with the entire formatted event. The other bbcode is stripped.
		// Summary (e.g. title) is required, earlier revisions only required description (in addition to
		// start which is always required). Allow desc with a missing summary for compatibility.

		if ((!empty($ev['desc']) || !empty($ev['summary'])) && !empty($ev['start'])) {
			$sub = Event::getHTML($ev, $simple_html, $uriid);

			$text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/ism", '', $text);
			$text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/ism", '', $text);
			$text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/ism", $sub, $text);
			$text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/ism", '', $text);
			$text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/ism", '', $text);
			$text = preg_replace("/\[event\-id\](.*?)\[\/event\-id\]/ism", '', $text);
		}

		return $text;
	}

	private static function convertAttachmentsToHtml(string $text, int $simple_html, bool $try_oembed, int $uriid): string
	{
		/// @todo Have a closer look at the different html modes
		// Handle attached links or videos
		if ($simple_html == self::NPF) {
			$text = self::removeAttachment($text);
		} elseif (in_array($simple_html, [self::MASTODON_API, self::TWITTER_API, self::ACTIVITYPUB])) {
			$text = self::replaceAttachment($text);
		} elseif (!in_array($simple_html, [self::INTERNAL, self::EXTERNAL, self::CONNECTORS])) {
			$text = self::replaceAttachment($text, true);
		} else {
			$text = self::convertAttachment($text, $simple_html, $try_oembed, [], $uriid);
		}

		return $text;
	}

	private static function convertMapsToHtml(string $text, int $simple_html): string
	{
		// leave open the possibility of [map=something]
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

		return $text;
	}

	private static function convertHeaderToHtml(string $text, int $simple_html): string
	{
		// Check for headers

		if ($simple_html == self::INTERNAL) {
			//Ensure to always start with <h4> if possible
			$heading_count = 0;
			for ($level = 6; $level > 0; $level--) {
				if (preg_match("(\[h$level\].*?\[\/h$level\])ism", $text)) {
					$heading_count++;
				}
			}
			if ($heading_count > 0) {
				$heading = min($heading_count + 3, 6);
				for ($level = 6; $level > 0; $level--) {
					if (preg_match("(\[h$level\].*?\[\/h$level\])ism", $text)) {
						$text = preg_replace("(\[h$level\](.*?)\[\/h$level\])ism", "</p><h$heading>$1</h$heading><p>", $text);
						$heading--;
					}
				}
			}
		} else {
			$text = preg_replace("(\[h1\](.*?)\[\/h1\])ism", '</p><h1>$1</h1><p>', $text);
			$text = preg_replace("(\[h2\](.*?)\[\/h2\])ism", '</p><h2>$1</h2><p>', $text);
			$text = preg_replace("(\[h3\](.*?)\[\/h3\])ism", '</p><h3>$1</h3><p>', $text);
			$text = preg_replace("(\[h4\](.*?)\[\/h4\])ism", '</p><h4>$1</h4><p>', $text);
			$text = preg_replace("(\[h5\](.*?)\[\/h5\])ism", '</p><h5>$1</h5><p>', $text);
			$text = preg_replace("(\[h6\](.*?)\[\/h6\])ism", '</p><h6>$1</h6><p>', $text);
		}

		return $text;
	}

	private static function convertEmojisToHtml(string $text, int $simple_html): string
	{
		// Mastodon Emoji (internal tag, do not document for users)
		if ($simple_html == self::MASTODON_API) {
			$text = preg_replace("(\[emoji=(.*?)](.*?)\[/emoji])ism", '$2', $text);
		} else {
			$text = preg_replace("(\[emoji=(.*?)](.*?)\[/emoji])ism", '<span class="mastodon emoji"><img src="$1" alt="$2" title="$2"/></span>', $text);
		}
		return $text;
	}

	private static function convertStylesToHtml(string $text, int $simple_html): string
	{
		// Markdown is designed to pass through HTML elements that it can't handle itself,
		// so that the other system would parse the original HTML element.
		// But Diaspora has chosen not to do this and doesn't parse HTML elements.
		// So we need to make some changes here.
		if ($simple_html == BBCode::DIASPORA) {
			$elements = ['big', 'small'];
			foreach ($elements as $bbcode) {
				$text = preg_replace("(\[" . $bbcode . "\](.*?)\[\/" . $bbcode . "\])ism", '$1', $text);
			}

			$elements = [
				'del'  => 's', 'ins' => 'em', 'kbd' => 'code', 'mark' => 'strong',
				'samp' => 'code', 'u' => 'em', 'var' => 'em'
			];
			foreach ($elements as $bbcode => $html) {
				$text = preg_replace("(\[" . $bbcode . "\](.*?)\[\/" . $bbcode . "\])ism", '<' . $html . '>$1</' . $html . '>', $text);
			}
		}

		// Several easy to replace HTML elements
		// @todo add the new elements to the documentation by the end of 2024 so that most systems will support them.
		$elements = [
			'b', 'del', 'em', 'i', 'ins', 'kbd', 'mark',
			's', 'samp', 'small', 'strong', 'sub', 'sup', 'u', 'var'
		];
		foreach ($elements as $element) {
			$text = preg_replace("(\[" . $element . "\](.*?)\[\/" . $element . "\])ism", '<' . $element . '>$1</' . $element . '>', $text);
		}

		$text = preg_replace("(\[big\](.*?)\[\/big\])ism", "<span style=\"font-size: larger;\">$1</span>", $text);

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

		// Check for block-level custom CSS
		$text = preg_replace('#(?<=^|\n)\[style=(.*?)](.*?)\[/style](?:\n|$)#ism', '<div style="$1">$2</div>', $text);

		// Check for inline custom CSS
		$text = preg_replace("(\[style=(.*?)\](.*?)\[\/style\])ism", '<span style="$1">$2</span>', $text);

		// Check for CSS classes
		// @deprecated since 2021.12, left for backward-compatibility reasons
		$text = preg_replace("(\[class=(.*?)\](.*?)\[\/class\])ism", '<span class="$1">$2</span>', $text);
		// Add HTML new lines
		$text = str_replace("\n\n", '</p><p>', $text);
		$text = str_replace("\n", '<br>', $text);

		// Check for font change text
		$text = preg_replace("/\[font=(.*?)\](.*?)\[\/font\]/sm", "<span style=\"font-family: $1;\">$2</span>", $text);

		return $text;
	}

	private static function convertTablesToHtml(string $text): string
	{
		$text = preg_replace("/\[th\](.*?)\[\/th\]/sm", '<th>$1</th>', $text);
		$text = preg_replace("/\[td\](.*?)\[\/td\]/sm", '<td>$1</td>', $text);
		$text = preg_replace("/\[tr\](.*?)\[\/tr\]/sm", '<tr>$1</tr>', $text);
		$text = preg_replace("/\[table\](.*?)\[\/table\]/sm", '</p><table>$1</table><p>', $text);

		$text = preg_replace("/\[table border=1\](.*?)\[\/table\]/sm", '</p><table border="1" >$1</table><p>', $text);
		$text = preg_replace("/\[table border=0\](.*?)\[\/table\]/sm", '</p><table border="0" >$1</table><p>', $text);

		return $text;
	}

	private static function convertListsToHtml(string $text): string
	{
		// handle nested lists
		$endlessloop = 0;

		while ((((strpos($text, "[/list]") !== false) && (strpos($text, "[list") !== false)) ||
			((strpos($text, "[/ol]") !== false) && (strpos($text, "[ol]") !== false)) ||
			((strpos($text, "[/ul]") !== false) && (strpos($text, "[ul]") !== false)) ||
			((strpos($text, "[/li]") !== false) && (strpos($text, "[li]") !== false))) && (++$endlessloop < 20)) {
			$text = preg_replace("/\[list\](.*?)\[\/list\]/ism", '</p><ul class="listbullet" style="list-style-type: circle;">$1</ul><p>', $text);
			$text = preg_replace("/\[list=\](.*?)\[\/list\]/ism", '</p><ul class="listnone" style="list-style-type: none;">$1</ul><p>', $text);
			$text = preg_replace("/\[list=1\](.*?)\[\/list\]/ism", '</p><ul class="listdecimal" style="list-style-type: decimal;">$1</ul><p>', $text);
			$text = preg_replace("/\[list=((?-i)i)\](.*?)\[\/list\]/ism", '</p><ul class="listlowerroman" style="list-style-type: lower-roman;">$2</ul><p>', $text);
			$text = preg_replace("/\[list=((?-i)I)\](.*?)\[\/list\]/ism", '</p><ul class="listupperroman" style="list-style-type: upper-roman;">$2</ul><p>', $text);
			$text = preg_replace("/\[list=((?-i)a)\](.*?)\[\/list\]/ism", '</p><ul class="listloweralpha" style="list-style-type: lower-alpha;">$2</ul><p>', $text);
			$text = preg_replace("/\[list=((?-i)A)\](.*?)\[\/list\]/ism", '</p><ul class="listupperalpha" style="list-style-type: upper-alpha;">$2</ul><p>', $text);
			$text = preg_replace("/\[ul\](.*?)\[\/ul\]/ism", '</p><ul>$1</ul><p>', $text);
			$text = preg_replace("/\[ol\](.*?)\[\/ol\]/ism", '</p><ol>$1</ol><p>', $text);
			$text = preg_replace("/\[li\](.*?)\[\/li\]/ism", '<li>$1</li>', $text);
		}

		// Check for list text
		$text = str_replace("[*]", "<li>", $text);
		$text = str_replace("[li]", "<li>", $text);

		return $text;
	}

	private static function convertSpoilersToHtml(string $text): string
	{
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
		while ((strpos($text, "[/spoiler]") !== false) && (strpos($text, "[spoiler=") !== false) && (++$endlessloop < 20)) {
			$text = preg_replace(
				"/\[spoiler=[\"\']*(.*?)[\"\']*\](.*?)\[\/spoiler\]/ism",
				'<details class="spoiler"><summary>$1</summary>$2</details>',
				$text
			);
		}

		return $text;
	}

	private static function convertStructuresToHtml(string $text): string
	{
		$text = preg_replace("(\[p\](.*?)\[\/p\])ism", '<p>$1</p>', $text);
		// Check for paragraph
		return str_replace('[hr]', '</p><hr /><p>', $text);
	}

	private static function convertSmileysToHtml(string $text, int $simple_html, bool $for_plaintext): string
	{
		if (strpos($text, '[nosmile]') !== false) {
			$text = str_replace('[nosmile]', '', $text);
			return $text;
		}

		return Smilies::replace($text, ($simple_html != self::INTERNAL) || $for_plaintext);
	}

	private static function convertQuotesToHtml(string $text): string
	{
		// Declare the format for [quote] layout
		$QuoteLayout = '</p><blockquote>$1</blockquote><p>';

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
		while ((strpos($text, "[/quote]") !== false) && (strpos($text, "[quote=") !== false) && (++$endlessloop < 20)) {
			$text = preg_replace(
				"/\[quote=[\"\']*(.*?)[\"\']*\](.*?)\[\/quote\]/ism",
				"<p><strong class=" . '"author"' . ">" . $t_wrote . "</strong></p><blockquote>$2</blockquote>",
				$text
			);
		}

		return $text;
	}

	private static function convertImagesToHtml(string $text, int $simple_html, int $uriid): string
	{
		// [img=widthxheight]image source[/img]
		$text = preg_replace_callback(
			"/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism",
			function ($matches) use ($simple_html, $uriid) {
				if (strpos($matches[3], "data:image/") === 0) {
					return $matches[0];
				}

				$matches[3] = self::proxyUrl($matches[3], $simple_html, $uriid);
				return "[img=" . $matches[1] . "x" . $matches[2] . "]" . $matches[3] . "[/img]";
			},
			$text
		);

		$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="width: $1px;" alt="" class="empty-description">', $text);
		$text = preg_replace("/\[zmg\=([0-9]*)x([0-9]*)\](.*?)\[\/zmg\]/ism", '<img class="zrl" src="$3" style="width: $1px;" alt="" class="empty-description">', $text);

		$text = preg_replace_callback(
			"/\[[iz]mg\=(.*?)\](.*?)\[\/[iz]mg\]/ism",
			function ($matches) use ($simple_html, $uriid) {
				$matches[1] = self::proxyUrl($matches[1], $simple_html, $uriid);
				$alt        = htmlspecialchars($matches[2], ENT_COMPAT);
				// Fix for Markdown problems with Diaspora, see issue #12701
				if (($simple_html != self::DIASPORA) || strpos($matches[2], '"') === false) {
					return '<img src="' . $matches[1] . '" alt="' . $alt . '" title="' . $alt . '" class="' . (empty($alt) ? 'empty-description' : 'has-alt-description') . '">';
				} else {
					return '<img src="' . $matches[1] . '" alt="' . $alt . '">';
				}
			},
			$text
		);

		// Images
		// [img]pathtoimage[/img]
		$text = preg_replace_callback(
			"/\[[iz]mg\](.*?)\[\/[iz]mg\]/ism",
			function ($matches) use ($simple_html, $uriid) {
				if (strpos($matches[1], "data:image/") === 0) {
					return $matches[0];
				}

				$matches[1] = self::proxyUrl($matches[1], $simple_html, $uriid);
				return "[img]" . $matches[1] . "[/img]";
			},
			$text
		);

		$text = preg_replace("/\[img\](.*?)\[\/img\]/ism", '<img src="$1" alt="" class="empty-description"/>', $text);
		$text = preg_replace("/\[zmg\](.*?)\[\/zmg\]/ism", '<img src="$1" alt="" class="empty-description" />', $text);

		$text = self::convertImages($text, $simple_html, $uriid);

		return $text;
	}

	private static function convertCryptToHtml(string $text): string
	{
		$text = preg_replace("/\[crypt\](.*?)\[\/crypt\]/ism", '<br><img src="' . DI::baseUrl() . '/images/lock_icon.gif" alt="' . DI::l10n()->t('Encrypted content') . '" title="' . DI::l10n()->t('Encrypted content') . '" /><br>', $text);
		$text = preg_replace("/\[crypt(.*?)\](.*?)\[\/crypt\]/ism", '<br><img src="' . DI::baseUrl() . '/images/lock_icon.gif" alt="' . DI::l10n()->t('Encrypted content') . '" title="' . '$1' . ' ' . DI::l10n()->t('Encrypted content') . '" /><br>', $text);
		return $text;
	}

	private static function convertAudioVideoToHtml(string $text, int $simple_html, bool $try_oembed, \Closure $try_oembed_callback): string
	{
		// Simplify "video" element
		$text = preg_replace('(\[video[^\]]*?\ssrc\s?=\s?([^\s\]]+)[^\]]*?\].*?\[/video\])ism', '[video]$1[/video]', $text);

		$text = preg_replace_callback("/\[(video)\](.*?)\[\/video\]/ism", [self::class, 'sanitizeLinksCallback'], $text);
		$text = preg_replace_callback("/\[(audio)\](.*?)\[\/audio\]/ism", [self::class, 'sanitizeLinksCallback'], $text);

		if ($simple_html == self::NPF) {
			$text = preg_replace(
				"/\[video\](.*?)\[\/video\]/ism",
				'</p><video src="$1" controls width="100%" height="auto">$1</video><p>',
				$text
			);
			$text = preg_replace(
				"/\[audio\](.*?)\[\/audio\]/ism",
				'</p><audio src="$1" controls>$1">$1</audio><p>',
				$text
			);
		} elseif ($try_oembed) {
			// html5 video and audio
			$text = preg_replace(
				"/\[video\](.*?\.(ogg|ogv|oga|ogm|webm|mp4).*?)\[\/video\]/ism",
				'<video src="$1" controls width="100%" height="auto"><a href="$1">$1</a></video>',
				$text
			);

			$text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", $try_oembed_callback, $text);
			$text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", $try_oembed_callback, $text);

			$text = preg_replace("/\[video\](.*?)\[\/video\]/ism", '[url]$1[/url]', $text);
			$text = preg_replace("/\[audio\](.*?)\[\/audio\]/ism", '<audio src="$1" controls><a href="$1">$1</a></audio>', $text);
		} else {
			$text = preg_replace("/\[video\](.*?)\[\/video\]/ism", '[url]$1[/url]', $text);
			$text = preg_replace("/\[audio\](.*?)\[\/audio\]/ism", '[url]$1[/url]', $text);
		}
		return $text;
	}

	private static function convertIFramesToHtml(string $text): string
	{
		// Backward compatibility, [iframe] support has been removed in version 2020.12
		$text = preg_replace_callback("/\[(iframe)\](.*?)\[\/iframe\]/ism", [self::class, 'sanitizeLinksCallback'], $text);
		$text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/ism", '[url]$1[/url]', $text);

		return $text;
	}

	private static function convertVideoPlatformsToHtml(string $text, bool $try_oembed): string
	{
		$appHelper = DI::appHelper();

		$text = self::normalizeVideoLinks($text);

		// Youtube extensions
		if ($try_oembed && OEmbed::isAllowedURL('https://www.youtube.com/embed/')) {
			$text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '<iframe width="' . $appHelper->getThemeInfoValue('videowidth') . '" height="' . $appHelper->getThemeInfoValue('videoheight') . '" src="https://www.youtube.com/embed/$1" frameborder="0" ></iframe>', $text);
		} else {
			$text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '[url]https://www.youtube.com/watch?v=$1[/url]', $text);
		}

		// Vimeo extensions
		if ($try_oembed && OEmbed::isAllowedURL('https://player.vimeo.com/video')) {
			$text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '<iframe width="' . $appHelper->getThemeInfoValue('videowidth') . '" height="' . $appHelper->getThemeInfoValue('videoheight') . '" src="https://player.vimeo.com/video/$1" frameborder="0" ></iframe>', $text);
		} else {
			$text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '[url]https://vimeo.com/$1[/url]', $text);
		}
		return $text;
	}

	private static function convertOEmbedToHtml(string $text, int $uriid): string
	{
		// oembed tag
		$text = OEmbed::BBCode2HTML($text, $uriid);

		// Avoid triple linefeeds through oembed
		$text = str_replace("<br style='clear:left'></span><br><br>", "<br style='clear:left'></span><br>", $text);

		return $text;
	}

	private static function convertUrlToHtml(string $text, int $simple_html, bool $for_plaintext, bool $try_oembed, \Closure $try_oembed_callback): string
	{
		$text = preg_replace_callback("/\[(url)\](.*?)\[\/url\]/ism", [self::class, 'sanitizeLinksCallback'], $text);
		$text = preg_replace_callback("/\[(url)\=(.*?)\](.*?)\[\/url\]/ism", [self::class, 'sanitizeLinksCallback'], $text);

		// Handle mentions and hashtag links
		if ($simple_html == self::DIASPORA) {
			// The ! is converted to @ since Diaspora only understands the @
			$text = preg_replace(
				"/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
				'@<a href="$2">$3</a>',
				$text
			);
		} elseif (in_array($simple_html, [self::ACTIVITYPUB])) {
			$text = preg_replace(
				"/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
				'<span class="h-card"><a href="$2" class="u-url mention">$1<span>$3</span></a></span>',
				$text
			);
			$text = preg_replace(
				"/([#])\[url\=(.*?)\](.*?)\[\/url\]/ism",
				'<a href="$2" class="mention hashtag" rel="tag">$1<span>$3</span></a>',
				$text
			);
		} elseif (in_array($simple_html, [self::INTERNAL, self::EXTERNAL, self::TWITTER_API])) {
			$text = preg_replace(
				"/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
				'<bdi>$1<a href="$2" class="userinfo mention" title="$3">$3</a></bdi>',
				$text
			);
		} elseif ($simple_html == self::MASTODON_API) {
			$text = preg_replace(
				"/([@!])\[url\=(.*?)\](.*?)\[\/url\]/ism",
				'<a class="u-url mention status-link" href="$2" rel="nofollow noopener noreferrer" target="_blank" title="$3">$1<span>$3</span></a>',
				$text
			);
			$text = preg_replace(
				"/([#])\[url\=(.*?)\](.*?)\[\/url\]/ism",
				'<a class="mention hashtag status-link" href="$2" rel="tag">$1<span>$3</span></a>',
				$text
			);
		} else {
			$text = preg_replace("/([#@!])\[url\=(.*?)\](.*?)\[\/url\]/ism", '$1$3', $text);
		}

		if ($for_plaintext) {
			$text = preg_replace("(\[url\](.*?)\[\/url\])ism", " $1 ", $text);
			$text = preg_replace_callback("&\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]&Usi", [self::class, 'removePictureLinksCallback'], $text);
		}

		// Bookmarks in red - will be converted to bookmarks in friendica
		$text = preg_replace("/#\^\[url\](.*?)\[\/url\]/ism", '[bookmark=$1]$1[/bookmark]', $text);
		$text = preg_replace("/#\^\[url\=(.*?)\](.*?)\[\/url\]/ism", '[bookmark=$1]$2[/bookmark]', $text);
		$text = preg_replace(
			"/#\[url\=.*?\]\^\[\/url\]\[url\=(.*?)\](.*?)\[\/url\]/i",
			"[bookmark=$1]$2[/bookmark]",
			$text
		);

		if (in_array($simple_html, [self::TWITTER, self::BLUESKY])) {
			$text = preg_replace_callback("/([^#@!])\[url\=([^\]]*)\](.*?)\[\/url\]/ism", [self::class, 'expandLinksCallback'], $text);
			//$text = preg_replace("/[^#@!]\[url\=([^\]]*)\](.*?)\[\/url\]/ism", ' $2 [url]$1[/url]', $text);
			$text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", ' $2 [url]$1[/url]', $text);
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
			},
			$text
		);

		$text = preg_replace_callback(
			"&\[url=/people\?q\=(.*)\](.*)\[\/url\]&Usi",
			function ($match) {
				return "[url=" . DI::baseUrl() . "/search?search=%40" . $match[1] . "]" . $match[2] . "[/url]";
			},
			$text
		);

		// Server independent link to posts and comments
		// See issue: https://github.com/diaspora/diaspora_federation/issues/75
		$expression = "=diaspora://.*?/post/([0-9A-Za-z\-_@.:]{15,254}[0-9A-Za-z])=ism";
		$text       = preg_replace($expression, DI::baseUrl() . "/display/$1", $text);

		/* Tag conversion
		 * Supports:
		 * - #[url=<anything>]<term>[/url]
		 * - [url=<anything>]#<term>[/url]
		 */
		self::performWithEscapedTags($text, ['url', 'share'], function ($text) use ($simple_html) {
			$text = preg_replace_callback("/(?:#\[url\=[^\[\]]*\]|\[url\=[^\[\]]*\]#)(.*?)\[\/url\]/ism", function ($matches) use ($simple_html) {
				if ($simple_html == self::ACTIVITYPUB) {
					return '<a href="' . DI::baseUrl() . '/search?tag=' . urlencode($matches[1])
						. '" data-tag="' . XML::escape($matches[1]) . '" rel="tag ugc">#'
						. XML::escape($matches[1]) . '</a>';
				} else {
					return '#<a href="' . DI::baseUrl() . '/search?tag=' . urlencode($matches[1])
						. '" class="tag" rel="tag" title="' . XML::escape($matches[1]) . '">'
						. XML::escape($matches[1]) . '</a>';
				}
			}, $text);
			return $text;
		});

		// Red compatibility, though the link can't be authenticated on Friendica
		$text = preg_replace("/\[zrl\=(.*?)\](.*?)\[\/zrl\]/ism", '[url=$1]$2[/url]', $text);

		if (in_array($simple_html, [self::INTERNAL, self::EXTERNAL, self::DIASPORA, self::MASTODON_API, self::TWITTER_API, self::ACTIVITYPUB])) {
			$text = self::shortenLinkDescription($text, $simple_html);
		} else {
			$text = self::unifyLinks($text);
		}

		// We need no target="_blank" rel="noopener noreferrer" for local links
		// convert links start with DI::baseUrl() as local link without the target="_blank" rel="noopener noreferrer" attribute
		$text = preg_replace("/\[url\=(" . preg_quote(DI::baseUrl(), '/') . ".*?)\](.*?)\[\/url\]/ism", '<a href="$1">$2</a>', $text);

		$text = preg_replace("/\[url\=(.*?)\](.*?)\[\/url\]/ism", '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>', $text);

		// we may need to restrict this further if it picks up too many strays
		// link acct:user@host to a webfinger profile redirector
		return preg_replace('/acct:([^@]+)@((?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63})/', '<a href="' . DI::baseUrl() . '/acctlink?addr=$1@$2" target="extlink">acct:$1@$2</a>', $text);
	}

	private static function escapeUrl(string $url): string
	{
		return self::escapeContent(self::idnUrl($url));
	}

	private static function escapeContent(string $url): string
	{
		return str_replace(['[', ']'], ['&#91;', '&#93;'], $url);
	}

	private static function idnUrl(string $url): string
	{
		$parts = parse_url($url);
		if (empty($parts['host'])) {
			return $url;
		}

		$parts['host'] = idn_to_ascii(urldecode($parts['host']));
		try {
			return (string)Uri::fromParts($parts);
		} catch (\Throwable $th) {
			DI::logger()->notice('Exception on unparsing url', ['url' => $url, 'parts' => $parts, 'code' => $th->getCode(), 'message' => $th->getMessage()]);
			return $url;
		}
	}

	private static function unifyLinks(string $text): string
	{
		return preg_replace_callback(
			"/\[url\](.*?)\[\/url\]/ism",
			function ($match) {
				return "[url=" . self::escapeUrl($match[1]) . "]" . $match[1] . "[/url]";
			},
			$text
		);
	}

	private static function shortenLinkDescription(string $text, int $simple_html): string
	{
		if ($simple_html == self::INTERNAL) {
			$max_length = DI::config()->get('system', 'display_link_length');
		} else {
			$max_length = 30;
		}

		$text = preg_replace_callback(
			"/\[url\](.*?)\[\/url\]/ism",
			function ($match) use ($max_length) {
				return "[url=" . self::escapeUrl($match[1]) . "]" . Strings::getStyledURL($match[1], $max_length) . "[/url]";
			},
			$text
		);
		$text = preg_replace_callback(
			"/\[url\=(.*?)\](.*?)\[\/url\]/ism",
			function ($match) use ($max_length) {
				if ($match[1] == $match[2]) {
					return "[url=" . self::escapeUrl($match[1]) . "]" . Strings::getStyledURL($match[2], $max_length) . "[/url]";
				} else {
					return "[url=" . self::escapeUrl($match[1]) . "]" . $match[2] . "[/url]";
				}
			},
			$text
		);
		return $text;
	}

	private static function convertMailToHtml(string $text): string
	{
		$text = preg_replace_callback("/\[(mail)\](.*?)\[\/mail\]/ism", [self::class, 'sanitizeLinksCallback'], $text);
		$text = preg_replace("/\[mail\](.*?)\[\/mail\]/", '<a href="mailto:$1">$1</a>', $text);
		$text = preg_replace("/\[mail\=(.*?)\](.*?)\[\/mail\]/", '<a href="mailto:$1">$2</a>', $text);
		return $text;
	}

	private static function convertSharesToHtml(string $text, int $simple_html, bool $try_oembed, int $uriid): string
	{
		// Shared content
		// when the content is meant exporting to other systems then remove the avatar picture since this doesn't really look good on these systems
		if (!$try_oembed) {
			$text = preg_replace("/\[share(.*?)avatar\s?=\s?'.*?'\s?(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "\n[share$1$2]$3[/share]", $text);
		}

		$text = self::convertShare(
			$text,
			function (array $attributes, array $author_contact, $content, $is_quote_share) use ($simple_html) {
				return self::convertShareCallback($attributes, $author_contact, $content, $is_quote_share, $simple_html);
			},
			$uriid
		);

		return $text;
	}

	private static function cleanupHtml(string $text): string
	{
		/// @todo What is the meaning of these lines?
		$text = preg_replace('/\[\&amp\;([#a-z0-9]+)\;\]/', '&$1;', $text);
		$text = preg_replace('/\&\#039\;/', '\'', $text);

		// Currently deactivated, it made problems with " inside of alt texts.
		//$text = preg_replace('/\&quot\;/', '"', $text);

		// fix any escaped ampersands that may have been converted into links
		$text = preg_replace('/\<([^>]*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism', '<$1$2=$3&$4>', $text);

		// sanitizes src attributes (http and redir URLs for displaying in a web page, cid used for inline images in emails)
		$allowed_src_protocols = ['//', 'http://', 'https://', 'contact/redir/', 'cid:'];

		array_walk($allowed_src_protocols, function (&$value) {
			$value = preg_quote($value, '#');
		});

		$text = preg_replace(
			'#<([^>]*?)(src)="(?!' . implode('|', $allowed_src_protocols) . ')(.*?)"(.*?)>#ism',
			'<$1$2=""$4 data-original-src="$3" class="invalid-src" title="' . DI::l10n()->t('Invalid source protocol') . '">',
			$text
		);

		// sanitize href attributes (only allowlisted protocols URLs)
		// default value for backward compatibility
		$allowed_link_protocols = DI::config()->get('system', 'allowed_link_protocols', []);

		// Always allowed protocol even if config isn't set or not including it
		$allowed_link_protocols[] = '//';
		$allowed_link_protocols[] = 'http://';
		$allowed_link_protocols[] = 'https://';
		$allowed_link_protocols[] = 'contact/redir/';

		array_walk($allowed_link_protocols, function (&$value) {
			$value = preg_quote($value, '#');
		});

		$regex = '#<([^>]*?)(href)="(?!' . implode('|', $allowed_link_protocols) . ')(.*?)"(.*?)>#ism';
		$text  = preg_replace($regex, '<$1$2="javascript:void(0)"$4 data-original-href="$3" class="invalid-href" title="' . DI::l10n()->t('Invalid link protocol') . '">', $text);

		return $text;
	}

	/**
	 * Strips the "abstract" tag from the provided text
	 *
	 * @param string $text The text with BBCode
	 * @return string The same text - but without "abstract" element
	 */
	public static function stripAbstract(string $text): string
	{
		DI::profiler()->startRecording('rendering');

		$text = self::performWithEscapedTags($text, ['code', 'noparse', 'nobb', 'pre'], function ($text) {
			$text = preg_replace("/[\s|\n]*\[abstract\].*?\[\/abstract\][\s|\n]*/ism", ' ', $text);
			$text = preg_replace("/[\s|\n]*\[abstract=.*?\].*?\[\/abstract][\s|\n]*/ism", ' ', $text);
			return $text;
		});

		DI::profiler()->stopRecording();
		return $text;
	}

	/**
	 * Returns the value of the "abstract" element
	 *
	 * @param string $text  The text that maybe contains the element
	 * @param string $addon The addon for which the abstract is meant for
	 * @return string The abstract
	 */
	public static function getAbstract(string $text, string $addon = ''): string
	{
		DI::profiler()->startRecording('rendering');
		$addon = strtolower($addon);

		$abstract = self::performWithEscapedTags($text, ['code', 'noparse', 'nobb', 'pre'], function ($text) use ($addon) {
			if ($addon && preg_match('#\[abstract=' . preg_quote($addon, '#') . '](.*?)\[/abstract]#ism', $text, $matches)) {
				return $matches[1];
			}

			if (preg_match("#\[abstract](.*?)\[/abstract]#ism", $text, $matches)) {
				return $matches[1];
			}

			return '';
		});

		DI::profiler()->stopRecording();
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
	private static function bbCodeMention2DiasporaCallback(array $match): string
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
	public static function toMarkdown(string $text, bool $for_diaspora = true): string
	{
		DI::profiler()->startRecording('rendering');
		$original_text = $text;

		// Since Diaspora is creating a summary for links, this function removes them before posting
		if ($for_diaspora) {
			$text = self::removeShareInformation($text);
		}

		/**
		 * Transform #tags, strip off the [url] and replace spaces with underscore
		 */
		$url_search_string = "^\[\]";
		$text              = preg_replace_callback(
			"/#\[url\=([$url_search_string]*)\](.*?)\[\/url\]/i",
			function ($matches) {
				return '#' . str_replace(' ', '_', $matches[2]);
			},
			$text
		);

		// Converting images with size parameters to simple images. Markdown doesn't know it.
		$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $text);

		// Convert it to HTML - don't try oembed
		if ($for_diaspora) {
			$text = self::convertForUriId(0, $text, self::DIASPORA);

			// Add all tags that maybe were removed
			if (preg_match_all("/#\[url\=([$url_search_string]*)\](.*?)\[\/url\]/ism", $original_text, $tags)) {
				$tagline = '';
				foreach ($tags[2] as $tag) {
					$tag = html_entity_decode($tag, ENT_QUOTES, 'UTF-8');
					if (!strpos(html_entity_decode($text, ENT_QUOTES, 'UTF-8'), '#' . $tag)) {
						$tagline .= '#' . $tag . ' ';
					}
				}
				$text = $text . ' ' . $tagline;
			}
		} else {
			$text = self::convertForUriId(0, $text, self::CONNECTORS);
		}

		// If a link is followed by a quote then there should be a newline before it
		// Maybe we should make this newline at every time before a quote.
		$text = str_replace(['</a><blockquote>'], ['</a><br><blockquote>'], $text);

		// The converter doesn't convert these elements
		$text = str_replace(['<div>', '</div>'], ['<p>', '</p>'], $text);

		// Now convert HTML to Markdown
		$text = HTML::toMarkdown($text);

		// Libertree has a problem with escaped hashtags.
		$text = str_replace(['\#'], ['#'], $text);

		// Remove any leading or trailing whitespace, as this will mess up
		// the Diaspora signature verification and cause the item to disappear
		$text = trim($text);

		if ($for_diaspora) {
			$url_search_string = "^\[\]";
			$text              = preg_replace_callback(
				"/([@!])\[(.*?)\]\(([$url_search_string]*?)\)/ism",
				[self::class, 'bbCodeMention2DiasporaCallback'],
				$text
			);
		}

		Hook::callAll('bb2diaspora', $text);

		DI::profiler()->stopRecording();
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
	 * @return array List of tag and person names
	 */
	public static function getTags(string $string): array
	{
		DI::profiler()->startRecording('rendering');
		$ret = [];

		self::performWithEscapedTags($string, ['noparse', 'pre', 'code', 'img', 'attachment'], function ($string) use (&$ret) {
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

		DI::profiler()->stopRecording();
		return array_unique($ret);
	}

	/**
	 * Expand tags to URLs, checks the tag is at the start of a line or preceded by a non-word character
	 *
	 * @param string $body HTML/BBCode
	 * @return string body with expanded tags
	 */
	public static function expandTags(string $body): string
	{
		return preg_replace_callback(
			"/(?<=\W|^)([!#@])([^\^ \x0D\x0A,;:?'\"]*[^\^ \x0D\x0A,;:?!'\".])/",
			function (array $match) {
				switch ($match[1]) {
					case '!':
					case '@':
						$contact = Contact::getByURL($match[2]);
						if (!empty($contact)) {
							return $match[1] . '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
						} else {
							return $match[1] . $match[2];
						}
						break;

					case '#':
					default:
						return $match[1] . '[url=' . DI::baseUrl() . '/search?tag=' . urlencode($match[2]) . ']' . $match[2] . '[/url]';
				}
			},
			$body
		);
	}

	/**
	 * Perform a custom function on a text after having escaped blocks enclosed in the provided tag list.
	 *
	 * @param string   $text HTML/BBCode
	 * @param array    $tagList A list of tag names, e.g ['noparse', 'nobb', 'pre']
	 * @param callable $callback
	 * @return string
	 * @see Strings::performWithEscapedBlocks
	 */
	public static function performWithEscapedTags(string $text, array $tagList, callable $callback): string
	{
		$tagList = array_map('preg_quote', $tagList);

		return Strings::performWithEscapedBlocks($text, '#\[(?:' . implode('|', $tagList) . ').*?\[/(?:' . implode('|', $tagList) . ')]#ism', $callback);
	}

	/**
	 * Replaces mentions in the provided message body in BBCode links for the provided user and network if any
	 *
	 * @param string $body HTML/BBCode
	 * @param int $profile_uid Profile user id
	 * @param string $network Network name
	 * @return string HTML/BBCode with inserted images
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function setMentions(string $body, $profile_uid = 0, $network = '')
	{
		DI::profiler()->startRecording('rendering');
		$body = self::performWithEscapedTags($body, ['noparse', 'pre', 'code', 'img'], function ($body) use ($profile_uid, $network) {
			$tags = self::getTags($body);

			$tagged = [];

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

				if (($success = Item::replaceTag($body, $profile_uid, $tag, $network)) && $success['replaced']) {
					$tagged[] = $tag;
				}
			}

			return $body;
		});

		DI::profiler()->stopRecording();
		return $body;
	}

	/**
	 * @param string      $author  Author display name
	 * @param string      $profile Author profile URL
	 * @param string      $avatar  Author profile picture URL
	 * @param string      $link    Post source URL
	 * @param string      $posted  Post created date
	 * @param string|null $guid    Post guid (if any)
	 * @param string|null $uri     Post uri (if any)
	 * @return string
	 * @TODO Rewrite to handle over whole record array
	 */
	public static function getShareOpeningTag(string $author, string $profile, string $avatar, string $link, string $posted, string $guid = null, string $uri = null): string
	{
		DI::profiler()->startRecording('rendering');
		$header = "[share author='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $author) .
			"' profile='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $profile) .
			"' avatar='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $avatar) .
			"' link='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $link) .
			"' posted='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $posted);

		if ($guid) {
			$header .= "' guid='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $guid);
		}

		if ($uri) {
			$header .= "' message_id='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $uri);
		}

		$header .= "']";

		DI::profiler()->stopRecording();
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
	 * @see ParseUrl::getSiteinfoCached
	 */
	public static function embedURL(string $url, bool $tryAttachment = true, string $title = null, string $description = null, string $tags = null): string
	{
		DI::profiler()->startRecording('rendering');
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

			DI::profiler()->stopRecording();
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
					$bbcode = "\n" . '[img=' . $url . '][/img]' . "\n";
					break;
			}

			DI::profiler()->stopRecording();
			return $bbcode;
		}

		unset($siteinfo['keywords']);

		// Bypass attachment if parse url for a comment
		if (!$tryAttachment) {
			DI::profiler()->stopRecording();
			return "\n" . '[url=' . $url . ']' . ($siteinfo['title'] ?? $url) . '[/url]';
		}

		// Format it as BBCode attachment
		$bbcode = "\n" . PageInfo::getFooterFromData($siteinfo);
		DI::profiler()->stopRecording();
		return $bbcode;
	}
}
