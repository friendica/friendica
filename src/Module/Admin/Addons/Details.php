<?php

namespace Friendica\Module\Admin\Addons;

use Friendica\Content\Text\Markdown;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
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
			$addon = $a->argv[2];
			$addon = Strings::sanitizeFilePathItem($addon);
			if (is_file('addon/' . $addon . '/' . $addon . '.php')) {
				include_once 'addon/' . $addon . '/' . $addon . '.php';
				if (function_exists($addon . '_addon_admin_post')) {
					$func = $addon . '_addon_admin_post';
					$func($a);
				}

				DI::baseUrl()->redirect('admin/addons/' . $addon);
			}
		}

		DI::baseUrl()->redirect('admin/addons');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$addons_admin = Addon::getAdminList();

		if ($a->argc > 2) {
			// @TODO: Replace with parameter from router
			$addon = $a->argv[2];
			$addon = Strings::sanitizeFilePathItem($addon);
			if (!is_file("addon/$addon/$addon.php")) {
				notice(L10n::t('Addon not found.'));
				Addon::uninstall($addon);
				DI::baseUrl()->redirect('admin/addons');
			}

			if (($_GET['action'] ?? '') == 'toggle') {
				parent::checkFormSecurityTokenRedirectOnError('/admin/addons', 'admin_themes', 't');

				// Toggle addon status
				if (Addon::isEnabled($addon)) {
					Addon::uninstall($addon);
					info(L10n::t('Addon %s disabled.', $addon));
				} else {
					Addon::install($addon);
					info(L10n::t('Addon %s enabled.', $addon));
				}

				Addon::saveEnabledList();

				DI::baseUrl()->redirect('admin/addons/' . $addon);
			}

			// display addon details
			if (Addon::isEnabled($addon)) {
				$status = 'on';
				$action = L10n::t('Disable');
			} else {
				$status = 'off';
				$action = L10n::t('Enable');
			}

			$readme = null;
			if (is_file("addon/$addon/README.md")) {
				$readme = Markdown::convert(file_get_contents("addon/$addon/README.md"), false);
			} elseif (is_file("addon/$addon/README")) {
				$readme = '<pre>' . file_get_contents("addon/$addon/README") . '</pre>';
			}

			$admin_form = '';
			if (array_key_exists($addon, $addons_admin)) {
				require_once "addon/$addon/$addon.php";
				$func = $addon . '_addon_admin';
				$func($a, $admin_form);
			}

			$t = Renderer::getMarkupTemplate('admin/addons/details.tpl');

			return Renderer::replaceMacros($t, [
				'$title' => L10n::t('Administration'),
				'$page' => L10n::t('Addons'),
				'$toggle' => L10n::t('Toggle'),
				'$settings' => L10n::t('Settings'),
				'$baseurl' => $a->getBaseURL(true),

				'$addon' => $addon,
				'$status' => $status,
				'$action' => $action,
				'$info' => Addon::getInfo($addon),
				'$str_author' => L10n::t('Author: '),
				'$str_maintainer' => L10n::t('Maintainer: '),

				'$admin_form' => $admin_form,
				'$function' => 'addons',
				'$screenshot' => '',
				'$readme' => $readme,

				'$form_security_token' => parent::getFormSecurityToken('admin_themes'),
			]);
		}

		DI::baseUrl()->redirect('admin/addons');
	}
}
