<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Themes;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Embed extends BaseAdmin
{
	/** @var AppHelper */
	protected $appHelper;
	/** @var Mode */
	protected $mode;

	public function __construct(AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Mode $mode, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->appHelper = $appHelper;
		$this->mode      = $mode;

		$theme = Strings::sanitizeFilePathItem($this->parameters['theme']);
		if (is_file("view/theme/$theme/config.php")) {
			$this->appHelper->setCurrentTheme($theme);
		}
	}

	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		$theme = Strings::sanitizeFilePathItem($this->parameters['theme']);
		if (is_file("view/theme/$theme/config.php")) {
			require_once "view/theme/$theme/config.php";
			if (function_exists('theme_admin_post')) {
				self::checkFormSecurityTokenRedirectOnError('/admin/themes/' . $theme . '/embed?mode=minimal', 'admin_theme_settings');
				theme_admin_post();
			}
		}

		if ($this->mode->isAjax()) {
			return;
		}

		$this->baseUrl->redirect('admin/themes/' . $theme . '/embed?mode=minimal');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$theme = Strings::sanitizeFilePathItem($this->parameters['theme']);
		if (!is_dir("view/theme/$theme")) {
			DI::sysmsg()->addNotice($this->t('Unknown theme.'));
			return '';
		}

		$admin_form = '';
		if (is_file("view/theme/$theme/config.php")) {
			require_once "view/theme/$theme/config.php";

			if (function_exists('theme_admin')) {
				$admin_form = theme_admin($this->appHelper);
			}
		}

		// Overrides normal theme style include to strip user param to show embedded theme settings
		Renderer::$theme['stylesheet'] = 'view/theme/' . $theme . '/style.pcss';

		$t = Renderer::getMarkupTemplate('admin/addons/embed.tpl');
		return Renderer::replaceMacros($t, [
			'$action'              => 'admin/themes/' . $theme . '/embed?mode=minimal',
			'$form'                => $admin_form,
			'$form_security_token' => self::getFormSecurityToken("admin_theme_settings"),
		]);
	}
}
