<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Settings\Profile\Photo;

use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Module\BaseSettings;
use Friendica\Network\HTTPException;

class Crop extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			return;
		}

		$photo_prefix = $this->parameters['guid'];
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

		$path = 'profile/' . DI::app()->getLoggedInUserNickname();

		$base_image = Photo::selectFirst([], ['resource-id' => $resource_id, 'uid' => DI::userSession()->getLocalUserId(), 'scale' => $scale]);
		if (DBA::isResult($base_image)) {
			$Image = Photo::getImageForPhoto($base_image);
			if (empty($Image)) {
				throw new HTTPException\InternalServerErrorException();
			}

			if ($Image->isValid()) {
				// If setting for the default profile, unset the profile photo flag from any other photos I own
				DBA::update('photo', ['profile' => 0], ['uid' => DI::userSession()->getLocalUserId()]);

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

				$condition = ['resource-id' => $resource_id, 'uid' => DI::userSession()->getLocalUserId(), 'contact-id' => 0];

				$r = Photo::store(
					$Image,
					DI::userSession()->getLocalUserId(),
					0,
					$resource_id,
					$base_image['filename'],
					DI::l10n()->t(Photo::PROFILE_PHOTOS),
					4,
					Photo::USER_AVATAR
				);
				if ($r === false) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Image size reduction [%s] failed.', '300'));
				} else {
					Photo::update(['profile' => true], array_merge($condition, ['scale' => 4]));
				}

				$Image->scaleDown(80);

				$r = Photo::store(
					$Image,
					DI::userSession()->getLocalUserId(),
					0,
					$resource_id,
					$base_image['filename'],
					DI::l10n()->t(Photo::PROFILE_PHOTOS),
					5,
					Photo::USER_AVATAR
				);
				if ($r === false) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Image size reduction [%s] failed.', '80'));
				} else {
					Photo::update(['profile' => true], array_merge($condition, ['scale' => 5]));
				}

				$Image->scaleDown(48);

				$r = Photo::store(
					$Image,
					DI::userSession()->getLocalUserId(),
					0,
					$resource_id,
					$base_image['filename'],
					DI::l10n()->t(Photo::PROFILE_PHOTOS),
					6,
					Photo::USER_AVATAR
				);
				if ($r === false) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Image size reduction [%s] failed.', '48'));
				} else {
					Photo::update(['profile' => true], array_merge($condition, ['scale' => 6]));
				}

				Contact::updateSelfFromUserID(DI::userSession()->getLocalUserId(), true);

				DI::sysmsg()->addInfo(DI::l10n()->t('Shift-reload the page or clear browser cache if the new photo does not display immediately.'));

				// Update global directory in background
				Profile::publishUpdate(DI::userSession()->getLocalUserId());
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Unable to process image'));
			}
		}

		DI::baseUrl()->redirect($path);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		parent::content();

		$resource_id = $this->parameters['guid'];

		$photos = Photo::selectToArray([], ['resource-id' => $resource_id, 'uid' => DI::userSession()->getLocalUserId()], ['order' => ['scale' => false]]);
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
		if ($photos[0]['photo-type'] == Photo::USER_AVATAR && $havescale) {
			Photo::update(['profile' => false], ['uid' => DI::userSession()->getLocalUserId()]);

			Photo::update(['profile' => true], ['resource-id' => $resource_id, 'uid' => DI::userSession()->getLocalUserId()]);

			Contact::updateSelfFromUserID(DI::userSession()->getLocalUserId(), true);

			// Update global directory in background
			Profile::publishUpdate(DI::userSession()->getLocalUserId());

			DI::sysmsg()->addInfo(DI::l10n()->t('Profile picture successfully updated.'));

			DI::baseUrl()->redirect('profile/' . DI::app()->getLoggedInUserNickname());
		}

		$Image = Photo::getImageForPhoto($photos[0]);
		if (empty($Image)) {
			throw new HTTPException\InternalServerErrorException();
		}

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
