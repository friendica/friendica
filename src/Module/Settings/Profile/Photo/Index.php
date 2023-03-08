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
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\BaseSettings;
use Friendica\Network\HTTPException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Strings;

class Index extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/profile/photo', 'settings_profile_photo');

		if (empty($_FILES['userfile'])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Missing uploaded image.'));
			return;
		}

		$src = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];

		$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

		$maximagesize = Strings::getBytesFromShorthand(DI::config()->get('system', 'maximagesize', 0));

		if ($maximagesize && $filesize > $maximagesize) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize)));
			@unlink($src);
			return;
		}

		$imagedata = @file_get_contents($src);
		$Image = new Image($imagedata, $filetype);

		if (!$Image->isValid()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to process image.'));
			@unlink($src);
			return;
		}

		$Image->orient($src);
		@unlink($src);

		$max_length = DI::config()->get('system', 'max_image_length', 0);
		if ($max_length > 0) {
			$Image->scaleDown($max_length);
		}

		$width = $Image->getWidth();
		$height = $Image->getHeight();

		if ($width < 175 || $height < 175) {
			$Image->scaleUp(300);
			$width = $Image->getWidth();
			$height = $Image->getHeight();
		}

		$resource_id = Photo::newResource();

		$filename = '';

		if (!Photo::store($Image, DI::userSession()->getLocalUserId(), 0, $resource_id, $filename, DI::l10n()->t(Photo::PROFILE_PHOTOS), 0, Photo::USER_AVATAR)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Image upload failed.'));
		}

		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			if (!Photo::store($Image, DI::userSession()->getLocalUserId(), 0, $resource_id, $filename, DI::l10n()->t(Photo::PROFILE_PHOTOS), 1, Photo::USER_AVATAR)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Image size reduction [%s] failed.', '640'));
			}
		}

		DI::baseUrl()->redirect('settings/profile/photo/crop/' . $resource_id);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		parent::content();

		$args = DI::args();

		$newuser = $args->get($args->getArgc() - 1) === 'new';

		$contact = Contact::selectFirst(['avatar'], ['uid' => DI::userSession()->getLocalUserId(), 'self' => true]);

		$tpl = Renderer::getMarkupTemplate('settings/profile/photo/index.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title'           => DI::l10n()->t('Profile Picture Settings'),
			'$current_picture' => DI::l10n()->t('Current Profile Picture'),
			'$upload_picture'  => DI::l10n()->t('Upload Profile Picture'),
			'$lbl_upfile'      => DI::l10n()->t('Upload Picture:'),
			'$submit'          => DI::l10n()->t('Upload'),
			'$avatar'          => $contact['avatar'],
			'$form_security_token' => self::getFormSecurityToken('settings_profile_photo'),
			'$select'          => sprintf('%s %s',
				DI::l10n()->t('or'),
				($newuser) ?
					'<a href="' . DI::baseUrl() . '">' . DI::l10n()->t('skip this step') . '</a>'
					: '<a href="' . DI::baseUrl() . '/profile/' . DI::app()->getLoggedInUserNickname() . '/photos">'
						. DI::l10n()->t('select a photo from your photo albums') . '</a>'
			),
		]);

		return $o;
	}
}
