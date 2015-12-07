<?php
// Based upon "Privacy Image Cache" by Tobias Hößl <https://github.com/CatoTH/>

define("PROXY_DEFAULT_TIME", 86400); // 1 Day

define("PROXY_SIZE_MICRO", "micro");
define("PROXY_SIZE_THUMB", "thumb");
define("PROXY_SIZE_SMALL", "small");
define("PROXY_SIZE_MEDIUM", "medium");
define("PROXY_SIZE_LARGE", "large");

require_once('include/security.php');
require_once("include/Photo.php");

function proxy_init() {
	global $a, $_SERVER;

	// Pictures are stored in one of the following ways:
	// 1. If a folder "proxy" exists and is writeable, then use this for caching
	// 2. If a cache path is defined, use this
	// 3. If everything else failed, cache into the database
	//
	// Question: Do we really need these three methods?

	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		header('HTTP/1.1 304 Not Modified');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
		header('Etag: '.$_SERVER['HTTP_IF_NONE_MATCH']);
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
		header("Cache-Control: max-age=31536000");
		if(function_exists('header_remove')) {
			header_remove('Last-Modified');
			header_remove('Expires');
			header_remove('Cache-Control');
		}
		exit;
	}

	if(function_exists('header_remove')) {
		header_remove('Pragma');
		header_remove('pragma');
	}

	$thumb = false;
	$size = 1024;
	$sizetype = "";

	// If the cache path isn't there, try to create it
	if (!is_dir($_SERVER["DOCUMENT_ROOT"]."/proxy"))
		if (is_writable($_SERVER["DOCUMENT_ROOT"]))
			mkdir($_SERVER["DOCUMENT_ROOT"]."/proxy");

	// Checking if caching into a folder in the webroot is activated and working
	$direct_cache = (is_dir($_SERVER["DOCUMENT_ROOT"]."/proxy") AND is_writable($_SERVER["DOCUMENT_ROOT"]."/proxy"));

	// Look for filename in the arguments
	if ((isset($a->argv[1]) OR isset($a->argv[2]) OR isset($a->argv[3])) AND !isset($_REQUEST["url"])) {
		if (isset($a->argv[3]))
			$url = $a->argv[3];
		elseif (isset($a->argv[2]))
			$url = $a->argv[2];
		else
			$url = $a->argv[1];

		if (isset($a->argv[3]) and ($a->argv[3] == "thumb"))
			$size = 200;

		// thumb, small, medium and large.
		if (substr($url, -6) == ":micro") {
			$size = 48;
			$sizetype = ":micro";
			$url = substr($url, 0, -6);
		} elseif (substr($url, -6) == ":thumb") {
			$size = 80;
			$sizetype = ":thumb";
			$url = substr($url, 0, -6);
		} elseif (substr($url, -6) == ":small") {
			$size = 175;
			$url = substr($url, 0, -6);
			$sizetype = ":small";
		} elseif (substr($url, -7) == ":medium") {
			$size = 600;
			$url = substr($url, 0, -7);
			$sizetype = ":medium";
		} elseif (substr($url, -6) == ":large") {
			$size = 1024;
			$url = substr($url, 0, -6);
			$sizetype = ":large";
		}

		$pos = strrpos($url, "=.");
		if ($pos)
			$url = substr($url, 0, $pos+1);

		$url = str_replace(array(".jpg", ".jpeg", ".gif", ".png"), array("","","",""), $url);

		$url = base64_decode(strtr($url, '-_', '+/'), true);

		if ($url)
			$_REQUEST['url'] = $url;
	} else
		$direct_cache = false;

	if (!$direct_cache) {
		$urlhash = 'pic:' . sha1($_REQUEST['url']);

		$cachefile = get_cachefile(hash("md5", $_REQUEST['url']));
		if ($cachefile != '') {
			if (file_exists($cachefile)) {
				$img_str = file_get_contents($cachefile);
				$mime = image_type_to_mime_type(exif_imagetype($cachefile));

				header("Content-type: $mime");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
				header('Etag: "'.md5($img_str).'"');
				header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
				header("Cache-Control: max-age=31536000");

				// reduce quality - if it isn't a GIF
				if ($mime != "image/gif") {
					$img = new Photo($img_str, $mime);
					if($img->is_valid()) {
						$img_str = $img->imageString();
					}
				}

				echo $img_str;
				killme();
			}
		}
	} else
		$cachefile = "";

	$valid = true;

	if (!$direct_cache AND ($cachefile == "")) {
		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' LIMIT 1", $urlhash);
		if (count($r)) {
        		$img_str = $r[0]['data'];
			$mime = $r[0]["desc"];
			if ($mime == "") $mime = "image/jpeg";
		}
	} else
		$r = array();

	if (!count($r)) {
		// It shouldn't happen but it does - spaces in URL
		$_REQUEST['url'] = str_replace(" ", "+", $_REQUEST['url']);
		$redirects = 0;
		$img_str = fetch_url($_REQUEST['url'],true, $redirects, 10);

		$tempfile = tempnam(get_temppath(), "cache");
		file_put_contents($tempfile, $img_str);
		$mime = image_type_to_mime_type(exif_imagetype($tempfile));
		unlink($tempfile);

		// If there is an error then return a blank image
		if ((substr($a->get_curl_code(), 0, 1) == "4") or (!$img_str)) {
			$img_str = file_get_contents("images/blank.png");
			$mime = "image/png";
			$cachefile = ""; // Clear the cachefile so that the dummy isn't stored
			$valid = false;
			$img = new Photo($img_str, "image/png");
			if($img->is_valid()) {
				$img->scaleImage(10);
				$img_str = $img->imageString();
			}
		} else if (($mime != "image/jpeg") AND !$direct_cache AND ($cachefile == "")) {
			$image = @imagecreatefromstring($img_str);

			if($image === FALSE) die();

			q("INSERT INTO `photo`
			( `uid`, `contact-id`, `guid`, `resource-id`, `created`, `edited`, `filename`, `album`, `height`, `width`, `desc`, `data`, `scale`, `profile`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid` )
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' )",
				0, 0, get_guid(), dbesc($urlhash),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename(dbesc($_REQUEST["url"]))),
				dbesc(''),
				intval(imagesy($image)),
				intval(imagesx($image)),
				$mime,
				dbesc($img_str),
				100,
				intval(0),
				dbesc(''), dbesc(''), dbesc(''), dbesc('')
			);

		} else {
			$img = new Photo($img_str, $mime);
			if($img->is_valid()) {
				if (!$direct_cache AND ($cachefile == ""))
					$img->store(0, 0, $urlhash, $_REQUEST['url'], '', 100);
			}
		}
	}

	$img_str_orig = $img_str;

	// reduce quality - if it isn't a GIF
	if ($mime != "image/gif") {
		$img = new Photo($img_str, $mime);
		if($img->is_valid()) {
			$img->scaleImage($size);
			$img_str = $img->imageString();
		}
	}

	// If there is a real existing directory then put the cache file there
	// advantage: real file access is really fast
	// Otherwise write in cachefile
	if ($valid AND $direct_cache) {
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/proxy/".proxy_url($_REQUEST['url'], true), $img_str_orig);
		if ($sizetype <> '')
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/proxy/".proxy_url($_REQUEST['url'], true).$sizetype, $img_str);
	} elseif ($cachefile != '')
		file_put_contents($cachefile, $img_str_orig);

	header("Content-type: $mime");

	// Only output the cache headers when the file is valid
	if ($valid) {
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
		header('Etag: "'.md5($img_str).'"');
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
		header("Cache-Control: max-age=31536000");
	}

	echo $img_str;

	killme();
}

function proxy_url($url, $writemode = false, $size = "") {
	global $_SERVER;

	$a = get_app();

	// Only continue if it isn't a local image and the isn't deactivated
	if (proxy_is_local_image($url)) {
		$url = str_replace(normalise_link($a->get_baseurl())."/", $a->get_baseurl()."/", $url);
		return($url);
	}

	if (get_config("system", "proxy_disabled"))
		return($url);

	// Creating a sub directory to reduce the amount of files in the cache directory
	$basepath = $_SERVER["DOCUMENT_ROOT"]."/proxy";

	$path = substr(hash("md5", $url), 0, 2);

	if (is_dir($basepath) and $writemode)
		if (!is_dir($basepath."/".$path)) {
			mkdir($basepath."/".$path);
			chmod($basepath."/".$path, 0777);
		}

	$path .= "/".strtr(base64_encode($url), '+/', '-_');

	// Checking for valid extensions. Only add them if they are safe
	$pos = strrpos($url, ".");
	if ($pos) {
		$extension = strtolower(substr($url, $pos+1));
		$pos = strpos($extension, "?");
		if ($pos)
			$extension = substr($extension, 0, $pos);
	}

	$extensions = array("jpg", "jpeg", "gif", "png");

	if (in_array($extension, $extensions))
		$path .= ".".$extension;

	$proxypath = $a->get_baseurl()."/proxy/".$path;

	if ($size != "")
		$size = ":".$size;

	// Too long files aren't supported by Apache
	// Writemode in combination with long files shouldn't be possible
	if ((strlen($proxypath) > 250) AND $writemode)
		return (hash("md5", $url));
	elseif (strlen($proxypath) > 250)
		return ($a->get_baseurl()."/proxy/".hash("md5", $url)."?url=".urlencode($url));
	elseif ($writemode)
		return ($path);
	else
		return ($proxypath.$size);
}

/**
 * @param $url string
 * @return boolean
 */
function proxy_is_local_image($url) {
	if ($url[0] == '/') return true;

	if (strtolower(substr($url, 0, 5)) == "data:") return true;

	// links normalised - bug #431
	$baseurl = normalise_link(get_app()->get_baseurl());
	$url = normalise_link($url);
	return (substr($url, 0, strlen($baseurl)) == $baseurl);
}

function proxy_parse_query($var) {
        /**
         *  Use this function to parse out the query array element from
         *  the output of parse_url().
        */
        $var  = parse_url($var, PHP_URL_QUERY);
        $var  = html_entity_decode($var);
        $var  = explode('&', $var);
        $arr  = array();

        foreach($var as $val) {
                $x          = explode('=', $val);
                $arr[$x[0]] = $x[1];
        }

        unset($val, $x, $var);
        return $arr;
}

function proxy_img_cb($matches) {

	// if the picture seems to be from another picture cache then take the original source
	$queryvar = proxy_parse_query($matches[2]);
	if (($queryvar['url'] != "") AND (substr($queryvar['url'], 0, 4) == "http"))
		$matches[2] = urldecode($queryvar['url']);

	// following line changed per bug #431
	if (proxy_is_local_image($matches[2]))
		return $matches[1] . $matches[2] . $matches[3];

	return $matches[1].proxy_url(htmlspecialchars_decode($matches[2])).$matches[3];
}

function proxy_parse_html($html) {
	$a = get_app();
	$html = str_replace(normalise_link($a->get_baseurl())."/", $a->get_baseurl()."/", $html);

	return preg_replace_callback("/(<img [^>]*src *= *[\"'])([^\"']+)([\"'][^>]*>)/siU", "proxy_img_cb", $html);
}
