<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\ParseUrl;
use Friendica\Util\Strings;

/**
 * Extracts trailing URLs from post bodies to transform them in enriched attachment tags through Site Info query
 */
class PageInfo
{
	/**
	 * @param string $body
	 * @param bool   $searchNakedUrls
	 * @param bool   $no_photos
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function searchAndAppendToBody(string $body, bool $searchNakedUrls = false, bool $no_photos = false)
	{
		DI::logger()->debug('add_page_info_to_body: fetch page info for body', ['body' => $body]);

		$url = self::getRelevantUrlFromBody($body, $searchNakedUrls);
		if (!$url) {
			return $body;
		}

		$data = self::queryUrl($url);
		if (!$data) {
			return $body;
		}

		return self::appendDataToBody($body, $data, $no_photos);
	}

	/**
	 * @param string $body
	 * @param array  $data
	 * @param bool   $no_photos
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function appendDataToBody(string $body, array $data, bool $no_photos = false): string
	{
		// Only one [attachment] tag per body is allowed
		$existingAttachmentPos = strpos($body, '[attachment');
		if ($existingAttachmentPos !== false) {
			$linkTitle = $data['title'] ?: $data['url'];
			// Additional link attachments are prepended before the existing [attachment] tag
			$body = substr_replace($body, "\n[bookmark=" . $data['url'] . ']' . $linkTitle . "[/bookmark]\n", $existingAttachmentPos, 0);
		} else {
			$footer = self::getFooterFromData($data, $no_photos);
			$body   = self::stripTrailingUrlFromBody($body, $data['url']);
			$body .= "\n" . $footer;
		}

		return $body;
	}

	/**
	 * @param string $url
	 * @param bool $no_photos
	 * @param string $photo
	 * @param bool $keywords
	 * @param string $keyword_denylist
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getFooterFromUrl(string $url, bool $no_photos = false, string $photo = '', bool $keywords = false, string $keyword_denylist = ''): string
	{
		$data = self::queryUrl($url, $photo, $keywords, $keyword_denylist);

		return self::getFooterFromData($data, $no_photos);
	}

	/**
	 * @param array $data
	 * @param bool  $no_photos
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getFooterFromData(array $data, bool $no_photos = false): string
	{
		Hook::callAll('page_info_data', $data);

		if (empty($data['type'])) {
			return '';
		}

		// It maybe is a rich content, but if it does have everything that a link has,
		// then treat it that way
		if (($data['type'] == 'rich') && is_string($data['title']) &&
			is_string($data['text']) && !empty($data['images'])) {
			$data['type'] = 'link';
		}

		$data['title'] = $data['title'] ?? '';

		if ((($data['type'] != 'link') && ($data['type'] != 'video') && ($data['type'] != 'photo')) || ($data['title'] == $data['url'])) {
			return '';
		}

		if ($no_photos && ($data['type'] == 'photo')) {
			return '';
		}

		// Escape some bad characters
		$text = "[attachment";

		foreach (['type', 'url', 'title', 'alternative_title', 'publisher_name', 'publisher_url', 'publisher_img', 'author_name', 'author_url', 'author_img'] as $field) {
			if (!empty($data[$field])) {
				$text .= " " . $field . "='" . str_replace(['[', ']'], ['&#91;', '&#93;'], htmlentities($data[$field], ENT_QUOTES, 'UTF-8', false)) . "'";
			}
		}

		if (empty($data['text'])) {
			$data['text'] = '';
		}

		// Only embed a picture link when it seems to be a valid picture ("width" is set)
		if (!empty($data['images']) && !empty($data['images'][0]['width'])) {
			$preview = str_replace(['[', ']'], ['&#91;', '&#93;'], htmlentities($data['images'][0]['src'], ENT_QUOTES, 'UTF-8', false));
			// if the preview picture is larger than 500 pixels then show it in a larger mode
			// But only, if the picture isn't higher than large (To prevent huge posts)
			if (!DI::config()->get('system', 'always_show_preview') && ($data['images'][0]['width'] >= 500)
				&& ($data['images'][0]['width'] >= $data['images'][0]['height'])) {
				$text .= " image='" . $preview . "'";
			} else {
				$text .= " preview='" . $preview . "'";

				if (empty($data['text'])) {
					$data['text'] = $data['title'];
				}

				if (empty($data['text'])) {
					$data['text'] = $data['url'];
				}
			}
		}

		$text .= ']' . str_replace(['[', ']'], ['&#91;', '&#93;'], $data['text']) . '[/attachment]';

		$hashtags = '';
		if (!empty($data['keywords'])) {
			$hashtags = "\n";
			foreach ($data['keywords'] as $keyword) {
				/// @TODO make a positive list of allowed characters
				$hashtag = str_replace([' ', '+', '/', '.', '#', '@', "'", '"', '’', '`', '(', ')', '„', '“'], '', $keyword);
				$hashtags .= '#[url=' . DI::baseUrl() . '/search?tag=' . urlencode($hashtag) . ']' . $hashtag . '[/url] ';
			}
		}

		return $text . $hashtags;
	}

	/**
	 * @param string  $url
	 * @param string $photo
	 * @param bool $keywords
	 * @param string $keyword_denylist
	 * @return array|bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function queryUrl(string $url, string $photo = '', bool $keywords = false, string $keyword_denylist = '')
	{
		$data = ParseUrl::getSiteinfoCached($url);

		if ($photo != '') {
			$data['images'][0]['src'] = $photo;
		}

		if (!$keywords) {
			unset($data['keywords']);
		} elseif ($keyword_denylist && !empty($data['keywords'])) {
			$list = explode(', ', $keyword_denylist);

			foreach ($list as $keyword) {
				$keyword = trim($keyword);

				$index = array_search($keyword, $data['keywords']);
				if ($index !== false) {
					unset($data['keywords'][$index]);
				}
			}
		}

		DI::logger()->debug('fetch page info for URL', ['url' => $url, 'data' => $data]);

		return $data;
	}

	/**
	 * @param string $url
	 * @param string $photo
	 * @param string $keyword_denylist
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getTagsFromUrl(string $url, string $photo = '', string $keyword_denylist = ''): array
	{
		$data = self::queryUrl($url, $photo, true, $keyword_denylist);

		if (empty($data['keywords'])) {
			return [];
		}

		$taglist = [];
		foreach ($data['keywords'] as $keyword) {
			$hashtag = str_replace(
				[' ', '+', '/', '.', '#', "'"],
				['', '', '', '', '', ''],
				$keyword
			);

			$taglist[] = $hashtag;
		}

		return $taglist;
	}

	/**
	 * Picks a non-hashtag, non-mention, schemeful URL at the end of the provided body string to be converted into Page Info.
	 *
	 * @param string $body
	 * @param bool   $searchNakedUrls Whether we should pick a naked URL (outside of BBCode tags) as a last resort
	 * @return string|null
	 */
	public static function getRelevantUrlFromBody(string $body, bool $searchNakedUrls = false)
	{
		$URLSearchString = 'https?://[^\[\]]*';

		// Fix for Mastodon where the mentions are in a different format
		$body = preg_replace("~\[url=($URLSearchString)]([#!@])(.*?)\[/url]~is", '$2[url=$1]$3[/url]', $body);

		// Remove all hashtags and mentions
		$body = preg_replace("/([#@!])\[url\=(.*?)\](.*?)\[\/url\]/ism", '', $body);

		// Search for pure links
		preg_match("/\[url\](https?:.*?)\[\/url\]/ism", $body, $matches);

		if (!$matches) {
			// Search for links with descriptions
			preg_match("/\[url\=(https?:.*?)\].*?\[\/url\]/ism", $body, $matches);
		}

		if (!$matches && $searchNakedUrls) {
			preg_match(Strings::autoLinkRegEx(), $body, $matches);
			if ($matches && !Strings::endsWith($body, $matches[1])) {
				unset($matches);
			}
		}

		return $matches[1] ?? null;
	}

	/**
	 * Remove the provided URL from the body if it is at the end of it.
	 * Keep the link label if it isn't the full URL or a shortened version of it.
	 *
	 * @param string $body
	 * @param string $url
	 * @return string
	 */
	protected static function stripTrailingUrlFromBody(string $body, string $url): string
	{
		$quotedUrl = preg_quote($url, '#');
		$body      = preg_replace_callback("#(?:
			\[url]$quotedUrl\[/url]|
			\[url=$quotedUrl]$quotedUrl\[/url]|
			\[url=$quotedUrl]([^[]*?)\[/url]|
			$quotedUrl
		)$#isx", function ($match) use ($url) {
			// Stripping URLs with no label
			if (empty($match[1])) {
				return '';
			}

			// Stripping link labels that include a shortened version of the URL
			$trimMatch = trim($match[1], '.…');
			if (!empty($trimMatch) && strpos($url, $trimMatch) !== false) {
				return '';
			}

			// Keep all other labels
			return $match[1];
		}, $body);

		return rtrim($body);
	}
}
