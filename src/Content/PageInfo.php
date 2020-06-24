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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
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
	public static function appendToBody(string $body, bool $searchNakedUrls = false, bool $no_photos = false)
	{
		Logger::info('add_page_info_to_body: fetch page info for body', ['body' => $body]);

		$url = self::getRelevantUrlFromBody($body, $searchNakedUrls);
		if (!$url) {
			return $body;
		}

		$footer = self::getFooterFromUrl($url, $no_photos);
		if (!$footer) {
			return $body;
		}

		$body = self::stripTrailingUrlFromBody($body, $url);

		$body .= "\n" . $footer;

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
	public static function getFooterFromUrl(string $url, bool $no_photos = false, string $photo = '', bool $keywords = false, string $keyword_denylist = '')
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
	public static function getFooterFromData(array $data, bool $no_photos = false)
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
		$data['url'] = str_replace(['[', ']'], ['&#91;', '&#93;'], htmlentities($data['url'], ENT_QUOTES, 'UTF-8', false));
		$data['title'] = str_replace(['[', ']'], ['&#91;', '&#93;'], htmlentities($data['title'], ENT_QUOTES, 'UTF-8', false));

		$text = "[attachment type='" . $data['type'] . "'";

		if (empty($data['text'])) {
			$data['text'] = $data['title'];
		}

		if (empty($data['text'])) {
			$data['text'] = $data['url'];
		}

		if (!empty($data['url'])) {
			$text .= " url='" . $data['url'] . "'";
		}

		if (!empty($data['title'])) {
			$text .= " title='" . $data['title'] . "'";
		}

		// Only embedd a picture link when it seems to be a valid picture ("width" is set)
		if (!empty($data['images']) && !empty($data['images'][0]['width'])) {
			$preview = str_replace(['[', ']'], ['&#91;', '&#93;'], htmlentities($data['images'][0]['src'], ENT_QUOTES, 'UTF-8', false));
			// if the preview picture is larger than 500 pixels then show it in a larger mode
			// But only, if the picture isn't higher than large (To prevent huge posts)
			if (!DI::config()->get('system', 'always_show_preview') && ($data['images'][0]['width'] >= 500)
				&& ($data['images'][0]['width'] >= $data['images'][0]['height'])) {
				$text .= " image='" . $preview . "'";
			} else {
				$text .= " preview='" . $preview . "'";
			}
		}

		$text .= ']' . $data['text'] . '[/attachment]';

		$hashtags = '';
		if (!empty($data['keywords'])) {
			$hashtags = "\n";
			foreach ($data['keywords'] as $keyword) {
				/// @TODO make a positive list of allowed characters
				$hashtag = str_replace([' ', '+', '/', '.', '#', '@', "'", '"', '’', '`', '(', ')', '„', '“'], '', $keyword);
				$hashtags .= '#[url=' . DI::baseUrl() . '/search?tag=' . $hashtag . ']' . $hashtag . '[/url] ';
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
		$data = ParseUrl::getSiteinfoCached($url, true);

		if ($photo != '') {
			$data['images'][0]['src'] = $photo;
		}

		if (!$keywords) {
			unset($data['keywords']);
		} elseif ($keyword_denylist) {
			$list = explode(', ', $keyword_denylist);

			foreach ($list as $keyword) {
				$keyword = trim($keyword);

				$index = array_search($keyword, $data['keywords']);
				if ($index !== false) {
					unset($data['keywords'][$index]);
				}
			}
		}

		Logger::info('fetch page info for URL', ['url' => $url, 'data' => $data]);

		return $data;
	}

	/**
	 * @param string $url
	 * @param string $photo
	 * @param string $keyword_denylist
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getTagsFromUrl(string $url, string $photo = '', string $keyword_denylist = '')
	{
		$data = self::queryUrl($url, $photo, true, $keyword_denylist);

		$taglist = [];
		foreach ($data['keywords'] as $keyword) {
			$hashtag = str_replace([' ', '+', '/', '.', '#', "'"],
				['', '', '', '', '', ''], $keyword);

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
	protected static function getRelevantUrlFromBody(string $body, bool $searchNakedUrls = false)
	{
		$URLSearchString = 'https?://[^\[\]]*';

		// Fix for Mastodon where the mentions are in a different format
		$body = preg_replace("~\[url=($URLSearchString)]([#!@])(.*?)\[/url]~is", '$2[url=$1]$3[/url]', $body);

		preg_match("~(?<![!#@])\[url]($URLSearchString)\[/url]$~is", $body, $matches);

		if (!$matches) {
			preg_match("~(?<![!#@])\[url=($URLSearchString)].*\[/url]$~is", $body, $matches);
		}

		if (!$matches && $searchNakedUrls) {
			preg_match('~(?<=\W|^)(?<![=\]])(https?://.+)$~is', $body, $matches);
			if ($matches && !Strings::endsWith($body, $matches[1])) {
				unset($matches);
			}
		}

		return $matches[1] ?? null;
	}

	/**
	 * Remove the provided URL from the body if it is at the end of it.
	 * Keep the link label if it isn't the full URL.
	 *
	 * @param string $body
	 * @param string $url
	 * @return string|string[]|null
	 */
	protected static function stripTrailingUrlFromBody(string $body, string $url)
	{
		$quotedUrl = preg_quote($url, '#');
		$body = preg_replace("#(?:
			\[url]$quotedUrl\[/url]|
			\[url=$quotedUrl]$quotedUrl\[/url]|
			\[url=$quotedUrl]([^[]*?)\[/url]|
			$quotedUrl
		)$#isx", '$1', $body);

		return $body;
	}
}
