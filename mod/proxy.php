<?php
// Based upon "Privacy Image Cache" by Tobias Hößl <https://github.com/CatoTH/>

define('PROXY_DEFAULT_TIME', 86400); // 1 Day

define('PROXY_SIZE_MICRO', 'micro');
define('PROXY_SIZE_THUMB', 'thumb');
define('PROXY_SIZE_SMALL', 'small');
define('PROXY_SIZE_MEDIUM', 'medium');
define('PROXY_SIZE_LARGE', 'large');

require_once 'include/security.php';
require_once 'include/Photo.php';

function proxy_init(App $a) {
	// Pictures are stored in one of the following ways:
	// 1. If a folder "proxy" exists and is writeable, then use this for caching
	// 2. If a cache path is defined, use this
	// 3. If everything else failed, cache into the database
	//
	// Question: Do we really need these three methods?

	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		header('HTTP/1.1 304 Not Modified');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
		header('Etag: ' . $_SERVER['HTTP_IF_NONE_MATCH']);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
		header('Cache-Control: max-age=31536000');

		if (function_exists('header_remove')) {
			header_remove('Last-Modified');
			header_remove('Expires');
			header_remove('Cache-Control');
		}
		exit;
	}

	if (function_exists('header_remove')) {
		header_remove('Pragma');
		header_remove('pragma');
	}

	$thumb = false;
	$size = 1024;
	$sizetype = '';
	$basepath = $a->get_basepath();

	// If the cache path isn't there, try to create it
	if (!is_dir($basepath . '/proxy') AND is_writable($basepath)) {
		mkdir($basepath . '/proxy');
	}

	// Checking if caching into a folder in the webroot is activated and working
	$direct_cache = (is_dir($basepath . '/proxy') AND is_writable($basepath . '/proxy'));

	// Look for filename in the arguments
	if ((isset($a->argv[1]) OR isset($a->argv[2]) OR isset($a->argv[3])) AND !isset($_REQUEST['url'])) {
		if (isset($a->argv[3])) {
			$url = $a->argv[3];
		} elseif (isset($a->argv[2])) {
			$url = $a->argv[2];
		} else {
			$url = $a->argv[1];
		}

		if (isset($a->argv[3]) AND ($a->argv[3] == 'thumb')) {
			$size = 200;
		}

		// thumb, small, medium and large.
		if (substr($url, -6) == ':micro') {
			$size = 48;
			$sizetype = ':micro';
			$url = substr($url, 0, -6);
		} elseif (substr($url, -6) == ':thumb') {
			$size = 80;
			$sizetype = ':thumb';
			$url = substr($url, 0, -6);
		} elseif (substr($url, -6) == ':small') {
			$size = 175;
			$url = substr($url, 0, -6);
			$sizetype = ':small';
		} elseif (substr($url, -7) == ':medium') {
			$size = 600;
			$url = substr($url, 0, -7);
			$sizetype = ':medium';
		} elseif (substr($url, -6) == ':large') {
			$size = 1024;
			$url = substr($url, 0, -6);
			$sizetype = ':large';
		}

		$pos = strrpos($url, '=.');
		if ($pos) {
			$url = substr($url, 0, $pos + 1);
		}

		$url = str_replace(array('.jpg', '.jpeg', '.gif', '.png'), array('','','',''), $url);

		$url = base64_decode(strtr($url, '-_', '+/'), true);

		if ($url) {
			$_REQUEST['url'] = $url;
		}
	} else {
		$direct_cache = false;
	}

	if (!$direct_cache) {
		$urlhash = 'pic:' . sha1($_REQUEST['url']);

		$cachefile = get_cachefile(hash('md5', $_REQUEST['url']));
		if ($cachefile != '' AND file_exists($cachefile)) {
			$img_str = file_get_contents($cachefile);
			$mime = image_type_to_mime_type(exif_imagetype($cachefile));

			header('Content-type: ' . $mime);
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
			header('Etag: "' . md5($img_str) . '"');
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
			header('Cache-Control: max-age=31536000');

			// reduce quality - if it isn't a GIF
			if ($mime != 'image/gif') {
				$img = new Photo($img_str, $mime);
				if ($img->is_valid()) {
					$img_str = $img->imageString();
				}
			}

			echo $img_str;
			killme();
		}
	} else {
		$cachefile = '';
	}

	$valid = true;
	$r = array();

	if (!$direct_cache AND ($cachefile == '')) {
		$r = qu("SELECT * FROM `photo` WHERE `resource-id` = '%s' LIMIT 1", $urlhash);
		if (dbm::is_result($r)) {
			$img_str = $r[0]['data'];
			$mime = $r[0]['desc'];
			if ($mime == '') {
				$mime = 'image/jpeg';
			}
		}
	}

	if (!dbm::is_result($r)) {
		// It shouldn't happen but it does - spaces in URL
		$_REQUEST['url'] = str_replace(' ', '+', $_REQUEST['url']);
		$redirects = 0;
		$img_str = fetch_url($_REQUEST['url'], true, $redirects, 10);

		$tempfile = tempnam(get_temppath(), 'cache');
		file_put_contents($tempfile, $img_str);
		$mime = image_type_to_mime_type(exif_imagetype($tempfile));
		unlink($tempfile);

		// If there is an error then return a blank image
		if ((substr($a->get_curl_code(), 0, 1) == '4') OR (!$img_str)) {
			$img_str = file_get_contents('images/blank.png');
			$mime = 'image/png';
			$cachefile = ''; // Clear the cachefile so that the dummy isn't stored
			$valid = false;
			$img = new Photo($img_str, 'image/png');
			if ($img->is_valid()) {
				$img->scaleImage(10);
				$img_str = $img->imageString();
			}
		} elseif ($mime != 'image/jpeg' AND !$direct_cache AND $cachefile == '') {
			$image = @imagecreatefromstring($img_str);

			if ($image === FALSE) {
				die();
			}

			q("INSERT INTO `photo`
			( `uid`, `contact-id`, `guid`, `resource-id`, `created`, `edited`, `filename`, `album`, `height`, `width`, `desc`, `data`, `scale`, `profile`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid` )
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' )",
				0, 0, get_guid(), dbesc($urlhash),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename(dbesc($_REQUEST['url']))),
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
			if ($img->is_valid() AND !$direct_cache AND ($cachefile == '')) {
				$img->store(0, 0, $urlhash, $_REQUEST['url'], '', 100);
			}
		}
	}

	$img_str_orig = $img_str;

	// reduce quality - if it isn't a GIF
	if ($mime != 'image/gif') {
		$img = new Photo($img_str, $mime);
		if ($img->is_valid()) {
			$img->scaleImage($size);
			$img_str = $img->imageString();
		}
	}

	// If there is a real existing directory then put the cache file there
	// advantage: real file access is really fast
	// Otherwise write in cachefile
	if ($valid AND $direct_cache) {
		file_put_contents($basepath . '/proxy/' . proxy_url($_REQUEST['url'], true), $img_str_orig);
		if ($sizetype != '') {
			file_put_contents($basepath . '/proxy/' . proxy_url($_REQUEST['url'], true) . $sizetype, $img_str);
		}
	} elseif ($cachefile != '') {
		file_put_contents($cachefile, $img_str_orig);
	}

	header('Content-type: ' . $mime);

	// Only output the cache headers when the file is valid
	if ($valid) {
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
		header('Etag: "' . md5($img_str) . '"');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
		header('Cache-Control: max-age=31536000');
	}

	echo $img_str;

	killme();
}

/**
 * @brief Transform a remote URL into a local one
 *
 * This function only performs the URL replacement on http URL and if the
 * provided URL isn't local, "the isn't deactivated" (sic) and if the config
 * system.proxy_disabled is set to false.
 *
 * @param string $url       The URL to proxyfy
 * @param bool   $writemode Returns a local path the remote URL should be saved to
 * @param string $size      One of the PROXY_SIZE_* constants
 *
 * @return string The proxyfied URL or relative path
 */
function proxy_url($url, $writemode = false, $size = '') {
	$a = get_app();

	if (substr($url, 0, strlen('http')) !== 'http') {
		return $url;
	}

	// Only continue if it isn't a local image and the isn't deactivated
	if (proxy_is_local_image($url)) {
		$url = str_replace(normalise_link(App::get_baseurl()) . '/', App::get_baseurl() . '/', $url);
		return $url;
	}

	if (get_config('system', 'proxy_disabled')) {
		return $url;
	}

	// Image URL may have encoded ampersands for display which aren't desirable for proxy
	$url = html_entity_decode($url, ENT_NOQUOTES, 'utf-8');

	// Creating a sub directory to reduce the amount of files in the cache directory
	$basepath = $a->get_basepath() . '/proxy';

	$shortpath = hash('md5', $url);
	$longpath = substr($shortpath, 0, 2);

	if (is_dir($basepath) AND $writemode AND !is_dir($basepath . '/' . $longpath)) {
		mkdir($basepath . '/' . $longpath);
		chmod($basepath . '/' . $longpath, 0777);
	}

	$longpath .= '/' . strtr(base64_encode($url), '+/', '-_');

	// Extract the URL extension
	$extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

	$extensions = array('jpg', 'jpeg', 'gif', 'png');
	if (in_array($extension, $extensions)) {
		$shortpath .= '.' . $extension;
		$longpath .= '.' . $extension;
	}

	$proxypath = App::get_baseurl() . '/proxy/' . $longpath;

	if ($size != '') {
		$size = ':' . $size;
	}

	// Too long files aren't supported by Apache
	// Writemode in combination with long files shouldn't be possible
	if ((strlen($proxypath) > 250) AND $writemode) {
		return $shortpath;
	} elseif (strlen($proxypath) > 250) {
		return App::get_baseurl() . '/proxy/' . $shortpath . '?url=' . urlencode($url);
	} elseif ($writemode) {
		return $longpath;
	} else {
		return $proxypath . $size;
	}
}

/**
 * @param $url string
 * @return boolean
 */
function proxy_is_local_image($url) {
	if ($url[0] == '/') {
		return true;
	}

	if (strtolower(substr($url, 0, 5)) == 'data:') {
		return true;
	}

	// links normalised - bug #431
	$baseurl = normalise_link(App::get_baseurl());
	$url = normalise_link($url);
	return (substr($url, 0, strlen($baseurl)) == $baseurl);
}

/**
 * @brief Return the array of query string parameters from a URL
 *
 * @param string $url
 * @return array Associative array of query string parameters
 */
function proxy_parse_query($url) {
	$query = parse_url($url, PHP_URL_QUERY);
	$query = html_entity_decode($query);
	$query_list = explode('&', $query);
	$arr = array();

	foreach ($query_list as $key_value) {
		$key_value_list = explode('=', $key_value);
		$arr[$key_value_list[0]] = $key_value_list[1];
	}

	unset($url, $query_list, $url);
	return $arr;
}

function proxy_img_cb($matches) {
	// if the picture seems to be from another picture cache then take the original source
	$queryvar = proxy_parse_query($matches[2]);
	if (($queryvar['url'] != '') AND (substr($queryvar['url'], 0, 4) == 'http')) {
		$matches[2] = urldecode($queryvar['url']);
	}

	// following line changed per bug #431
	if (proxy_is_local_image($matches[2])) {
		return $matches[1] . $matches[2] . $matches[3];
	}

	return $matches[1] . proxy_url(htmlspecialchars_decode($matches[2])) . $matches[3];
}

function proxy_parse_html($html) {
	$html = str_replace(normalise_link(App::get_baseurl()) . '/', App::get_baseurl() . '/', $html);

	return preg_replace_callback('/(<img [^>]*src *= *["\'])([^"\']+)(["\'][^>]*>)/siU', 'proxy_img_cb', $html);
}
