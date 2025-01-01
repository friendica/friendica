<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Themes;

use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;

class Index extends BaseAdmin
{
	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$allowed_themes = Theme::getAllowedList();

		// reload active themes
		if (!empty($_GET['action'])) {
			self::checkFormSecurityTokenRedirectOnError('/admin/themes', 'admin_themes', 't');

			switch ($_GET['action']) {
				case 'reload':
					$allowed_themes = array_unique($allowed_themes);
					foreach ($allowed_themes as $theme) {
						Theme::uninstall($theme);
						Theme::install($theme);
					}
					Theme::setAllowedList($allowed_themes);

					DI::sysmsg()->addInfo(DI::l10n()->t('Themes reloaded'));
					break;

				case 'toggle':
					$theme = $_GET['addon'] ?? '';
					if ($theme) {
						$theme = Strings::sanitizeFilePathItem($theme);
						if (!is_dir("view/theme/$theme")) {
							DI::sysmsg()->addNotice(DI::l10n()->t('Item not found.'));
							return '';
						}

						if (in_array($theme, Theme::getAllowedList())) {
							Theme::uninstall($theme);
							DI::sysmsg()->addInfo(DI::l10n()->t('Theme %s disabled.', $theme));
						} elseif (Theme::install($theme)) {
							DI::sysmsg()->addInfo(DI::l10n()->t('Theme %s successfully enabled.', $theme));
						} else {
							DI::sysmsg()->addNotice(DI::l10n()->t('Theme %s failed to install.', $theme));
						}
					}

					break;

			}

			DI::baseUrl()->redirect('admin/themes');
		}

		$themes = [];
		$files  = glob('view/theme/*');
		if (is_array($files)) {
			foreach ($files as $file) {
				$theme = basename($file);

				// Is there a style file?
				$theme_files = glob('view/theme/' . $theme . '/style.*');

				// If not then quit
				if (count($theme_files) == 0) {
					continue;
				}

				$is_experimental = intval(file_exists($file . '/experimental'));
				$is_supported    = 1 - (intval(file_exists($file . '/unsupported')));
				$is_allowed      = intval(in_array($theme, $allowed_themes));

				if ($is_allowed || $is_supported || DI::config()->get('system', 'show_unsupported_themes')) {
					$themes[] = ['name' => $theme, 'experimental' => $is_experimental, 'supported' => $is_supported, 'allowed' => $is_allowed];
				}
			}
		}

		$addons = [];
		foreach ($themes as $theme) {
			$addons[] = [$theme['name'], (($theme['allowed']) ? 'on' : 'off'), Theme::getInfo($theme['name'])];
		}

		$t = Renderer::getMarkupTemplate('admin/addons/index.tpl');
		return Renderer::replaceMacros($t, [
			'$title'               => DI::l10n()->t('Administration'),
			'$page'                => DI::l10n()->t('Themes'),
			'$submit'              => DI::l10n()->t('Save Settings'),
			'$reload'              => DI::l10n()->t('Reload active themes'),
			'$function'            => 'themes',
			'$addons'              => $addons,
			'$pcount'              => count($themes),
			'$noplugshint'         => DI::l10n()->t('No themes found on the system. They should be placed in %1$s', '<code>/view/themes</code>'),
			'$experimental'        => DI::l10n()->t('[Experimental]'),
			'$unsupported'         => DI::l10n()->t('[Unsupported]'),
			'$form_security_token' => self::getFormSecurityToken('admin_themes'),
		]);
	}
}
