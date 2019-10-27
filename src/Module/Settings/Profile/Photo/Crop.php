<?php

namespace Friendica\Module\Settings\Profile\Photo;

use Friendica\Core\Config;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\BaseSettingsModule;
use Friendica\Network\HTTPException;

class Crop extends BaseSettingsModule
{
	public static function post(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			return;
		}

		$photo_prefix = $parameters['guid'];
		$resource_id = $photo_prefix;
		$scale = 0;
		if (substr($photo_prefix, -2, 1) == '-') {
			list($resource_id, $scale) = explode('-', $photo_prefix);
		}

		self::checkFormSecurityTokenRedirectOnError('settings/profile/photo/crop/' . $photo_prefix, 'settings_profile_photo_crop');

		$action = $_POST['action'] ?? 'crop';

		// Image selection origin is top left
		$selectionX = intval($_POST['xstart'] ?? 0);
		$selectionY = intval($_POST['ystart'] ?? 0);
		$selectionW = intval($_POST['width']  ?? 0);
		$selectionH = intval($_POST['height'] ?? 0);

		$path = 'profile/' . DI::app()->user['nickname'];

		$base_image = Photo::selectFirst([], ['resource-id' => $resource_id, 'uid' => local_user(), 'scale' => $scale]);
		if (DBA::isResult($base_image)) {
			$Image = Photo::getImageForPhoto($base_image);
			if ($Image->isValid()) {
				// If setting for the default profile, unset the profile photo flag from any other photos I own
				DBA::update('photo', ['profile' => 0], ['uid' => local_user()]);

				// Normalizing expected square crop parameters
				$selectionW = $selectionH = min($selectionW, $selectionH);

				$imageIsSquare = $Image->getWidth() === $Image->getHeight();
				$selectionIsFullImage = $selectionX === 0 && $selectionY === 0 && $selectionW === $Image->getWidth() && $selectionH === $Image->getHeight();

				// Bypassed UI with a rectangle image, we force a square cropped image
				if (!$imageIsSquare && $action == 'skip') {
					$selectionX = $selectionY = 0;
					$selectionW = $selectionH = min($Image->getWidth(), $Image->getHeight());
					$action = 'crop';
				}

				// Selective crop if it was asked and the selection isn't the full image
				if ($action == 'crop'
					&& !($imageIsSquare && !$selectionIsFullImage)
				) {
					$Image->crop(300, $selectionX, $selectionY, $selectionW, $selectionH);
					$resource_id = Photo::newResource();
				} else {
					$Image->scaleDown(300);
				}

				$r = Photo::store(
					$Image,
					local_user(),
					0,
					$resource_id,
					$base_image['filename'],
					DI::l10n()->t('Profile Photos'),
					4,
					1
				);
				if ($r === false) {
					notice(DI::l10n()->t('Image size reduction [%s] failed.', '300'));
				}

				$Image->scaleDown(80);

				$r = Photo::store(
					$Image,
					local_user(),
					0,
					$resource_id,
					$base_image['filename'],
					DI::l10n()->t('Profile Photos'),
					5,
					1
				);
				if ($r === false) {
					notice(DI::l10n()->t('Image size reduction [%s] failed.', '80'));
				}

				$Image->scaleDown(48);

				$r = Photo::store(
					$Image,
					local_user(),
					0,
					$resource_id,
					$base_image['filename'],
					DI::l10n()->t('Profile Photos'),
					6,
					1
				);
				if ($r === false) {
					notice(DI::l10n()->t('Image size reduction [%s] failed.', '48'));
				}

				Contact::updateSelfFromUserID(local_user(), true);

				info(DI::l10n()->t('Shift-reload the page or clear browser cache if the new photo does not display immediately.'));
				// Update global directory in background
				if ($path && strlen(Config::get('system', 'directory'))) {
					Worker::add(PRIORITY_LOW, 'Directory', DI::baseUrl()->get() . '/' . $path);
				}

				Worker::add(PRIORITY_LOW, 'ProfileUpdate', local_user());
			} else {
				notice(DI::l10n()->t('Unable to process image'));
			}
		}

		DI::baseUrl()->redirect($path);
	}

	public static function content(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		parent::content();

		$resource_id = $parameters['guid'];

		$photos = Photo::selectToArray([], ['resource-id' => $resource_id, 'uid' => local_user()], ['order' => ['scale' => false]]);
		if (!DBA::isResult($photos)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Photo not found.'));
		}

		$havescale = false;
		$smallest = 0;
		foreach ($photos as $photo) {
			$smallest = $photo['scale'] == 1 ? 1 : $smallest;
			$havescale = $havescale || $photo['scale'] == 5;
		}

		// set an already uloaded photo as profile photo
		// if photo is in 'Profile Photos', change it in db
		if ($photos[0]['album'] == DI::l10n()->t('Profile Photos') && $havescale) {
			Photo::update(['profile' => false], ['uid' => local_user()]);

			Photo::update(['profile' => true], ['resource-id' => $resource_id, 'uid' => local_user()]);

			Contact::updateSelfFromUserID(local_user(), true);

			// Update global directory in background
			if (Session::get('my_url') && strlen(Config::get('system', 'directory'))) {
				Worker::add(PRIORITY_LOW, 'Directory', Session::get('my_url'));
			}

			notice(DI::l10n()->t('Profile picture successfully updated.'));

			DI::baseUrl()->redirect('profile/' . DI::app()->user['nickname']);
		}

		$Image = Photo::getImageForPhoto($photos[0]);

		$imagecrop = [
			'resource-id' => $resource_id,
			'scale'       => $smallest,
			'ext'         => $Image->getExt(),
		];

		$isSquare = $Image->getWidth() === $Image->getHeight();

		DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/profile/photo/crop_head.tpl'), []);

		$filename = $imagecrop['resource-id'] . '-' . $imagecrop['scale'] . '.' . $imagecrop['ext'];
		$tpl = Renderer::getMarkupTemplate('settings/profile/photo/crop.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$filename'  => $filename,
			'$resource'  => $imagecrop['resource-id'] . '-' . $imagecrop['scale'],
			'$image_url' => DI::baseUrl() . '/photo/' . $filename,
			'$title'     => DI::l10n()->t('Crop Image'),
			'$desc'      => DI::l10n()->t('Please adjust the image cropping for optimum viewing.'),
			'$form_security_token' => self::getFormSecurityToken('settings_profile_photo_crop'),
			'$skip'      => $isSquare ? DI::l10n()->t('Use Image As Is') : '',
			'$crop'      => DI::l10n()->t('Crop Image'),
		]);

		return $o;
	}
}
