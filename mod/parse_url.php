<?php

/**
 * @file mod/parse_url.php
 * @brief The parse_url module
 *
 * This module does parse an url for embeddable content (audio, video, image files or link)
 * information and does format this information to BBCode
 *
 * @see ParseUrl::getSiteinfo() for more information about scraping embeddable content
 */
use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;

require_once 'include/items.php';

function parse_url_content(App $a)
{
	$text = null;
	$str_tags = '';

	$br = "\n";

	if (!empty($_GET['binurl'])) {
		$url = trim(hex2bin($_GET['binurl']));
	} else {
		$url = trim($_GET['url']);
	}

	if (!empty($_GET['title'])) {
		$title = strip_tags(trim($_GET['title']));
	}

	if (!empty($_GET['description'])) {
		$text = strip_tags(trim($_GET['description']));
	}

	if (!empty($_GET['tags'])) {
		$arr_tags = ParseUrl::convertTagsToArray($_GET['tags']);
		if (count($arr_tags)) {
			$str_tags = $br . implode(' ', $arr_tags) . $br;
		}
	}

	// Add url scheme if it is missing
	$arrurl = parse_url($url);
	if (empty($arrurl['scheme'])) {
		if (!empty($arrurl['host'])) {
			$url = 'http:' . $url;
		} else {
			$url = 'http://' . $url;
		}
	}

	Logger::log($url);

	// Check if the URL is an image, video or audio file. If so format
	// the URL with the corresponding BBCode media tag
	$redirects = 0;
	// Fetch the header of the URL
	$curlResponse = Network::curl($url, false, $redirects, ['novalidate' => true, 'nobody' => true]);

	if ($curlResponse->isSuccess()) {
		// Convert the header fields into an array
		$hdrs = [];
		$h = explode("\n", $curlResponse->getHeader());
		foreach ($h as $l) {
			$header = array_map('trim', explode(':', trim($l), 2));
			if (count($header) == 2) {
				list($k, $v) = $header;
				$hdrs[$k] = $v;
			}
		}
		$type = null;
		if (array_key_exists('Content-Type', $hdrs)) {
			$type = $hdrs['Content-Type'];
		}
		if ($type) {
			if (stripos($type, 'image/') !== false) {
				echo $br . '[img]' . $url . '[/img]' . $br;
				exit();
			}
			if (stripos($type, 'video/') !== false) {
				echo $br . '[video]' . $url . '[/video]' . $br;
				exit();
			}
			if (stripos($type, 'audio/') !== false) {
				echo $br . '[audio]' . $url . '[/audio]' . $br;
				exit();
			}
		}
	}


	$template = '[bookmark=%s]%s[/bookmark]%s';

	$arr = ['url' => $url, 'text' => ''];

	Hook::callAll('parse_link', $arr);

	if (strlen($arr['text'])) {
		echo $arr['text'];
		exit();
	}

	// If there is already some content information submitted we don't
	// need to parse the url for content.
	if (!empty($url) && !empty($title) && !empty($text)) {
		$title = str_replace(["\r", "\n"], ['', ''], $title);

		$text = '[quote]' . trim($text) . '[/quote]' . $br;

		$result = sprintf($template, $url, ($title) ? $title : $url, $text) . $str_tags;

		Logger::log('(unparsed): returns: ' . $result);

		echo $result;
		exit();
	}

	// Fetch the information directly from the webpage
	$siteinfo = ParseUrl::getSiteinfo($url);

	unset($siteinfo['keywords']);

	// Bypass attachment if parse url for a comment
	if (!empty($_GET['noAttachment'])) {
		echo $br . '[url=' . $url . ']' . $siteinfo['title'] . '[/url]';
		exit();
	}

	// Format it as BBCode attachment
	$info = add_page_info_data($siteinfo);

	echo $info;

	exit();
}

/**
 * @brief Legacy function to call ParseUrl::getSiteinfoCached
 *
 * Note: We have moved the function to ParseUrl.php. This function is only for
 * legacy support and will be remove in the future
 *
 * @param string $url         The url of the page which should be scraped
 * @param bool   $no_guessing If true the parse doens't search for
 *                            preview pictures
 * @param bool   $do_oembed   The false option is used by the function fetch_oembed()
 *                            to avoid endless loops
 *
 * @return array which contains needed data for embedding
 *
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @see   ParseUrl::getSiteinfoCached()
 *
 * @deprecated since version 3.6 use ParseUrl::getSiteinfoCached instead
 */
function parseurl_getsiteinfo_cached($url, $no_guessing = false, $do_oembed = true)
{
	$siteinfo = ParseUrl::getSiteinfoCached($url, $no_guessing, $do_oembed);
	return $siteinfo;
}
