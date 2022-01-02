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

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Util\Strings;

function removeme_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if (!empty($_SESSION['submanage'])) {
		return;
	}

	if (empty($_POST['qxz_password'])) {
		return;
	}

	if (empty($_POST['verify'])) {
		return;
	}

	if ($_POST['verify'] !== $_SESSION['remove_account_verify']) {
		return;
	}

	// send notification to admins so that they can clean um the backups
	// send email to admins
	$admin_mails = explode(",", str_replace(" ", "", DI::config()->get('config', 'admin_email')));
	foreach ($admin_mails as $mail) {
		$admin = DBA::selectFirst('user', ['uid', 'language', 'email', 'username'], ['email' => $mail]);
		if (!DBA::isResult($admin)) {
			continue;
		}

		$email = DI::emailer()
			->newSystemMail()
			->withMessage(
				DI::l10n()->t('[Friendica System Notify]') . ' ' . DI::l10n()->t('User deleted their account'),
				DI::l10n()->t('On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'),
				DI::l10n()->t('The user id is %d', local_user()))
			->forUser($admin)
			->withRecipient($admin['email'])
			->build();
		DI::emailer()->send($email);
	}

	if (User::getIdFromPasswordAuthentication($a->getLoggedInUserId(), trim($_POST['qxz_password']))) {
		User::remove($a->getLoggedInUserId());

		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		DI::baseUrl()->redirect();
		// NOTREACHED
	}
}

function removeme_content(App $a)
{
	if (!local_user()) {
		DI::baseUrl()->redirect();
	}

	$hash = Strings::getRandomHex();

	require_once("mod/settings.php");
	settings_init($a);

	$_SESSION['remove_account_verify'] = $hash;

	$tpl = Renderer::getMarkupTemplate('removeme.tpl');
	$o = Renderer::replaceMacros($tpl, [
		'$basedir' => DI::baseUrl()->get(),
		'$hash' => $hash,
		'$title' => DI::l10n()->t('Remove My Account'),
		'$desc' => DI::l10n()->t('This will completely remove your account. Once this has been done it is not recoverable.'),
		'$passwd' => DI::l10n()->t('Please enter your password for verification:'),
		'$submit' => DI::l10n()->t('Remove My Account')
	]);

	return $o;
}
