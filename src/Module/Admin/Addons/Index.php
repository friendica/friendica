<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Addons;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

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

		$addonHelper = DI::addonHelper();

		// reload active themes
		if (!empty($_GET['action'])) {
			self::checkFormSecurityTokenRedirectOnError('/admin/addons', 'admin_addons', 't');

			switch ($_GET['action']) {
				case 'reload':
					$addonHelper->reloadAddons();
					DI::sysmsg()->addInfo(DI::l10n()->t('Addons reloaded'));
					break;

				case 'toggle':
					$addon = $_GET['addon'] ?? '';

					if ($addonHelper->isAddonEnabled($addon)) {
						$addonHelper->uninstallAddon($addon);
						DI::sysmsg()->addInfo(DI::l10n()->t('Addon %s disabled.', $addon));
					} elseif ($addonHelper->installAddon($addon)) {
						DI::sysmsg()->addInfo(DI::l10n()->t('Addon %s enabled.', $addon));
					} else {
						DI::sysmsg()->addNotice(DI::l10n()->t('Addon %s failed to install.', $addon));
					}

					break;

			}

			DI::baseUrl()->redirect('admin/addons');
		}

		$addons = [];

		foreach ($addonHelper->getAvailableAddons() as $addonId) {
			$addonInfo = $addonHelper->getAddonInfo($addonId);

			$info = [
				'name'        => $addonInfo->getName(),
				'description' => $addonInfo->getDescription(),
				'version'     => $addonInfo->getVersion(),
			];

			$addons[] = [
				$addonId,
				($addonHelper->isAddonEnabled($addonId) ? 'on' : 'off'),
				$info,
			];
		}

		$t = Renderer::getMarkupTemplate('admin/addons/index.tpl');
		return Renderer::replaceMacros($t, [
			'$title'               => DI::l10n()->t('Administration'),
			'$page'                => DI::l10n()->t('Addons'),
			'$submit'              => DI::l10n()->t('Save Settings'),
			'$reload'              => DI::l10n()->t('Reload active addons'),
			'$function'            => 'addons',
			'$addons'              => $addons,
			'$pcount'              => count($addons),
			'$noplugshint'         => DI::l10n()->t('There are currently no addons available on your node. You can find the official addon repository at %1$s.', 'https://git.friendi.ca/friendica/friendica-addons'),
			'$form_security_token' => self::getFormSecurityToken('admin_addons'),
		]);
	}
}
