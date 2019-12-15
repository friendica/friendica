<?php

/**
 * @file src/Model/ItemContent.php
 */

namespace Friendica\Model;

use Friendica\Content\Text;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;

class ItemContent
{
	/**
	 * @brief Convert a message into plaintext for connectors to other networks
	 *
	 * @param array  $item           The message array that is about to be posted
	 * @param int    $limit          The maximum number of characters when posting to that network
	 * @param bool   $includedlinks  Has an attached link to be included into the message?
	 * @param int    $htmlmode       This controls the behavior of the BBCode conversion
	 * @param string $target_network Name of the network where the post should go to.
	 *
	 * @return array Same array structure than \Friendica\Content\Text\BBCode::getAttachedData
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   \Friendica\Content\Text\BBCode::getAttachedData
	 *
	 */
	public static function getPlaintextPost($item, $limit = 0, $includedlinks = false, $htmlmode = 2, $target_network = '')
	{
		// Remove hashtags
		$URLSearchString = '^\[\]';
		$body = preg_replace("/([#@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $item['body']);

		// Add an URL element if the text contains a raw link
		$body = preg_replace('/([^\]\=\'"]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism',
			'$1[url]$2[/url]', $body);

		// Remove the abstract
		$body = Text\BBCode::stripAbstract($body);

		// At first look at data that is attached via "type-..." stuff
		// This will hopefully replaced with a dedicated bbcode later
		//$post = self::getAttachedData($b['body']);
		$post = Text\BBCode::getAttachedData($body, $item);

		if (($item['title'] != '') && ($post['text'] != '')) {
			$post['text'] = trim($item['title'] . "\n\n" . $post['text']);
		} elseif ($item['title'] != '') {
			$post['text'] = trim($item['title']);
		}

		$abstract = '';

		// Fetch the abstract from the given target network
		if ($target_network != '') {
			$default_abstract = Text\BBCode::getAbstract($item['body']);
			$abstract = Text\BBCode::getAbstract($item['body'], $target_network);

			// If we post to a network with no limit we only fetch
			// an abstract exactly for this network
			if (($limit == 0) && ($abstract == $default_abstract)) {
				$abstract = '';
			}
		} else {// Try to guess the correct target network
			switch ($htmlmode) {
				case 8:
					$abstract = Text\BBCode::getAbstract($item['body'], Protocol::TWITTER);
					break;

				case 7:
					$abstract = Text\BBCode::getAbstract($item['body'], Protocol::STATUSNET);
					break;

				default: // We don't know the exact target.
					// We fetch an abstract since there is a posting limit.
					if ($limit > 0) {
						$abstract = Text\BBCode::getAbstract($item['body']);
					}
			}
		}

		if ($abstract != '') {
			$post['text'] = $abstract;

			if ($post['type'] == 'text') {
				$post['type'] = 'link';
				$post['url'] = $item['plink'];
			}
		}

		$html = Text\BBCode::convert($post['text'] . ($post['after'] ?? ''), false, $htmlmode);
		$msg = Text\HTML::toPlaintext($html, 0, true);
		$msg = trim(html_entity_decode($msg, ENT_QUOTES, 'UTF-8'));

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

			// If the link is already contained in the post, then it neeedn't to be added again
			// But: if the link is beyond the limit, then it has to be added.
			if (($link != '') && strstr($msg, $link)) {
				$pos = strpos($msg, $link);

				// Will the text be shortened in the link?
				// Or is the link the last item in the post?
				if (($limit > 0) && ($pos < $limit) && (($pos + 23 > $limit) || ($pos + strlen($link) == strlen($msg)))) {
					$msg = trim(str_replace($link, '', $msg));
				} elseif (($limit == 0) || ($pos < $limit)) {
					// The limit has to be increased since it will be shortened - but not now
					// Only do it with Twitter (htmlmode = 8)
					if (($limit > 0) && (strlen($link) > 23) && ($htmlmode == 8)) {
						$limit = $limit - 23 + strlen($link);
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

			// Twitter is using its own limiter, so we always assume that shortened links will have this length
			if (iconv_strlen($link, 'UTF-8') > 0) {
				$limit = $limit - 23;
			}

			if (iconv_strlen($msg, 'UTF-8') > $limit) {
				if (($post['type'] == 'text') && isset($post['url'])) {
					$post['url'] = $item['plink'];
				} elseif (!isset($post['url'])) {
					$limit = $limit - 23;
					$post['url'] = $item['plink'];
				} elseif (strpos($item['body'], '[share') !== false) {
					$post['url'] = $item['plink'];
				} elseif (PConfig::get($item['uid'], 'system', 'no_intelligent_shortening')) {
					$post['url'] = $item['plink'];
				}
				$msg = Text\Plaintext::shorten($msg, $limit);
			}
		}

		$post['text'] = trim($msg);

		return $post;
	}
}
