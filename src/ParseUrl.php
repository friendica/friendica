<?php

/**
 * @file include/ParseUrl.php
 * @brief Get informations about a given URL
 */

namespace Friendica;

use \Friendica\Core\Config;

require_once("include/network.php");
require_once("include/Photo.php");
require_once("include/oembed.php");
require_once("include/xml.php");

/**
 * @brief Class with methods for extracting certain content from an url
 */
class ParseUrl {

	/**
	 * @brief Search for chached embeddable data of an url otherwise fetch it
	 *
	 * @param type $url The url of the page which should be scraped
	 * @param type $no_guessing If true the parse doens't search for
	 *    preview pictures
	 * @param type $do_oembed The false option is used by the function fetch_oembed()
	 *    to avoid endless loops
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
	 * @see ParseUrl::getSiteinfo() for more information about scraping
	 * embeddable content
	 */
	public static function getSiteinfoCached($url, $no_guessing = false, $do_oembed = true) {

		if ($url == "") {
			return false;
		}

		$r = q("SELECT * FROM `parsed_url` WHERE `url` = '%s' AND `guessing` = %d AND `oembed` = %d",
			dbesc(normalise_link($url)), intval(!$no_guessing), intval($do_oembed));

		if ($r) {
			$data = $r[0]["content"];
		}

		if (!is_null($data)) {
			$data = unserialize($data);
			return $data;
		}

		$data = self::getSiteinfo($url, $no_guessing, $do_oembed);

		q("INSERT INTO `parsed_url` (`url`, `guessing`, `oembed`, `content`, `created`) VALUES ('%s', %d, %d, '%s', '%s')
			 ON DUPLICATE KEY UPDATE `content` = '%s', `created` = '%s'",
			dbesc(normalise_link($url)), intval(!$no_guessing), intval($do_oembed),
			dbesc(serialize($data)), dbesc(datetime_convert()),
			dbesc(serialize($data)), dbesc(datetime_convert()));

		return $data;
	}
	/**
	 * @brief Parse a page for embeddable content information
	 *
	 * This method parses to url for meta data which can be used to embed
	 * the content. If available it prioritizes Open Graph meta tags.
	 * If this is not available it uses the twitter cards meta tags.
	 * As fallback it uses standard html elements with meta informations
	 * like \<title\>Awesome Title\</title\> or
	 * \<meta name="description" content="An awesome description"\>
	 *
	 * @param type $url The url of the page which should be scraped
	 * @param type $no_guessing If true the parse doens't search for
	 *    preview pictures
	 * @param type $do_oembed The false option is used by the function fetch_oembed()
	 *    to avoid endless loops
	 * @param type $count Internal counter to avoid endless loops
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
	 * @todo https://developers.google.com/+/plugins/snippet/
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
	public static function getSiteinfo($url, $no_guessing = false, $do_oembed = true, $count = 1) {

		$a = get_app();

		$siteinfo = array();

		// Check if the URL does contain a scheme
		$scheme = parse_url($url, PHP_URL_SCHEME);

		if ($scheme == "") {
			$url = "http://".trim($url, "/");
		}

		if ($count > 10) {
			logger("parseurl_getsiteinfo: Endless loop detected for ".$url, LOGGER_DEBUG);
			return($siteinfo);
		}

		$url = trim($url, "'");
		$url = trim($url, '"');

		$url = strip_tracking_query_params($url);

		$siteinfo["url"] = $url;
		$siteinfo["type"] = "link";

		$check_cert = Config::get("system", "verifyssl");

		$stamp1 = microtime(true);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$header = curl_exec($ch);
		$curl_info = @curl_getinfo($ch);
		curl_close($ch);

		$a->save_timestamp($stamp1, "network");

		if ((($curl_info["http_code"] == "301") || ($curl_info["http_code"] == "302") || ($curl_info["http_code"] == "303") || ($curl_info["http_code"] == "307"))
			&& (($curl_info["redirect_url"] != "") || ($curl_info["location"] != ""))) {
			if ($curl_info["redirect_url"] != "") {
				$siteinfo = self::getSiteinfo($curl_info["redirect_url"], $no_guessing, $do_oembed, ++$count);
			} else {
				$siteinfo = self::getSiteinfo($curl_info["location"], $no_guessing, $do_oembed, ++$count);
			}
			return($siteinfo);
		}

		// If the file is too large then exit
		if ($curl_info["download_content_length"] > 1000000) {
			return($siteinfo);
		}

		// If it isn't a HTML file then exit
		if (($curl_info["content_type"] != "") && !strstr(strtolower($curl_info["content_type"]), "html")) {
			return($siteinfo);
		}

		if ($do_oembed) {

			$oembed_data = oembed_fetch_url($url);

			if (!in_array($oembed_data->type, array("error", "rich"))) {
				$siteinfo["type"] = $oembed_data->type;
			}

			if (($oembed_data->type == "link") && ($siteinfo["type"] != "photo")) {
				if (isset($oembed_data->title)) {
					$siteinfo["title"] = $oembed_data->title;
				}
				if (isset($oembed_data->description)) {
					$siteinfo["text"] = trim($oembed_data->description);
				}
				if (isset($oembed_data->thumbnail_url)) {
					$siteinfo["image"] = $oembed_data->thumbnail_url;
				}
			}
		}

		// Fetch the first mentioned charset. Can be in body or header
		$charset = "";
		if (preg_match('/charset=(.*?)['."'".'"\s\n]/', $header, $matches)) {
			$charset = trim(trim(trim(array_pop($matches)), ';,'));
		}

		if ($charset == "") {
			$charset = "utf-8";
		}

		$pos = strpos($header, "\r\n\r\n");

		if ($pos) {
			$body = trim(substr($header, $pos));
		} else {
			$body = $header;
		}

		if (($charset != "") && (strtoupper($charset) != "UTF-8")) {
			logger("parseurl_getsiteinfo: detected charset ".$charset, LOGGER_DEBUG);
			//$body = mb_convert_encoding($body, "UTF-8", $charset);
			$body = iconv($charset, "UTF-8//TRANSLIT", $body);
		}

		$body = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");

		$doc = new \DOMDocument();
		@$doc->loadHTML($body);

		\xml::deleteNode($doc, "style");
		\xml::deleteNode($doc, "script");
		\xml::deleteNode($doc, "option");
		\xml::deleteNode($doc, "h1");
		\xml::deleteNode($doc, "h2");
		\xml::deleteNode($doc, "h3");
		\xml::deleteNode($doc, "h4");
		\xml::deleteNode($doc, "h5");
		\xml::deleteNode($doc, "h6");
		\xml::deleteNode($doc, "ol");
		\xml::deleteNode($doc, "ul");

		$xpath = new \DomXPath($doc);

		$list = $xpath->query("//meta[@content]");
		foreach ($list as $node) {
			$attr = array();
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}

			if (@$attr["http-equiv"] == "refresh") {
				$path = $attr["content"];
				$pathinfo = explode(";", $path);
				$content = "";
				foreach ($pathinfo as $value) {
					if (substr(strtolower($value), 0, 4) == "url=") {
						$content = substr($value, 4);
					}
				}
				if ($content != "") {
					$siteinfo = self::getSiteinfo($content, $no_guessing, $do_oembed, ++$count);
					return($siteinfo);
				}
			}
		}

		$list = $xpath->query("//title");
		if ($list->length > 0) {
			$siteinfo["title"] = $list->item(0)->nodeValue;
		}

		//$list = $xpath->query("head/meta[@name]");
		$list = $xpath->query("//meta[@name]");
		foreach ($list as $node) {
			$attr = array();
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}

			$attr["content"] = trim(html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8"));

			if ($attr["content"] != "") {
				switch (strtolower($attr["name"])) {
					case "fulltitle":
						$siteinfo["title"] = $attr["content"];
						break;
					case "description":
						$siteinfo["text"] = $attr["content"];
						break;
					case "thumbnail":
						$siteinfo["image"] = $attr["content"];
						break;
					case "twitter:image":
						$siteinfo["image"] = $attr["content"];
						break;
					case "twitter:image:src":
						$siteinfo["image"] = $attr["content"];
						break;
					case "twitter:card":
						if (($siteinfo["type"] == "") || ($attr["content"] == "photo")) {
							$siteinfo["type"] = $attr["content"];
						}
						break;
					case "twitter:description":
						$siteinfo["text"] = $attr["content"];
						break;
					case "twitter:title":
						$siteinfo["title"] = $attr["content"];
						break;
					case "dc.title":
						$siteinfo["title"] = $attr["content"];
						break;
					case "dc.description":
						$siteinfo["text"] = $attr["content"];
						break;
					case "keywords":
						$keywords = explode(",", $attr["content"]);
						break;
					case "news_keywords":
						$keywords = explode(",", $attr["content"]);
						break;
				}
			}
			if ($siteinfo["type"] == "summary") {
				$siteinfo["type"] = "link";
			}
		}

		if (isset($keywords)) {
			$siteinfo["keywords"] = array();
			foreach ($keywords as $keyword) {
				if (!in_array(trim($keyword), $siteinfo["keywords"])) {
					$siteinfo["keywords"][] = trim($keyword);
				}
			}
		}

		//$list = $xpath->query("head/meta[@property]");
		$list = $xpath->query("//meta[@property]");
		foreach ($list as $node) {
			$attr = array();
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}

			$attr["content"] = trim(html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8"));

			if ($attr["content"] != "") {
				switch (strtolower($attr["property"])) {
					case "og:image":
						$siteinfo["image"] = $attr["content"];
						break;
					case "og:title":
						$siteinfo["title"] = $attr["content"];
						break;
					case "og:description":
						$siteinfo["text"] = $attr["content"];
						break;
				}
			}
		}

		if ((@$siteinfo["image"] == "") && !$no_guessing) {
			$list = $xpath->query("//img[@src]");
			foreach ($list as $node) {
				$attr = array();
				if ($node->attributes->length) {
					foreach ($node->attributes as $attribute) {
						$attr[$attribute->name] = $attribute->value;
					}
				}

				$src = self::completeUrl($attr["src"], $url);
				$photodata = get_photo_info($src);

				if (($photodata) && ($photodata[0] > 150) && ($photodata[1] > 150)) {
					if ($photodata[0] > 300) {
						$photodata[1] = round($photodata[1] * (300 / $photodata[0]));
						$photodata[0] = 300;
					}
					if ($photodata[1] > 300) {
						$photodata[0] = round($photodata[0] * (300 / $photodata[1]));
						$photodata[1] = 300;
					}
					$siteinfo["images"][] = array("src" => $src,
									"width" => $photodata[0],
									"height" => $photodata[1]);
				}

				}
		} elseif ($siteinfo["image"] != "") {
			$src = self::completeUrl($siteinfo["image"], $url);

			unset($siteinfo["image"]);

			$photodata = get_photo_info($src);

			if (($photodata) && ($photodata[0] > 10) && ($photodata[1] > 10)) {
				$siteinfo["images"][] = array("src" => $src,
								"width" => $photodata[0],
								"height" => $photodata[1]);
			}
		}

		if ((@$siteinfo["text"] == "") && (@$siteinfo["title"] != "") && !$no_guessing) {
			$text = "";

			$list = $xpath->query("//div[@class='article']");
			foreach ($list as $node) {
				if (strlen($node->nodeValue) > 40) {
					$text .= " ".trim($node->nodeValue);
				}
			}

			if ($text == "") {
				$list = $xpath->query("//div[@class='content']");
				foreach ($list as $node) {
					if (strlen($node->nodeValue) > 40) {
						$text .= " ".trim($node->nodeValue);
					}
				}
			}

			// If none text was found then take the paragraph content
			if ($text == "") {
				$list = $xpath->query("//p");
				foreach ($list as $node) {
					if (strlen($node->nodeValue) > 40) {
						$text .= " ".trim($node->nodeValue);
					}
				}
			}

			if ($text != "") {
				$text = trim(str_replace(array("\n", "\r"), array(" ", " "), $text));

				while (strpos($text, "  ")) {
					$text = trim(str_replace("  ", " ", $text));
				}

				$siteinfo["text"] = trim(html_entity_decode(substr($text, 0, 350), ENT_QUOTES, "UTF-8").'...');
			}
		}

		logger("parseurl_getsiteinfo: Siteinfo for ".$url." ".print_r($siteinfo, true), LOGGER_DEBUG);

		call_hooks("getsiteinfo", $siteinfo);

		return($siteinfo);
	}

	/**
	 * @brief Convert tags from CSV to an array
	 *
	 * @param string $string Tags
	 * @return array with formatted Hashtags
	 */
	public static function convertTagsToArray($string) {
		$arr_tags = str_getcsv($string);
		if (count($arr_tags)) {
			// add the # sign to every tag
			array_walk($arr_tags, array("self", "arrAddHashes"));

			return $arr_tags;
		}
	}

	/**
	 * @brief Add a hasht sign to a string
	 *
	 *  This method is used as callback function
	 *
	 * @param string $tag The pure tag name
	 * @param int $k Counter for internal use
	 */
	private static function arrAddHashes(&$tag, $k) {
		$tag = "#" . $tag;
	}

	/**
	 * @brief Add a scheme to an url
	 *
	 * The src attribute of some html elements (e.g. images)
	 * can miss the scheme so we need to add the correct
	 * scheme
	 *
	 * @param string $url The url which possibly does have
	 *    a missing scheme (a link to an image)
	 * @param string $scheme The url with a correct scheme
	 *    (e.g. the url from the webpage which does contain the image)
	 *
	 * @return string The url with a scheme
	 */
	private static function completeUrl($url, $scheme) {
		$urlarr = parse_url($url);

		// If the url does allready have an scheme
		// we can stop the process here
		if (isset($urlarr["scheme"])) {
			return($url);
		}

		$schemearr = parse_url($scheme);

		$complete = $schemearr["scheme"]."://".$schemearr["host"];

		if (@$schemearr["port"] != "") {
			$complete .= ":".$schemearr["port"];
		}

		if (strpos($urlarr["path"],"/") !== 0) {
			$complete .= "/";
		}

		$complete .= $urlarr["path"];

		if (@$urlarr["query"] != "") {
			$complete .= "?".$urlarr["query"];
		}

		if (@$urlarr["fragment"] != "") {
			$complete .= "#".$urlarr["fragment"];
		}

		return($complete);
	}
}
