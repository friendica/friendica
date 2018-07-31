<?php
/**
 * @file src/Module/Proxy.php
 * @brief Based upon "Privacy Image Cache" by Tobias Hößl <https://github.com/CatoTH/>
 */
namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Photo;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;

require_once 'include/security.php';

/**
 * @brief Module Proxy
 */
class Proxy extends BaseModule
{

	/**
	 * @brief Initializer method for this class.
	 *
	 * Sets application instance and checks if /proxy/ path is writable.
	 *
	 * @param \Friendica\App $app Application instance
	 */
	public static function init()
	{
		// Set application instance here
		$a = self::getApp();

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
			exit();
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
		if (!is_dir($basepath . '/proxy') && is_writable($basepath)) {
			mkdir($basepath . '/proxy');
		}

		// Checking if caching into a folder in the webroot is activated and working
		$direct_cache = (is_dir($basepath . '/proxy') && is_writable($basepath . '/proxy'));

		// Look for filename in the arguments
		if ((isset($a->argv[1]) || isset($a->argv[2]) || isset($a->argv[3])) && !isset($_REQUEST['url'])) {
			if (isset($a->argv[3])) {
				$url = $a->argv[3];
			} elseif (isset($a->argv[2])) {
				$url = $a->argv[2];
			} else {
				$url = $a->argv[1];
			}

			if (isset($a->argv[3]) && ($a->argv[3] == 'thumb')) {
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
				exit();
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
			if ((substr($a->get_curl_code(), 0, 1) == '4') || (!$img_str)) {
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
			file_put_contents($basepath . '/proxy/' . ProxyUtils::proxifyUrl($_REQUEST['url'], true), $img_str_orig);

			if ($sizetype != '') {
				file_put_contents($basepath . '/proxy/' . ProxyUtils::proxifyUrl($_REQUEST['url'], true) . $sizetype, $img_str);
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

		exit();
	}

}
