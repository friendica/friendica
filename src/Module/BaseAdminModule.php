<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Network\HTTPException\ForbiddenException;

require_once 'boot.php';

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
abstract class BaseAdminModule extends BaseModule
{
	public static function post(array $parameters = [])
	{
		if (!is_site_admin()) {
			return;
		}

		// do not allow a page manager to access the admin panel at all.
		if (!empty($_SESSION['submanage'])) {
			return;
		}
	}

	public static function rawContent(array $parameters = [])
	{
		if (!is_site_admin()) {
			return '';
		}

		if (!empty($_SESSION['submanage'])) {
			return '';
		}

		return '';
	}

	public static function content(array $parameters = [])
	{
		$a = DI::app();

		if (!is_site_admin()) {
			notice(L10n::t('Please login to continue.'));
			Session::set('return_path', DI::args()->getQueryString());
			DI::baseUrl()->redirect('login');
		}

		if (!empty($_SESSION['submanage'])) {
			throw new ForbiddenException(L10n::t('Submanaged account can\'t access the administation pages. Please log back in as the master account.'));
		}

		// Header stuff
		$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/settings_head.tpl'), []);

		/*
		 * Side bar links
		 */

		// array(url, name, extra css classes)
		// not part of $aside to make the template more adjustable
		$aside_sub = [
			'information' => [L10n::t('Information'), [
				'overview'     => ['admin'             , L10n::t('Overview')                , 'overview'],
				'federation'   => ['admin/federation'  , L10n::t('Federation Statistics')   , 'federation']
			]],
			'configuration' => [L10n::t('Configuration'), [
				'site'         => ['admin/site'        , L10n::t('Site')                    , 'site'],
				'users'        => ['admin/users'       , L10n::t('Users')                   , 'users'],
				'addons'       => ['admin/addons'      , L10n::t('Addons')                  , 'addons'],
				'themes'       => ['admin/themes'      , L10n::t('Themes')                  , 'themes'],
				'features'     => ['admin/features'    , L10n::t('Additional features')     , 'features'],
				'tos'          => ['admin/tos'         , L10n::t('Terms of Service')        , 'tos'],
			]],
			'database' => [L10n::t('Database'), [
				'dbsync'       => ['admin/dbsync'      , L10n::t('DB updates')              , 'dbsync'],
				'deferred'     => ['admin/queue/deferred', L10n::t('Inspect Deferred Workers'), 'deferred'],
				'workerqueue'  => ['admin/queue'       , L10n::t('Inspect worker Queue')    , 'workerqueue'],
			]],
			'tools' => [L10n::t('Tools'), [
				'contactblock' => ['admin/blocklist/contact', L10n::t('Contact Blocklist')  , 'contactblock'],
				'blocklist'    => ['admin/blocklist/server' , L10n::t('Server Blocklist')   , 'blocklist'],
				'deleteitem'   => ['admin/item/delete' , L10n::t('Delete Item')             , 'deleteitem'],
			]],
			'logs' => [L10n::t('Logs'), [
				'logsconfig'   => ['admin/logs/', L10n::t('Logs')                           , 'logs'],
				'logsview'     => ['admin/logs/view'    , L10n::t('View Logs')              , 'viewlogs'],
			]],
			'diagnostics' => [L10n::t('Diagnostics'), [
				'phpinfo'      => ['admin/phpinfo'           , L10n::t('PHP Info')          , 'phpinfo'],
				'probe'        => ['probe'             , L10n::t('probe address')           , 'probe'],
				'webfinger'    => ['webfinger'         , L10n::t('check webfinger')         , 'webfinger'],
				'itemsource'   => ['admin/item/source' , L10n::t('Item Source')             , 'itemsource'],
				'babel'        => ['babel'             , L10n::t('Babel')                   , 'babel'],
			]],
		];

		$t = Renderer::getMarkupTemplate('admin/aside.tpl');
		$a->page['aside'] .= Renderer::replaceMacros($t, [
			'$admin' => ['addons_admin' => Addon::getAdminList()],
			'$subpages' => $aside_sub,
			'$admtxt' => L10n::t('Admin'),
			'$plugadmtxt' => L10n::t('Addon Features'),
			'$h_pending' => L10n::t('User registrations waiting for confirmation'),
			'$admurl' => 'admin/'
		]);

		return '';
	}
}
