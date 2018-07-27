<?php
/**
 * @file src/Module/Proxy.php
 * @brief Based upon "Privacy Image Cache" by Tobias Hößl <https://github.com/CatoTH/>
 */
namespace Friendica\Network;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Photo;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

require_once 'include/security.php';

class Proxy
{

	/**
	 * Default time to keep images in proxy storage
	 */
	const DEFAULT_TIME = 86400; // 1 Day

	/**
	 * Sizes constants
	 */
	const SIZE_MICRO  = 'micro';
	const SIZE_THUMB  = 'thumb';
	const SIZE_SMALL  = 'small';
	const SIZE_MEDIUM = 'medium';
	const SIZE_LARGE  = 'large';

	/**
	 * Application instance
	 *
	 * @var \Friendica\App
	 */
	private static $a = null;

	/**
	 * Accepted extensions
	 *
	 * @var array
	 * @todo Make this configurable?
	 */
	private static $extensions = [
		'jpg',
		'jpeg',
		'gif',
		'png',
	];

	/**
	 * @brief Initializer method for this class.
	 *
	 * Sets application instance and checks if /proxy/ path is writable.
	 *
	 * @param \Friendica\App $app Application instance
	 */
	public static function init(App $a)
	{
		// Set application instance here
		self::$a = $a;

		/*
		 * Pictures are stored in one of the following ways:
		 *
		 * 1. If a folder "proxy" exists and is writeable, then use this for caching
		 * 2. If a cache path is defined, use this
		 * 3. If everything else failed, cache into the database
		 *
		 * Question: Do we really need these three methods?
		 */
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

			/// @TODO Stop here?
			killme();
		}

		if (function_exists('header_remove')) {
			header_remove('Pragma');
			header_remove('pragma');
		}

		$thumb = false;
		$size = 1024;
		$sizetype = '';
		$basepath = self::$a->get_basepath();

		// If the cache path isn't there, try to create it
		if (!is_dir($basepath . '/proxy') && is_writable($basepath)) {
			mkdir($basepath . '/proxy');
		}

		// Checking if caching into a folder in the webroot is activated and working
		$direct_cache = (is_dir($basepath . '/proxy') && is_writable($basepath . '/proxy'));

		// Look for filename in the arguments
		if ((isset(self::$a->argv[1]) || isset(self::$a->argv[2]) || isset(self::$a->argv[3])) && !isset($_REQUEST['url'])) {
			if (isset(self::$a->argv[3])) {
				$url = self::$a->argv[3];
			} elseif (isset(self::$a->argv[2])) {
				$url = self::$a->argv[2];
			} else {
				$url = self::$a->argv[1];
			}

			if (isset(self::$a->argv[3]) && (self::$a->argv[3] == 'thumb')) {
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

			$url = str_replace(['.jpg', '.jpeg', '.gif', '.png'], ['','','',''], $url);

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
			if ($cachefile != '' && file_exists($cachefile)) {
				$img_str = file_get_contents($cachefile);
				$mime = image_type_to_mime_type(exif_imagetype($cachefile));

				header('Content-type: ' . $mime);
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
				header('Etag: "' . md5($img_str) . '"');
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
				header('Cache-Control: max-age=31536000');

				// reduce quality - if it isn't a GIF
				if ($mime != 'image/gif') {
					$image = new Image($img_str, $mime);

					if ($image->isValid()) {
						$img_str = $image->asString();
					}
				}

				echo $img_str;
				killme();
			}
		} else {
			$cachefile = '';
		}

		$valid = true;
		$photo = null;

		if (!$direct_cache && ($cachefile == '')) {
			$photo = DBA::selectFirst('photo', ['data', 'desc'], ['resource-id' => $urlhash]);

			if (DBA::isResult($photo)) {
				$img_str = $photo['data'];
				$mime = $photo['desc'];

				if ($mime == '') {
					$mime = 'image/jpeg';
				}
			}
		}

		if (!DBA::isResult($photo)) {
			// It shouldn't happen but it does - spaces in URL
			$_REQUEST['url'] = str_replace(' ', '+', $_REQUEST['url']);
			$redirects = 0;
			$img_str = Network::fetchUrl($_REQUEST['url'], true, $redirects, 10);

			$tempfile = tempnam(get_temppath(), 'cache');
			file_put_contents($tempfile, $img_str);
			$mime = image_type_to_mime_type(exif_imagetype($tempfile));
			unlink($tempfile);

			// If there is an error then return a blank image
			if ((substr(self::$a->get_curl_code(), 0, 1) == '4') || (!$img_str)) {
				$img_str = file_get_contents('images/blank.png');
				$mime = 'image/png';
				$cachefile = ''; // Clear the cachefile so that the dummy isn't stored
				$valid = false;
				$image = new Image($img_str, 'image/png');

				if ($image->isValid()) {
					$image->scaleDown(10);
					$img_str = $image->asString();
				}
			} elseif ($mime != 'image/jpeg' && !$direct_cache && $cachefile == '') {
				$image = @imagecreatefromstring($img_str);

				if ($image === FALSE) {
					die();
				}

				$fields = ['uid' => 0, 'contact-id' => 0, 'guid' => System::createGUID(), 'resource-id' => $urlhash, 'created' => DateTimeFormat::utcNow(), 'edited' => DateTimeFormat::utcNow(),
					'filename' => basename($_REQUEST['url']), 'type' => '', 'album' => '', 'height' => imagesy($image), 'width' => imagesx($image),
					'datasize' => 0, 'data' => $img_str, 'scale' => 100, 'profile' => 0,
					'allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '', 'desc' => $mime];
				DBA::insert('photo', $fields);
			} else {
				$image = new Image($img_str, $mime);

				if ($image->isValid() && !$direct_cache && ($cachefile == '')) {
					Photo::store($image, 0, 0, $urlhash, $_REQUEST['url'], '', 100);
				}
			}
		}

		$img_str_orig = $img_str;

		// reduce quality - if it isn't a GIF
		if ($mime != 'image/gif') {
			$image = new Image($img_str, $mime);

			if ($image->isValid()) {
				$image->scaleDown($size);
				$img_str = $image->asString();
			}
		}

		/*
		 * If there is a real existing directory then put the cache file there
		 * advantage: real file access is really fast
		 * Otherwise write in cachefile
		 */
		if ($valid && $direct_cache) {
			file_put_contents($basepath . '/proxy/' . self::proxifyUrl($_REQUEST['url'], true), $img_str_orig);

			if ($sizetype != '') {
				file_put_contents($basepath . '/proxy/' . self::proxifyUrl($_REQUEST['url'], true) . $sizetype, $img_str);
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
	 * @brief Transform a remote URL into a local one.
	 *
	 * This function only performs the URL replacement on http URL and if the
	 * provided URL isn't local, "the isn't deactivated" (sic) and if the config
	 * system.proxy_disabled is set to false.
	 *
	 * @param string $url       The URL to proxyfy
	 * @param bool   $writemode Returns a local path the remote URL should be saved to
	 * @param string $size      One of the Proxy::SIZE_* constants
	 *
	 * @return string The proxyfied URL or relative path
	 */
	public static function proxifyUrl($url, $writemode = false, $size = '')
	{
		// Trim URL first
		$url = trim($url);

		// Is no http in front of it?
		/// @TODO To weak test for being a valid URL
		if (substr($url, 0, 4) !== 'http') {
			return $url;
		}

		// Only continue if it isn't a local image and the isn't deactivated
		if (self::isLocalImage($url)) {
			$url = str_replace(normalise_link(System::baseUrl()) . '/', System::baseUrl() . '/', $url);
			return $url;
		}

		// Is the proxy disabled?
		if (Config::get('system', 'proxy_disabled')) {
			return $url;
		}

		// Image URL may have encoded ampersands for display which aren't desirable for proxy
		$url = html_entity_decode($url, ENT_NOQUOTES, 'utf-8');

		// Creating a sub directory to reduce the amount of files in the cache directory
		$basepath = self::$a->get_basepath() . '/proxy';

		$shortpath = hash('md5', $url);
		$longpath = substr($shortpath, 0, 2);

		if (is_dir($basepath) && $writemode && !is_dir($basepath . '/' . $longpath)) {
			mkdir($basepath . '/' . $longpath);
			chmod($basepath . '/' . $longpath, 0777);
		}

		$longpath .= '/' . strtr(base64_encode($url), '+/', '-_');

		// Extract the URL extension
		$extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

		if (in_array($extension, self::$extensions)) {
			$shortpath .= '.' . $extension;
			$longpath .= '.' . $extension;
		}

		$proxypath = System::baseUrl() . '/proxy/' . $longpath;

		if ($size != '') {
			$size = ':' . $size;
		}

		// Too long files aren't supported by Apache
		// Writemode in combination with long files shouldn't be possible
		if ((strlen($proxypath) > 250) && $writemode) {
			return $shortpath;
		} elseif (strlen($proxypath) > 250) {
			return System::baseUrl() . '/proxy/' . $shortpath . '?url=' . urlencode($url);
		} elseif ($writemode) {
			return $longpath;
		} else {
			return $proxypath . $size;
		}
	}

	/**
	 * @brief "Proxifies" HTML code's image tags
	 *
	 * "Proxifies", means replaces image URLs in given HTML code with those from
	 * proxy storage directory.
	 *
	 * @param string $html Un-proxified HTML code
	 *
	 * @return string Proxified HTML code
	 */
	public static function proxifyHtml($html)
	{
		$html = str_replace(normalise_link(System::baseUrl()) . '/', System::baseUrl() . '/', $html);

		return preg_replace_callback('/(<img [^>]*src *= *["\'])([^"\']+)(["\'][^>]*>)/siU', 'self::replaceUrl', $html);
	}

	/**
	 * @brief Checks if the URL is a local URL.
	 *
	 * @param string $url
	 * @return boolean
	 */
	private static function isLocalImage($url)
	{
		if (substr($url, 0, 1) == '/') {
			return true;
		}

		if (strtolower(substr($url, 0, 5)) == 'data:') {
			return true;
		}

		// links normalised - bug #431
		$baseurl = normalise_link(System::baseUrl());
		$url = normalise_link($url);

		return (substr($url, 0, strlen($baseurl)) == $baseurl);
	}

	/**
	 * @brief Return the array of query string parameters from a URL
	 *
	 * @param string $url URL to parse
	 * @return array Associative array of query string parameters
	 */
	private static function parseQuery($url)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		$query = html_entity_decode($query);
		$query_list = explode('&', $query);

		$arr = [];

		foreach ($query_list as $key_value) {
			$key_value_list = explode('=', $key_value);
			$arr[$key_value_list[0]] = $key_value_list[1];
		}

		unset($url, $query_list, $url);

		return $arr;
	}

	/**
	 * @brief Call-back method to replace the UR
	 *
	 * @param array $matches Matches from preg_replace_callback()
	 * @return string Proxified HTML image tag
	 */
	private static function replaceUrl(array $matches)
	{
		// if the picture seems to be from another picture cache then take the original source
		$queryvar = self::parseQuery($matches[2]);

		if (!empty($queryvar['url']) && substr($queryvar['url'], 0, 4) == 'http') {
			$matches[2] = urldecode($queryvar['url']);
		}

		// Following line changed per bug #431
		if (self::isLocalImage($matches[2])) {
			return $matches[1] . $matches[2] . $matches[3];
		}

		// Return proxified HTML
		return $matches[1] . self::proxifyUrl(htmlspecialchars_decode($matches[2])) . $matches[3];
	}

}
