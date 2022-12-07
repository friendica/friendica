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
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo as MPhoto;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Core\Storage\Type\ExternalResource;
use Friendica\Core\Storage\Type\SystemResource;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
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

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
			if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
				header('Etag: ' . $_SERVER['HTTP_IF_NONE_MATCH']);
			}
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
			header('Cache-Control: max-age=31536000');
			if (function_exists('header_remove')) {
				header_remove('Last-Modified');
				header_remove('Expires');
				header_remove('Cache-Control');
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

			$photo = self::getPhotoById($id, $this->parameters['type'], $customsize ?: Proxy::PIXEL_SMALL);
		} else {
			$photoid = pathinfo($this->parameters['name'], PATHINFO_FILENAME);
			$scale = 0;
			if (substr($photoid, -2, 1) == '-') {
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

		$cacheable = ($photo['allow_cid'] . $photo['allow_gid'] . $photo['deny_cid'] . $photo['deny_gid'] === '') && (isset($photo['cacheable']) ? $photo['cacheable'] : true);

		$stamp = microtime(true);

		$imgdata = MPhoto::getImageDataForPhoto($photo);
		if (empty($imgdata) && empty($photo['blurhash'])) {
			throw new HTTPException\NotFoundException();
		} elseif (empty($imgdata) && !empty($photo['blurhash'])) {
			$image = New Image('', 'image/png');
			$image->getFromBlurHash($photo['blurhash'], $photo['width'], $photo['height']);
			$imgdata = $image->asString();
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

		if (!empty($request['static'])) {
			$img = new Image($imgdata, $photo['type']);
			$img->toStatic();
			$imgdata = $img->asString();
		}

		// if customsize is set and image is not a gif, resize it
		if ($photo['type'] !== 'image/gif' && $customsize > 0 && $customsize <= Proxy::PIXEL_THUMB && $square_resize) {
			$img = new Image($imgdata, $photo['type']);
			$img->scaleToSquare($customsize);
			$imgdata = $img->asString();
		} elseif ($photo['type'] !== 'image/gif' && $customsize > 0) {
			$img = new Image($imgdata, $photo['type']);
			$img->scaleDown($customsize);
			$imgdata = $img->asString();
		}

		if (function_exists('header_remove')) {
			header_remove('Pragma');
			header_remove('pragma');
		}

		header('Content-type: ' . $photo['type']);

		$stamp = microtime(true);
		if (!$cacheable) {
			// it is a private photo that they have no permission to view.
			// tell the browser not to cache it, in case they authenticate
			// and subsequently have permission to see it
			header('Cache-Control: no-store, no-cache, must-revalidate');
		} else {
			$md5 = $photo['hash'] ?: md5($imgdata);
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
			header("Etag: \"{$md5}\"");
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
			header('Cache-Control: max-age=31536000');
		}
		$checksum = microtime(true) - $stamp;

		$stamp = microtime(true);
		echo $imgdata;
		$output = microtime(true) - $stamp;

		$total = microtime(true) - $totalstamp;
		$rest = $total - ($fetch + $data + $checksum + $output);

		if (!is_null($scale) && ($scale < 4)) {
			Logger::debug('Performance:', ['scale' => $scale, 'resource' => $photo['resource-id'],
				'total' => number_format($total, 3), 'fetch' => number_format($fetch, 3),
				'data' => number_format($data, 3), 'checksum' => number_format($checksum, 3),
				'output' => number_format($output, 3), 'rest' => number_format($rest, 3)]);
		}

		System::exit();
	}

	/**
	 * Fetches photo record by given id number, type and custom size
	 *
	 * @param int $id Photo id
	 * @param string $type Photo type
	 * @param int $customsize Custom size (?)
	 * @return array|bool Array on success, false on error
	 */
	private static function getPhotoById(int $id, string $type, int $customsize)
	{
		switch($type) {
			case 'preview':
				$media = DBA::selectFirst('post-media', ['preview', 'url', 'preview-height', 'preview-width', 'height', 'width', 'mimetype', 'type', 'uri-id', 'blurhash'], ['id' => $id]);
				if (empty($media)) {
					return false;
				}
				$url    = $media['preview'];
				$width  = $media['preview-width'];
				$height = $media['preview-height'];

				if (empty($url) && ($media['type'] == Post\Media::IMAGE)) {
					$url    = $media['url'];
					$width  = $media['width'];
					$height = $media['height'];
				}

				if (empty($url)) {
					return false;
				}

				if (Network::isLocalLink($url) && preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $url, $matches)) {
					return MPhoto::getPhoto($matches[1], $matches[2]);
				}

				return MPhoto::createPhotoForExternalResource($url, (int)DI::userSession()->getLocalUserId(), $media['mimetype'] ?? '', $media['blurhash'], $width, $height);
			case 'media':
				$media = DBA::selectFirst('post-media', ['url', 'height', 'width', 'mimetype', 'uri-id', 'blurhash'], ['id' => $id, 'type' => Post\Media::IMAGE]);
				if (empty($media)) {
					return false;
				}

				if (Network::isLocalLink($media['url']) && preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $media['url'], $matches)) {
					return MPhoto::getPhoto($matches[1], $matches[2]);
				}

				return MPhoto::createPhotoForExternalResource($media['url'], (int)DI::userSession()->getLocalUserId(), $media['mimetype'], $media['blurhash'], $media['width'], $media['height']);
			case 'link':
				$link = DBA::selectFirst('post-link', ['url', 'mimetype'], ['id' => $id]);
				if (empty($link)) {
					return false;
				}

				return MPhoto::createPhotoForExternalResource($link['url'], (int)DI::userSession()->getLocalUserId(), $link['mimetype'] ?? '');
			case 'contact':
				$fields = ['uid', 'uri-id', 'url', 'nurl', 'avatar', 'photo', 'xmpp', 'addr', 'network', 'failed', 'updated'];
				$contact = Contact::getById($id, $fields);
				if (empty($contact)) {
					return false;
				}

				// For local users directly use the photo record that is marked as the profile
				if (Network::isLocalLink($contact['url'])) {
					$contact = Contact::selectFirst($fields, ['nurl' => $contact['nurl'], 'self' => true]);
					if (!empty($contact)) {
						if ($customsize <= Proxy::PIXEL_MICRO) {
							$scale = 6;
						} elseif ($customsize <= Proxy::PIXEL_THUMB) {
							$scale = 5;
						} else {
							$scale = 4;
						}
						$photo = MPhoto::selectFirst([], ['scale' => $scale, 'uid' => $contact['uid'], 'profile' => 1]);
						if (!empty($photo)) {
							return $photo;
						}
					}
				}

				if (!empty($contact['uid']) && empty($contact['photo']) && empty($contact['avatar'])) {
					$contact = Contact::getByURL($contact['url'], false, $fields);
				}

				if (!empty($contact['photo']) && !empty($contact['avatar'])) {
					// Fetch photo directly
					$resourceid = MPhoto::ridFromURI($contact['photo']);
					if (!empty($resourceid)) {
						$photo = MPhoto::selectFirst([], ['resource-id' => $resourceid], ['order' => ['scale']]);
						if (!empty($photo)) {
							return $photo;
						} else {
							$url = $contact['avatar'];
						}
					} else {
						$url = $contact['photo'];
					}
				} elseif (!empty($contact['avatar'])) {
					$url = $contact['avatar'];
				}
				$mimetext = '';
				if (!empty($url)) {
					$mime = ParseUrl::getContentType($url, HttpClientAccept::IMAGE);
					if (!empty($mime)) {
						$mimetext = $mime[0] . '/' . $mime[1];
					} else {
						// Only update federated accounts that hadn't failed before and hadn't been updated recently
						$update = in_array($contact['network'], Protocol::FEDERATED) && !$contact['failed']
							&& ((time() - strtotime($contact['updated']) > 86400));
						if ($update) {
							$curlResult = DI::httpClient()->head($url, [HttpClientOptions::ACCEPT_CONTENT => HttpClientAccept::IMAGE]);
							$update = !$curlResult->isSuccess() && ($curlResult->getReturnCode() == 404);
							Logger::debug('Got return code for avatar', ['return code' => $curlResult->getReturnCode(), 'cid' => $id, 'url' => $contact['url'], 'avatar' => $url]);
						}
						if ($update) {
							Logger::info('Invalid file, contact update initiated', ['cid' => $id, 'url' => $contact['url'], 'avatar' => $url]);
							Worker::add(Worker::PRIORITY_LOW, 'UpdateContact', $id);
						} else {
							Logger::info('Invalid file', ['cid' => $id, 'url' => $contact['url'], 'avatar' => $url]);
						}
					}
					if (!empty($mimetext) && ($mime[0] != 'image') && ($mimetext != 'application/octet-stream')) {
						Logger::info('Unexpected Content-Type', ['mime' => $mimetext, 'url' => $url]);
						$mimetext = '';
					} if (!empty($mimetext)) {
						Logger::debug('Expected Content-Type', ['mime' => $mimetext, 'url' => $url]);
					}
				}
				if (empty($mimetext)) {
					if ($customsize <= Proxy::PIXEL_MICRO) {
						$url = Contact::getDefaultAvatar($contact ?: [], Proxy::SIZE_MICRO);
					} elseif ($customsize <= Proxy::PIXEL_THUMB) {
						$url = Contact::getDefaultAvatar($contact ?: [], Proxy::SIZE_THUMB);
					} else {
						$url = Contact::getDefaultAvatar($contact ?: [], Proxy::SIZE_SMALL);
					}
				}
				return MPhoto::createPhotoForExternalResource($url, 0, $mimetext);
			case 'header':
				$fields = ['uid', 'url', 'header', 'network', 'gsid'];
				$contact = Contact::getById($id, $fields);
				if (empty($contact)) {
					return false;
				}
				If (($contact['uid'] != 0) && empty($contact['header'])) {
					$contact = Contact::getByURL($contact['url'], false, $fields);
				}
				if (!empty($contact['header'])) {
					$url = $contact['header'];
				} else {
					$url = Contact::getDefaultHeader($contact);
				}
				return MPhoto::createPhotoForExternalResource($url);
			case 'banner':
				$photo = MPhoto::selectFirst([], ['scale' => 3, 'uid' => $id, 'photo-type' => MPhoto::USER_BANNER]);
				if (!empty($photo)) {
					return $photo;
				}
				return MPhoto::createPhotoForExternalResource(DI::baseUrl() . '/images/friendica-banner.jpg');
			case 'profile':
			case 'custom':
				$scale = 4;
				break;
			case 'micro':
				$scale = 6;
				break;
			case 'avatar':
			default:
				$scale = 5;
		}

		$photo = MPhoto::selectFirst([], ['scale' => $scale, 'uid' => $id, 'profile' => 1]);
		if (empty($photo)) {
			$contact = DBA::selectFirst('contact', [], ['uid' => $id, 'self' => true]) ?: [];

			switch($type) {
				case 'profile':
				case 'custom':
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
					break;
				case 'micro':
					$default = Contact::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
					break;
				case 'avatar':
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
