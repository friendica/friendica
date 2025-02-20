<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
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

		if (!DI::userSession()->isSiteAdmin()) {
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
				'overview'   => ['admin'             , DI::l10n()->t('Overview')                , 'overview'],
				'federation' => ['admin/federation'  , DI::l10n()->t('Federation Statistics')   , 'federation']
			]],
			'configuration' => [DI::l10n()->t('Configuration'), [
				'site'     => ['admin/site'        , DI::l10n()->t('Site')                    , 'site'],
				'storage'  => ['admin/storage'     , DI::l10n()->t('Storage')                 , 'storage'],
				'addons'   => ['admin/addons'      , DI::l10n()->t('Addons')                  , 'addons'],
				'themes'   => ['admin/themes'      , DI::l10n()->t('Themes')                  , 'themes'],
				'features' => ['admin/features'    , DI::l10n()->t('Additional features')     , 'features'],
				'tos'      => ['admin/tos'         , DI::l10n()->t('Terms of Service')        , 'tos'],
			]],
			'database' => [DI::l10n()->t('Database'), [
				'dbsync'      => ['admin/dbsync'      , DI::l10n()->t('DB updates')              , 'dbsync'],
				'deferred'    => ['admin/queue/deferred', DI::l10n()->t('Inspect Deferred Workers'), 'deferred'],
				'workerqueue' => ['admin/queue'       , DI::l10n()->t('Inspect worker Queue')    , 'workerqueue'],
			]],
			'logs' => [DI::l10n()->t('Logs'), [
				'logsconfig' => ['admin/logs/', DI::l10n()->t('Logs')                           , 'logs'],
				'logsview'   => ['admin/logs/view'    , DI::l10n()->t('View Logs')              , 'viewlogs'],
			]],
			'diagnostics' => [DI::l10n()->t('Diagnostics'), [
				'phpinfo'   => ['admin/phpinfo?t=' . self::getFormSecurityToken('phpinfo'), DI::l10n()->t('PHP Info')                , 'phpinfo'],
				'probe'     => ['probe'             , DI::l10n()->t('probe address')           , 'probe'],
				'webfinger' => ['webfinger'         , DI::l10n()->t('check webfinger')         , 'webfinger'],
				'babel'     => ['babel'             , DI::l10n()->t('Babel')                   , 'babel'],
				'debug/ap'  => ['debug/ap'          , DI::l10n()->t('ActivityPub Conversion')  , 'debug/ap'],
			]],
		];

		$addons_admin = [];

		foreach (DI::addonHelper()->getEnabledAddonsWithAdminSettings() as $addonId) {
			$addons_admin[$addonId] = [
				'url'   => 'admin/addons/' . $addonId,
				'name'  => $addonId,
				'class' => 'addon',
			];
		}

		$t = Renderer::getMarkupTemplate('admin/aside.tpl');
		DI::page()['aside'] .= Renderer::replaceMacros($t, [
			'$admin'      => ['addons_admin' => $addons_admin],
			'$subpages'   => $aside_sub,
			'$admtxt'     => DI::l10n()->t('Admin'),
			'$plugadmtxt' => DI::l10n()->t('Addon Features'),
			'$h_pending'  => DI::l10n()->t('User registrations waiting for confirmation'),
			'$admurl'     => 'admin/'
		]);

		return '';
	}
}
