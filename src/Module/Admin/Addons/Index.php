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

namespace Friendica\Module\Admin\Addons;

use Friendica\Core\Addon;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class Index extends BaseAdmin
{
	protected function content(array $request = []): string
	{
		parent::content();

		// reload active themes
		if (!empty($_GET['action'])) {
			self::checkFormSecurityTokenRedirectOnError('/admin/addons', 'admin_addons', 't');

			switch ($_GET['action']) {
				case 'reload':
					Addon::reload();
					DI::sysmsg()->addInfo(DI::l10n()->t('Addons reloaded'));
					break;

				case 'toggle' :
					$addon = $_GET['addon'] ?? '';
					if (Addon::isEnabled($addon)) {
						Addon::uninstall($addon);
						DI::sysmsg()->addInfo(DI::l10n()->t('Addon %s disabled.', $addon));
					} elseif (Addon::install($addon)) {
						DI::sysmsg()->addInfo(DI::l10n()->t('Addon %s enabled.', $addon));
					} else {
						DI::sysmsg()->addNotice(DI::l10n()->t('Addon %s failed to install.', $addon));
					}

					break;

			}

			DI::baseUrl()->redirect('admin/addons');
		}

		$addons = Addon::getAvailableList();

		$t = Renderer::getMarkupTemplate('admin/addons/index.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Addons'),
			'$submit' => DI::l10n()->t('Save Settings'),
			'$reload' => DI::l10n()->t('Reload active addons'),
			'$function' => 'addons',
			'$addons' => $addons,
			'$pcount' => count($addons),
			'$noplugshint' => DI::l10n()->t('There are currently no addons available on your node. You can find the official addon repository at %1$s and might find other interesting addons in the open addon registry at %2$s', 'https://github.com/friendica/friendica-addons', 'http://addons.friendi.ca'),
			'$form_security_token' => self::getFormSecurityToken('admin_addons'),
		]);
	}
}
