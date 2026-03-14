<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Util\Network;
use IntlChar;

class Plaintext
{
	// Assumed length of an URL when shortened via the network's own url shortener (e.g. Twitter)
	public const URL_LENGTH = 23;

	/**
	 * Shortens message
	 *
	 * @param  string $msg
	 * @param  int    $limit
	 * @param  int    $uid
	 * @return string
	 *
	 * @todo For Twitter URLs aren't shortened, but they have to be calculated as if.
	 */
	public static function shorten(string $msg, int $limit, int $uid = 0): string
	{
		$ellipsis = html_entity_decode("&#x2026;", ENT_QUOTES, 'UTF-8');

		if (!empty($uid) && DI::pConfig()->get($uid, 'system', 'simple_shortening')) {
			return mb_substr(mb_substr(trim($msg), 0, $limit), 0, -3) . $ellipsis;
		}

		$lines   = explode("\n", $msg);
		$msg     = "";
		$recycle = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
		foreach ($lines as $row => $line) {
			if (mb_strlen(trim($msg . "\n" . $line)) <= $limit) {
				$msg = trim($msg . "\n" . $line);
			} elseif (($msg == "") || (($row == 1) && (substr($msg, 0, 4) == $recycle))) {
				// Is the new message empty by now or is it a reshared message?
				$msg = mb_substr(mb_substr(trim($msg . "\n" . $line), 0, $limit), 0, -3) . $ellipsis;
			} else {
				break;
			}
		}

		return $msg;
	}

	/**
	 * Returns the character positions of the provided boundaries, optionally skipping a number of first occurrences
	 *
	 * @param string $text        Text to search
	 * @param string $open        Left boundary
	 * @param string $close       Right boundary
	 * @param int    $occurrences Number of first occurrences to skip
	 * @return boolean|array
	 */
	public static function getBoundariesPosition($text, $open, $close, $occurrences = 0)
	{
		if ($occurrences < 0) {
			$occurrences = 0;
		}

		$start_pos = -1;
		for ($i = 0; $i <= $occurrences; $i++) {
			if ($start_pos !== false) {
				$start_pos = strpos($text, $open, $start_pos + 1);
			}
		}

		if ($start_pos === false) {
			return false;
		}

		$end_pos = strpos($text, $close, $start_pos);

		if ($end_pos === false) {
			return false;
		}

		$res = ['start' => $start_pos, 'end' => $end_pos];

		return $res;
	}

	/**
	 * Convert a message into plaintext for connectors to other networks
	 *
	 * @param array  $item           The message array that is about to be posted
	 * @param int    $limit          The maximum number of characters when posting to that network
	 * @param bool   $includedlinks  Has an attached link to be included into the message?
	 * @param int    $htmlmode       This controls the behavior of the BBCode conversion
	 *
	 * @return array Same array structure than \Friendica\Content\Text\BBCode::getAttachedData
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   \Friendica\Content\Text\BBCode::getAttachedData
	 */
	public static function getPost(array $item, int $limit = 0, bool $includedlinks = false, int $htmlmode = BBCode::MASTODON_API)
	{
		// Fetch attached media information
		$post = self::getPostMedia($item);

		if (($item['title'] != '') && ($post['text'] != '')) {
			$post['text'] = trim($item['title'] . "\n\n" . $post['text']);
		} elseif ($item['title'] != '') {
			$post['text'] = trim($item['title']);
		}

		$abstract = '';

		// Fetch the abstract from the given target network
		switch ($htmlmode) {
			case BBCode::TWITTER:
				$abstract = BBCode::getAbstract($item['body'], Protocol::TWITTER);
				break;

			case BBCode::BLUESKY:
				$abstract = BBCode::getAbstract($item['body'], Protocol::BLUESKY);
				break;

			default: // We don't know the exact target.
				// We fetch an abstract since there is a posting limit.
				if ($limit > 0) {
					$abstract = BBCode::getAbstract($item['body']);
				}
		}

		if ($abstract != '') {
			$post['text'] = $abstract;

			if ($post['type'] == 'text') {
				$post['type'] = 'link';
				$post['url']  = $item['plink'];
			}
		}

		$html = BBCode::convertForUriId($item['uri-id'], $post['text'] . ($post['after'] ?? ''), $htmlmode);
		$msg  = HTML::toPlaintext($html, 0, true);
		$msg  = trim(html_entity_decode($msg, ENT_QUOTES, 'UTF-8'));

		$complete_msg = $msg;

		$link = '';
		if ($includedlinks) {
			if ($post['type'] == 'link') {
				$link = $post['url'];
			} elseif ($post['type'] == 'text') {
				$link = $post['url'] ?? '';
			} elseif ($post['type'] == 'video') {
				$link = $post['url'];
			} elseif ($post['type'] == 'photo') {
				$link = $post['image'];
			}

			if (($msg == '') && isset($post['title'])) {
				$msg = trim($post['title']);
			}

			if (($msg == '') && isset($post['description'])) {
				$msg = trim($post['description']);
			}

			// If the link is already contained in the post, then it needn't to be added again
			// But: if the link is beyond the limit, then it has to be added.
			if (($link != '') && strstr($msg, (string) $link)) {
				$pos = strpos($msg, (string) $link);

				// Will the text be shortened in the link?
				// Or is the link the last item in the post?
				if (($limit > 0) && ($pos < $limit) && (($pos + self::URL_LENGTH > $limit) || ($pos + mb_strlen($link) == mb_strlen($msg)))) {
					$msg = trim(str_replace($link, '', $msg));
				} elseif (($limit == 0) || ($pos < $limit)) {
					// The limit has to be increased since it will be shortened - but not now
					// Only do it with Twitter
					if (($limit > 0) && (mb_strlen($link) > self::URL_LENGTH) && ($htmlmode == BBCode::TWITTER)) {
						$limit = $limit - self::URL_LENGTH + mb_strlen($link);
					}

					$link = '';

					if ($post['type'] == 'text') {
						unset($post['url']);
					}
				}
			}
		}

		if ($limit > 0) {
			// Reduce multiple spaces
			// When posted to a network with limited space, we try to gain space where possible
			while (strpos($msg, '  ') !== false) {
				$msg = str_replace('  ', ' ', $msg);
			}

			if (!in_array($link, ['', $item['plink']]) && ($post['type'] != 'photo') && (strpos($complete_msg, (string) $link) === false)) {
				$complete_msg .= "\n" . $link;
			}

			$post['parts'] = self::getParts(trim($complete_msg), $limit);

			// Twitter is using its own limiter, so we always assume that shortened links will have this length
			if (mb_strlen($link) > 0) {
				$limit = $limit - self::URL_LENGTH;
			}

			if (mb_strlen($msg) > $limit) {
				if (($post['type'] == 'text') && isset($post['url'])) {
					$post['url'] = $item['plink'];
				} elseif (!isset($post['url'])) {
					$limit       = $limit - self::URL_LENGTH;
					$post['url'] = $item['plink'];
				} elseif (strpos($item['body'], '[share') !== false) {
					$post['url'] = $item['plink'];
				} elseif (DI::pConfig()->get($item['uid'], 'system', 'no_intelligent_shortening')) {
					$post['url'] = $item['plink'];
				}
				$msg = self::shorten($msg, $limit, $item['uid']);
			}
		}

		$post['text'] = trim($msg);

		return $post;
	}

	/**
	 * Split the message in parts
	 *
	 * @param string  $message
	 * @param integer $baselimit
	 * @return array
	 */
	private static function getParts(string $message, int $baselimit): array
	{
		$parts     = [];
		$part      = '';
		$break_pos = 0;
		$comma_pos = 0;
		$pos       = 0;
		$word      = '';

		$limit = $baselimit;

		while ($message) {
			$pos_word      = mb_strpos($message, ' ');
			$pos_paragraph = mb_strpos($message, "\n");

			if (($pos_word !== false) && ($pos_paragraph !== false)) {
				$pos = min($pos_word, $pos_paragraph) + 1;
			} elseif ($pos_word !== false) {
				$pos = $pos_word + 1;
			} elseif ($pos_paragraph !== false) {
				$pos = $pos_paragraph + 1;
			} else {
				$word    = $message;
				$message = '';
			}

			if (trim($message)) {
				$word    = mb_substr($message, 0, $pos);
				$message = trim(mb_substr($message, $pos));
			}

			if (Network::isValidHttpUrl(trim($word))) {
				$limit += mb_strlen(trim($word)) - self::URL_LENGTH;
			}

			$break = mb_strrpos($word, "\n") !== false;
			if (!$break && (mb_strrpos($word, '. ') !== false || mb_strrpos($word, '? ') !== false || mb_strrpos($word, '! ') !== false)) {
				$break = IntlChar::isupper(mb_substr($message, 0, 1));
			}

			$comma = (mb_strrpos($word, ', ') !== false) && IntlChar::isalpha(mb_substr($message, 0, 1));

			if ((mb_strlen($part . $word) > $limit - 8) && ($parts || (mb_strlen($part . $word . $message) > $limit))) {
				if ($break_pos) {
					$parts[] = trim(mb_substr($part, 0, $break_pos));
					$part    = mb_substr($part, $break_pos);
				} elseif ($comma_pos) {
					$parts[] = trim(mb_substr($part, 0, $comma_pos));
					$part    = mb_substr($part, $comma_pos);
				} else {
					$parts[] = trim($part);
					$part    = '';
				}
				$limit     = $baselimit;
				$break_pos = 0;
				$comma_pos = 0;
			} elseif ($break) {
				$break_pos = $pos + mb_strlen($part);
			} elseif ($comma) {
				$comma_pos = $pos + mb_strlen($part);
			}
			$part .= $word;
		}
		$parts[] = trim($part);

		if (count($parts) > 1) {
			foreach ($parts as $key => $part) {
				$parts[$key] .= ' (' . ($key + 1) . '/' . count($parts) . ')';
			}
		}

		return $parts;
	}

	/**
	 * Fetch attached media to the post and simplify the body.
	 *
	 * @param array $item
	 * @return array
	 */
	private static function getPostMedia(array $item): array
	{
		$post = ['type' => 'text', 'images' => [], 'remote_images' => []];

		// Remove mentions and hashtag links
		$URLSearchString = '^\[\]';
		$post['text']    = preg_replace("/([#!@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $item['body']);

		// Remove abstract
		$post['text'] = BBCode::stripAbstract($post['text']);
		// Remove attached links
		$post['text'] = BBCode::removeAttachment($post['text']);
		// Remove any links
		$post['text'] = Post\Media::removeFromBody($post['text']);

		$images = Post\Media::getByURIId($item['uri-id'], [Post\Media::IMAGE]);
		if (!empty($item['quote-uri-id']) && ($item['quote-uri-id'] != $item['uri-id'])) {
			$images = array_merge($images, Post\Media::getByURIId($item['quote-uri-id'], [Post\Media::IMAGE]));
		}
		foreach ($images as $image) {
			if ($id = Photo::getIdForName($image['url'])) {
				$post['images'][] = ['url' => $image['url'], 'description' => $image['description'], 'id' => $id];
			} else {
				$post['remote_images'][] = ['url' => $image['url'], 'description' => $image['description']];
			}
		}

		if (empty($post['images'])) {
			unset($post['images']);
		}

		if (empty($post['remote_images'])) {
			unset($post['remote_images']);
		}

		if (!empty($post['images'])) {
			$post['type']              = 'photo';
			$post['image']             = $post['images'][0]['url'];
			$post['image_description'] = $post['images'][0]['description'];
		} elseif (!empty($post['remote_images'])) {
			$post['type']              = 'photo';
			$post['image']             = $post['remote_images'][0]['url'];
			$post['image_description'] = $post['remote_images'][0]['description'];
		}

		// Look for audio or video links
		$media = Post\Media::getByURIId($item['uri-id'], [Post\Media::AUDIO, Post\Media::VIDEO]);
		if (!empty($item['quote-uri-id']) && ($item['quote-uri-id'] != $item['uri-id'])) {
			$media = array_merge($media, Post\Media::getByURIId($item['quote-uri-id'], [Post\Media::AUDIO, Post\Media::VIDEO]));
		}

		foreach ($media as $medium) {
			if (in_array($medium['type'], [Post\Media::AUDIO, Post\Media::VIDEO])) {
				$post['type'] = 'link';
				$post['url']  = $medium['url'];
			}
		}

		// Look for an attached link
		$page = Post\Media::getByURIId($item['uri-id'], [Post\Media::HTML]);
		if (!empty($item['quote-uri-id']) && empty($page)) {
			$page = Post\Media::getByURIId($item['quote-uri-id'], [Post\Media::HTML]);
		}
		if (!empty($page)) {
			$post['type']        = 'link';
			$post['url']         = $page[0]['url'];
			$post['description'] = $page[0]['description'];
			$post['title']       = $page[0]['name'];

			if (empty($post['image']) && !empty($page[0]['preview'])) {
				$post['image'] = $page[0]['preview'];
			}
		}

		return $post;
	}
}
