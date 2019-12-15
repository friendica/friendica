<?php
/**
 * @file src/Module/Photo.php
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Photo as MPhoto;
use Friendica\Object\Image;

/**
 * @brief Photo Module
 */
class Photo extends BaseModule
{
	/**
	 * @brief Module initializer
	 *
	 * Fetch a photo or an avatar, in optional size, check for permissions and
	 * return the image
	 */
	public static function init(array $parameters = [])
	{
		$a = DI::app();
		// @TODO: Replace with parameter from router
		if ($a->argc <= 1 || $a->argc > 4) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
			header("HTTP/1.1 304 Not Modified");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
			if (!empty($_SERVER["HTTP_IF_NONE_MATCH"])) {
				header("Etag: " . $_SERVER["HTTP_IF_NONE_MATCH"]);
			}
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
			header("Cache-Control: max-age=31536000");
			if (function_exists("header_remove")) {
				header_remove("Last-Modified");
				header_remove("Expires");
				header_remove("Cache-Control");
			}
			exit;
		}

		$customsize = 0;
		$photo = false;
		// @TODO: Replace with parameter from router
		switch($a->argc) {
			case 4:
				$customsize = intval($a->argv[2]);
				$uid = MPhoto::stripExtension($a->argv[3]);
				$photo = self::getAvatar($uid, $a->argv[1]);
				break;
			case 3:
				$uid = MPhoto::stripExtension($a->argv[2]);
				$photo = self::getAvatar($uid, $a->argv[1]);
				break;
			case 2:
				$photoid = MPhoto::stripExtension($a->argv[1]);
				$scale = 0;
				if (substr($photoid, -2, 1) == "-") {
					$scale = intval(substr($photoid, -1, 1));
					$photoid = substr($photoid, 0, -2);
				}
				$photo = MPhoto::getPhoto($photoid, $scale);
				if ($photo === false) {
					$photo = MPhoto::createPhotoForSystemResource("images/nosign.jpg");
				}
				break;
		}

		if ($photo === false) {
			System::httpExit('404', 'Not Found');
		}

		$cacheable = ($photo["allow_cid"] . $photo["allow_gid"] . $photo["deny_cid"] . $photo["deny_gid"] === "") && (isset($photo["cacheable"]) ? $photo["cacheable"] : true);

		$img = MPhoto::getImageForPhoto($photo);

		if (is_null($img) || !$img->isValid()) {
			Logger::log("Invalid photo with id {$photo["id"]}.");
			throw new \Friendica\Network\HTTPException\InternalServerErrorException(L10n::t('Invalid photo with id %s.', $photo["id"]));
		}

		// if customsize is set and image is not a gif, resize it
		if ($img->getType() !== "image/gif" && $customsize > 0 && $customsize < 501) {
			$img->scaleToSquare($customsize);
		}

		if (function_exists("header_remove")) {
			header_remove("Pragma");
			header_remove("pragma");
		}

		header("Content-type: " . $img->getType());

		if (!$cacheable) {
			// it is a private photo that they have no permission to view.
			// tell the browser not to cache it, in case they authenticate
			// and subsequently have permission to see it
			header("Cache-Control: no-store, no-cache, must-revalidate");
		} else {
			$md5 = md5($img->asString());
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
			header("Etag: \"{$md5}\"");
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
			header("Cache-Control: max-age=31536000");
		}

		echo $img->asString();

		exit();
	}

	private static function getAvatar($uid, $type="avatar")
	{

		switch($type) {
		case "profile":
		case "custom":
			$scale = 4;
			$default = "images/person-300.jpg";
			break;
		case "micro":
			$scale = 6;
			$default = "images/person-48.jpg";
			break;
		case "avatar":
		default:
			$scale = 5;
			$default = "images/person-80.jpg";
		}

		$photo = MPhoto::selectFirst([], ["scale" => $scale, "uid" => $uid, "profile" => 1]);
		if ($photo === false) {
			$photo = MPhoto::createPhotoForSystemResource($default);
		}
		return $photo;
	}

}
