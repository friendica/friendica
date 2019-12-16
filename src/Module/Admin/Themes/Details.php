<?php

namespace Friendica\Module\Admin\Themes;

use Friendica\Content\Text\Markdown;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Strings;

class Details extends BaseAdminModule
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$a = DI::app();

		if ($a->argc > 2) {
			// @TODO: Replace with parameter from router
			$theme = $a->argv[2];
			$theme = Strings::sanitizeFilePathItem($theme);
			if (is_file("view/theme/$theme/config.php")) {
				require_once "view/theme/$theme/config.php";

				if (function_exists('theme_admin_post')) {
					theme_admin_post($a);
				}
			}

			info(L10n::t('Theme settings updated.'));

			if (DI::mode()->isAjax()) {
				return;
			}

			DI::baseUrl()->redirect('admin/themes/' . $theme);
		}
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		if ($a->argc > 2) {
			// @TODO: Replace with parameter from router
			$theme = $a->argv[2];
			$theme = Strings::sanitizeFilePathItem($theme);
			if (!is_dir("view/theme/$theme")) {
				notice(L10n::t("Item not found."));
				return '';
			}

			$isEnabled = in_array($theme, Theme::getAllowedList());
			if ($isEnabled) {
				$status = "on";
				$action = L10n::t("Disable");
			} else {
				$status = "off";
				$action = L10n::t("Enable");
			}

			if (!empty($_GET['action']) && $_GET['action'] == 'toggle') {
				parent::checkFormSecurityTokenRedirectOnError('/admin/themes', 'admin_themes', 't');

				if ($isEnabled) {
					Theme::uninstall($theme);
					info(L10n::t('Theme %s disabled.', $theme));
				} elseif (Theme::install($theme)) {
					info(L10n::t('Theme %s successfully enabled.', $theme));
				} else {
					info(L10n::t('Theme %s failed to install.', $theme));
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
					$admin_form = '<iframe onload="resizeIframe(this);" src="/admin/themes/' . $theme . '/embed?mode=minimal" width="100%" height="600px" frameborder="no"></iframe>';
				}
			}

			$screenshot = [Theme::getScreenshot($theme), L10n::t('Screenshot')];
			if (!stristr($screenshot[0], $theme)) {
				$screenshot = null;
			}

			$t = Renderer::getMarkupTemplate('admin/addons/details.tpl');
			return Renderer::replaceMacros($t, [
				'$title' => L10n::t('Administration'),
				'$page' => L10n::t('Themes'),
				'$toggle' => L10n::t('Toggle'),
				'$settings' => L10n::t('Settings'),
				'$baseurl' => DI::baseUrl()->get(true),
				'$addon' => $theme,
				'$status' => $status,
				'$action' => $action,
				'$info' => Theme::getInfo($theme),
				'$function' => 'themes',
				'$admin_form' => $admin_form,
				'$str_author' => L10n::t('Author: '),
				'$str_maintainer' => L10n::t('Maintainer: '),
				'$screenshot' => $screenshot,
				'$readme' => $readme,

				'$form_security_token' => parent::getFormSecurityToken("admin_themes"),
			]);
		}

		DI::baseUrl()->redirect('admin/themes');
	}
}
