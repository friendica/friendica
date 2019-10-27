<?php

namespace Friendica\Module\Settings\Profile\Photo;

use Friendica\App\Arguments;
use Friendica\Core\Config;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\BaseSettingsModule;
use Friendica\Network\HTTPException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Strings;

class Index extends BaseSettingsModule
{
	public static function post(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/profile/photo', 'settings_profile_photo');

		if (empty($_FILES['userfile'])) {
			notice(DI::l10n()->t('Missing uploaded image.'));
			return;
		}

		$src = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];
		if ($filetype == '') {
			$filetype = Images::guessType($filename);
		}

		$maximagesize = Config::get('system', 'maximagesize', 0);

		if ($maximagesize && $filesize > $maximagesize) {
			notice(DI::l10n()->t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize)));
			@unlink($src);
			return;
		}

		$imagedata = @file_get_contents($src);
		$Image = new Image($imagedata, $filetype);

		if (!$Image->isValid()) {
			notice(DI::l10n()->t('Unable to process image.'));
			@unlink($src);
			return;
		}

		$Image->orient($src);
		@unlink($src);

		$max_length = Config::get('system', 'max_image_length', 0);
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

		if (Photo::store($Image, local_user(), 0, $resource_id, $filename, DI::l10n()->t('Profile Photos'), 0)) {
			info(DI::l10n()->t('Image uploaded successfully.'));
		} else {
			notice(DI::l10n()->t('Image upload failed.'));
		}

		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			if (!Photo::store($Image, local_user(), 0, $resource_id, $filename, DI::l10n()->t('Profile Photos'), 1)) {
				notice(DI::l10n()->t('Image size reduction [%s] failed.', '640'));
			}
		}

		DI::baseUrl()->redirect('settings/profile/photo/crop/' . $resource_id);
	}

	public static function content(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		parent::content();

		/** @var Arguments $args */
		$args = DI::args();

		$newuser = $args->get($args->getArgc() - 1) === 'new';

		$contact = Contact::selectFirst(['avatar'], ['uid' => local_user(), 'self' => true]);

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
					: '<a href="' . DI::baseUrl() . '/photos/' . DI::app()->user['nickname'] . '">'
						. DI::l10n()->t('select a photo from your photo albums') . '</a>'
			),
		]);

		return $o;
	}
}
