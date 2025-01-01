<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Themes;

use Friendica\Content\Text\Markdown;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;

class Details extends BaseAdmin
{
	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$theme = Strings::sanitizeFilePathItem($this->parameters['theme']);
		if (!is_dir("view/theme/$theme")) {
			DI::sysmsg()->addNotice(DI::l10n()->t("Item not found."));
			return '';
		}

		$isEnabled = in_array($theme, Theme::getAllowedList());
		if ($isEnabled) {
			$status = "on";
			$action = DI::l10n()->t("Disable");
		} else {
			$status = "off";
			$action = DI::l10n()->t("Enable");
		}

		if (!empty($_GET['action']) && $_GET['action'] == 'toggle') {
			self::checkFormSecurityTokenRedirectOnError('/admin/themes', 'admin_themes', 't');

			if ($isEnabled) {
				Theme::uninstall($theme);
				DI::sysmsg()->addInfo(DI::l10n()->t('Theme %s disabled.', $theme));
			} elseif (Theme::install($theme)) {
				DI::sysmsg()->addInfo(DI::l10n()->t('Theme %s successfully enabled.', $theme));
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Theme %s failed to install.', $theme));
			}

			DI::baseUrl()->redirect('admin/themes/' . $theme);
		}

		$readme = null;
		if (is_file("view/theme/$theme/README.md")) {
			$readme = Markdown::convert(file_get_contents("view/theme/$theme/README.md"), false);
		} elseif (is_file("view/theme/$theme/README")) {
			$readme = "<pre>" . file_get_contents("view/theme/$theme/README") . "</pre>";
		}

		$admin_form = '';
		if (is_file("view/theme/$theme/config.php")) {
			require_once "view/theme/$theme/config.php";

			if (function_exists('theme_admin')) {
				$admin_form = '<iframe onload="resizeIframe(this);" src="' . DI::baseUrl() . '/admin/themes/' . $theme . '/embed?mode=minimal" width="100%" height="600px" frameborder="no"></iframe>';
			}
		}

		$screenshot = [Theme::getScreenshot($theme), DI::l10n()->t('Screenshot')];
		if (!stristr($screenshot[0], $theme)) {
			$screenshot = null;
		}

		$t = Renderer::getMarkupTemplate('admin/addons/details.tpl');
		return Renderer::replaceMacros($t, [
			'$title'          => DI::l10n()->t('Administration'),
			'$page'           => DI::l10n()->t('Themes'),
			'$toggle'         => DI::l10n()->t('Toggle'),
			'$settings'       => DI::l10n()->t('Settings'),
			'$addon'          => $theme,
			'$status'         => $status,
			'$action'         => $action,
			'$info'           => Theme::getInfo($theme),
			'$function'       => 'themes',
			'$admin_form'     => $admin_form,
			'$str_author'     => DI::l10n()->t('Author: '),
			'$str_maintainer' => DI::l10n()->t('Maintainer: '),
			'$screenshot'     => $screenshot,
			'$readme'         => $readme,

			'$form_security_token' => self::getFormSecurityToken("admin_themes"),
		]);
	}
}
