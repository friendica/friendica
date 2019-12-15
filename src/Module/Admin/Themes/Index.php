<?php

namespace Friendica\Module\Admin\Themes;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Strings;

class Index extends BaseAdminModule
{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$allowed_themes = Theme::getAllowedList();

		// reload active themes
		if (!empty($_GET['action'])) {
			parent::checkFormSecurityTokenRedirectOnError($a->getBaseURL() . '/admin/themes', 'admin_themes', 't');

			switch ($_GET['action']) {
				case 'reload':
					$allowed_themes = array_unique($allowed_themes);
					foreach ($allowed_themes as $theme) {
						Theme::uninstall($theme);
						Theme::install($theme);
					}
					Theme::setAllowedList($allowed_themes);

					info('Themes reloaded');
					break;

				case 'toggle' :
					$theme = $_GET['addon'] ?? '';
					if ($theme) {
						$theme = Strings::sanitizeFilePathItem($theme);
						if (!is_dir("view/theme/$theme")) {
							notice(L10n::t('Item not found.'));
							return '';
						}

						if (in_array($theme, Theme::getAllowedList())) {
							Theme::uninstall($theme);
							info(L10n::t('Theme %s disabled.', $theme));
						} elseif (Theme::install($theme)) {
							info(L10n::t('Theme %s successfully enabled.', $theme));
						} else {
							info(L10n::t('Theme %s failed to install.', $theme));
						}
					}

					break;

			}

			$a->internalRedirect('admin/themes');
		}

		$themes = [];
		$files = glob('view/theme/*');
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
				$is_supported = 1 - (intval(file_exists($file . '/unsupported')));
				$is_allowed = intval(in_array($theme, $allowed_themes));

				if ($is_allowed || $is_supported || Config::get('system', 'show_unsupported_themes')) {
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
			'$title'               => L10n::t('Administration'),
			'$page'                => L10n::t('Themes'),
			'$submit'              => L10n::t('Save Settings'),
			'$reload'              => L10n::t('Reload active themes'),
			'$baseurl'             => $a->getBaseURL(true),
			'$function'            => 'themes',
			'$addons'              => $addons,
			'$pcount'              => count($themes),
			'$noplugshint'         => L10n::t('No themes found on the system. They should be placed in %1$s', '<code>/view/themes</code>'),
			'$experimental'        => L10n::t('[Experimental]'),
			'$unsupported'         => L10n::t('[Unsupported]'),
			'$form_security_token' => parent::getFormSecurityToken('admin_themes'),
		]);
	}
}
