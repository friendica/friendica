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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * This abstract module is meant to be extended by all modules that are reserved to administrator users.
 *
 * It performs a blanket permission check in all the module methods as long as the relevant `parent::method()` is
 * called in the inheriting module.
 *
 * Additionally, it puts together the administration page aside with all the administration links.
 *
 * @package Friendica\Module
 */
abstract class BaseAdmin extends BaseModule
{
	/**
	 * Checks admin access and throws exceptions if not logged-in administrator
	 *
	 * @param bool $interactive
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function checkAdminAccess(bool $interactive = false)
	{
		if (!DI::userSession()->getLocalUserId()) {
			if ($interactive) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Please login to continue.'));
				DI::session()->set('return_path', DI::args()->getQueryString());
				DI::baseUrl()->redirect('login');
			} else {
				throw new HTTPException\UnauthorizedException(DI::l10n()->t('Please login to continue.'));
			}
		}

		if (!DI::app()->isSiteAdmin()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('You don\'t have access to administration pages.'));
		}

		if (DI::userSession()->getSubManagedUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Submanaged account can\'t access the administration pages. Please log back in as the main account.'));
		}
	}

	protected function content(array $request = []): string
	{
		self::checkAdminAccess(true);

		// Header stuff
		DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/settings_head.tpl'), []);

		/*
		 * Side bar links
		 */

		// array(url, name, extra css classes)
		// not part of $aside to make the template more adjustable
		$aside_sub = [
			'information' => [DI::l10n()->t('Information'), [
				'overview'     => ['admin'             , DI::l10n()->t('Overview')                , 'overview'],
				'federation'   => ['admin/federation'  , DI::l10n()->t('Federation Statistics')   , 'federation']
			]],
			'configuration' => [DI::l10n()->t('Configuration'), [
				'site'         => ['admin/site'        , DI::l10n()->t('Site')                    , 'site'],
				'storage'      => ['admin/storage'     , DI::l10n()->t('Storage')                 , 'storage'],
				'addons'       => ['admin/addons'      , DI::l10n()->t('Addons')                  , 'addons'],
				'themes'       => ['admin/themes'      , DI::l10n()->t('Themes')                  , 'themes'],
				'features'     => ['admin/features'    , DI::l10n()->t('Additional features')     , 'features'],
				'tos'          => ['admin/tos'         , DI::l10n()->t('Terms of Service')        , 'tos'],
			]],
			'database' => [DI::l10n()->t('Database'), [
				'dbsync'       => ['admin/dbsync'      , DI::l10n()->t('DB updates')              , 'dbsync'],
				'deferred'     => ['admin/queue/deferred', DI::l10n()->t('Inspect Deferred Workers'), 'deferred'],
				'workerqueue'  => ['admin/queue'       , DI::l10n()->t('Inspect worker Queue')    , 'workerqueue'],
			]],
			'logs' => [DI::l10n()->t('Logs'), [
				'logsconfig'   => ['admin/logs/', DI::l10n()->t('Logs')                           , 'logs'],
				'logsview'     => ['admin/logs/view'    , DI::l10n()->t('View Logs')              , 'viewlogs'],
			]],
			'diagnostics' => [DI::l10n()->t('Diagnostics'), [
				'phpinfo'      => ['admin/phpinfo'           , DI::l10n()->t('PHP Info')          , 'phpinfo'],
				'probe'        => ['probe'             , DI::l10n()->t('probe address')           , 'probe'],
				'webfinger'    => ['webfinger'         , DI::l10n()->t('check webfinger')         , 'webfinger'],
				'babel'        => ['babel'             , DI::l10n()->t('Babel')                   , 'babel'],
				'debug/ap'     => ['debug/ap'          , DI::l10n()->t('ActivityPub Conversion')  , 'debug/ap'],
			]],
		];

		$t = Renderer::getMarkupTemplate('admin/aside.tpl');
		DI::page()['aside'] .= Renderer::replaceMacros($t, [
			'$admin' => ['addons_admin' => Addon::getAdminList()],
			'$subpages' => $aside_sub,
			'$admtxt' => DI::l10n()->t('Admin'),
			'$plugadmtxt' => DI::l10n()->t('Addon Features'),
			'$h_pending' => DI::l10n()->t('User registrations waiting for confirmation'),
			'$admurl' => 'admin/'
		]);

		return '';
	}
}
