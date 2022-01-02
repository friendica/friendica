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
 * View for user import
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\UserImport;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;

function uimport_post(App $a)
{
	if ((DI::config()->get('config', 'register_policy') != \Friendica\Module\Register::OPEN) && !$a->isSiteAdmin()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	if (!empty($_FILES['accountfile'])) {
		UserImport::importAccount($_FILES['accountfile']);
		return;
	}
}

function uimport_content(App $a)
{
	if ((DI::config()->get('config', 'register_policy') != \Friendica\Module\Register::OPEN) && !$a->isSiteAdmin()) {
		notice(DI::l10n()->t('User imports on closed servers can only be done by an administrator.'));
		return;
	}

	$max_dailies = intval(DI::config()->get('system', 'max_daily_registrations'));
	if ($max_dailies) {
		$total = DBA::count('user', ["`register_date` > UTC_TIMESTAMP - INTERVAL 1 DAY"]);
		if ($total >= $max_dailies) {
			Logger::notice('max daily registrations exceeded.');
			notice(DI::l10n()->t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'));
			return;
		}
	}

	$tpl = Renderer::getMarkupTemplate("uimport.tpl");
	return Renderer::replaceMacros($tpl, [
		'$regbutt' => DI::l10n()->t('Import'),
		'$import' => [
			'title' => DI::l10n()->t("Move account"),
			'intro' => DI::l10n()->t("You can import an account from another Friendica server."),
			'instruct' => DI::l10n()->t("You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here."),
			'warn' => DI::l10n()->t("This feature is experimental. We can't import contacts from the OStatus network \x28GNU Social/Statusnet\x29 or from Diaspora"),
			'field' => ['accountfile', DI::l10n()->t('Account file'), '<input id="id_accountfile" name="accountfile" type="file">', DI::l10n()->t('To export your account, go to "Settings->Export your personal data" and select "Export account"')],
		],
	]);
}
