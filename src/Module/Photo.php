<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo as MPhoto;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Core\Storage\Type\ExternalResource;
use Friendica\Core\Storage\Type\SystemResource;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\NotModifiedException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;

/**
 * Photo Module
 */
class Photo extends BaseModule
{
	/**
	 * Module initializer
	 *
	 * Fetch a photo or an avatar, in optional size, check for permissions and
	 * return the image
	 */
	protected function rawContent(array $request = [])
	{
		$totalstamp = microtime(true);

		if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
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
			throw new NotModifiedException();
		}

		Profile::addVisitorCookieForHTTPSigner();

		$customsize = 0;
		$square_resize = true;
		$scale = null;
		$stamp = microtime(true);
		// User avatar
		if (!empty($this->parameters['type'])) {
			if (!empty($this->parameters['customsize'])) {
				$customsize = intval($this->parameters['customsize']);
				$square_resize = !in_array($this->parameters['type'], ['media', 'preview']);
			}

			if (!empty($this->parameters['guid'])) {
				$guid = $this->parameters['guid'];
				$account = DBA::selectFirst('account-user-view', ['id'], ['guid' => $guid], ['order' => ['uid' => true]]);
				if (empty($account)) {
					throw new HTTPException\NotFoundException();
				}

				$id = $account['id'];
			}

			// Contact Id Fallback, to remove after version 2021.12
			if (isset($this->parameters['contact_id'])) {
				$id = intval($this->parameters['contact_id']);
			}

			if (!empty($this->parameters['nickname_ext'])) {
				$nickname = pathinfo($this->parameters['nickname_ext'], PATHINFO_FILENAME);
				$user = User::getByNickname($nickname, ['uid']);
				if (empty($user)) {
					throw new HTTPException\NotFoundException();
				}

				$id = $user['uid'];
			}

			// User Id Fallback, to remove after version 2021.12
			if (!empty($this->parameters['uid_ext'])) {
				$id = intval(pathinfo($this->parameters['uid_ext'], PATHINFO_FILENAME));
			}

			// Please refactor this for the love of everything that's good
			if (isset($this->parameters['id'])) {
				$id = $this->parameters['id'];
			}

			if (empty($id)) {
				Logger::notice('No picture id was detected', ['parameters' => $this->parameters, 'query' => DI::args()->getQueryString()]);
				throw new HTTPException\NotFoundException(DI::l10n()->t('The Photo is not available.'));
			}

			$photo = self::getPhotoByid($id, $this->parameters['type'], $customsize ?: Proxy::PIXEL_SMALL);
		} else {
			$photoid = pathinfo($this->parameters['name'], PATHINFO_FILENAME);
			$scale = 0;
			if (substr($photoid, -2, 1) == "-") {
				$scale = intval(substr($photoid, -1, 1));
				$photoid = substr($photoid, 0, -2);
			}
			$photo = MPhoto::getPhoto($photoid, $scale);
			if ($photo === false) {
				throw new HTTPException\NotFoundException(DI::l10n()->t('The Photo with id %s is not available.', $photoid));
			}
		}

		$fetch = microtime(true) - $stamp;

		if ($photo === false) {
			throw new HTTPException\NotFoundException();
		}

		$cacheable = ($photo["allow_cid"] . $photo["allow_gid"] . $photo["deny_cid"] . $photo["deny_gid"] === "") && (isset($photo["cacheable"]) ? $photo["cacheable"] : true);

		$stamp = microtime(true);

		$imgdata = MPhoto::getImageDataForPhoto($photo);
		if (empty($imgdata)) {
			throw new HTTPException\NotFoundException();
		}

		// The mimetype for an external or system resource can only be known reliably after it had been fetched
		if (in_array($photo['backend-class'], [ExternalResource::NAME, SystemResource::NAME])) {
			$mimetype = Images::getMimeTypeByData($imgdata);
			if (!empty($mimetype)) {
				$photo['type'] = $mimetype;
			}
		}

		$data = microtime(true) - $stamp;

		if (empty($imgdata)) {
			Logger::warning('Invalid photo', ['id' => $photo['id']]);
			if (in_array($photo['backend-class'], [ExternalResource::NAME])) {
				$reference = json_decode($photo['backend-ref'], true);
				$error = DI::l10n()->t('Invalid external resource with url %s.', $reference['url']);
			} else {
				$error = DI::l10n()->t('Invalid photo with id %s.', $photo['id']);
			}
			throw new HTTPException\InternalServerErrorException($error);
		}

		// if customsize is set and image is not a gif, resize it
		if ($photo['type'] !== "image/gif" && $customsize > 0 && $customsize <= Proxy::PIXEL_THUMB && $square_resize) {
			$img = new Image($imgdata, $photo['type']);
			$img->scaleToSquare($customsize);
			$imgdata = $img->asString();
		} elseif ($photo['type'] !== "image/gif" && $customsize > 0) {
			$img = new Image($imgdata, $photo['type']);
			$img->scaleDown($customsize);
			$imgdata = $img->asString();
		}

		if (function_exists("header_remove")) {
			header_remove("Pragma");
			header_remove("pragma");
		}

		header("Content-type: " . $photo['type']);

		$stamp = microtime(true);
		if (!$cacheable) {
			// it is a private photo that they have no permission to view.
			// tell the browser not to cache it, in case they authenticate
			// and subsequently have permission to see it
			header("Cache-Control: no-store, no-cache, must-revalidate");
		} else {
			$md5 = $photo['hash'] ?: md5($imgdata);
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
			header("Etag: \"{$md5}\"");
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + (31536000)) . " GMT");
			header("Cache-Control: max-age=31536000");
		}
		$checksum = microtime(true) - $stamp;

		$stamp = microtime(true);
		echo $imgdata;
		$output = microtime(true) - $stamp;

		$total = microtime(true) - $totalstamp;
		$rest = $total - ($fetch + $data + $checksum + $output);

		if (!is_null($scale) && ($scale < 4)) {
			Logger::info('Performance:', ['scale' => $scale, 'resource' => $photo['resource-id'],
				'total' => number_format($total, 3), 'fetch' => number_format($fetch, 3),
				'data' => number_format($data, 3), 'checksum' => number_format($checksum, 3),
				'output' => number_format($output, 3), 'rest' => number_format($rest, 3)]);
		}

		exit();
	}

	private static function getPhotoByid(int $id, $type, $customsize)
	{
		switch($type) {
			case "preview":
				$media = DBA::selectFirst('post-media', ['preview', 'url', 'mimetype', 'type', 'uri-id'], ['id' => $id]);
				if (empty($media)) {
					return false;
				}
				$url = $media['preview'];

				if (empty($url) && ($media['type'] == Post\Media::IMAGE)) {
					$url = $media['url'];
				}

				if (empty($url)) {
					return false;
				}

				if (Network::isLocalLink($url) && preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $url, $matches)) {
					return MPhoto::getPhoto($matches[1], $matches[2]);
				}

				return MPhoto::createPhotoForExternalResource($url, (int)local_user(), $media['mimetype']);
			case "media":
				$media = DBA::selectFirst('post-media', ['url', 'mimetype', 'uri-id'], ['id' => $id, 'type' => Post\Media::IMAGE]);
				if (empty($media)) {
					return false;
				}

				if (Network::isLocalLink($media['url']) && preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $media['url'], $matches)) {
					return MPhoto::getPhoto($matches[1], $matches[2]);
				}

				return MPhoto::createPhotoForExternalResource($media['url'], (int)local_user(), $media['mimetype']);
			case "link":
				$link = DBA::selectFirst('post-link', ['url', 'mimetype'], ['id' => $id]);
				if (empty($link)) {
					return false;
				}

				return MPhoto::createPhotoForExternalResource($link['url'], (int)local_user(), $link['mimetype']);
			case "contact":
				$contact = Contact::getById($id, ['uid', 'url', 'avatar', 'photo', 'xmpp', 'addr']);
				if (empty($contact)) {
					return false;
				}
				If (($contact['uid'] != 0) && empty($contact['photo']) && empty($contact['avatar'])) {
					$contact = Contact::getByURL($contact['url'], false, ['avatar', 'photo', 'xmpp', 'addr']);
				}
				if (!empty($contact['photo']) && !empty($contact['avatar'])) {
					// Fetch photo directly
					$resourceid = MPhoto::ridFromURI($contact['photo']);
					if (!empty($resourceid)) {
						$photo = MPhoto::selectFirst([], ['resource-id' => $resourceid], ['order' => ['scale']]);
						if (!empty($photo)) {
							return $photo;
						}
					}
					// We continue with the avatar link when the photo link is invalid
					$url = $contact['avatar'];
				} elseif (!empty($contact['avatar'])) {
					$url = $contact['avatar'];
				}
				$mimetext = '';
				if (!empty($url)) {
					$mime = ParseUrl::getContentType($url);
					if (!empty($mime)) {
						$mimetext = $mime[0] . '/' . $mime[1];
					} else {
						Logger::info('Invalid file', ['url' => $url]);
					}
					if (!empty($mimetext) && ($mime[0] != 'image') && ($mimetext != 'application/octet-stream')) {
						Logger::info('Unexpected Content-Type', ['mime' => $mimetext, 'url' => $url]);
						$mimetext = '';
					}
				}
				if (empty($mimetext)) {
					if ($customsize <= Proxy::PIXEL_MICRO) {
						$url = Contact::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
					} elseif ($customsize <= Proxy::PIXEL_THUMB) {
						$url = Contact::getDefaultAvatar($contact, Proxy::SIZE_THUMB);
					} else {
						$url = Contact::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
					}
				}
				return MPhoto::createPhotoForExternalResource($url, 0, $mimetext);
			case "header":
				$contact = Contact::getById($id, ['uid', 'url', 'header']);
				if (empty($contact)) {
					return false;
				}
				If (($contact['uid'] != 0) && empty($contact['header'])) {
					$contact = Contact::getByURL($contact['url'], false, ['header']);
				}
				if (!empty($contact['header'])) {
					$url = $contact['header'];
				} else {
					$url = DI::baseUrl() . '/images/blank.png';
				}
				return MPhoto::createPhotoForExternalResource($url);
			case "profile":
			case "custom":
				$scale = 4;
				break;
			case "micro":
				$scale = 6;
				break;
			case "avatar":
			default:
				$scale = 5;
		}

		$photo = MPhoto::selectFirst([], ["scale" => $scale, "uid" => $id, "profile" => 1]);
		if (empty($photo)) {
			$contact = DBA::selectFirst('contact', [], ['uid' => $id, 'self' => true]) ?: [];

			switch($type) {
				case "profile":
				case "custom":
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
					break;
				case "micro":
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
					break;
				case "avatar":
				default:
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_THUMB);
			}

			$parts = parse_url($default);
			if (!empty($parts['scheme']) || !empty($parts['host'])) {
				$photo = MPhoto::createPhotoForExternalResource($default);
			} else {
				$photo = MPhoto::createPhotoForSystemResource($default);
			}
		}
		return $photo;
	}
}
