<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module\Admin;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class Tos extends BaseAdmin
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		parent::checkFormSecurityTokenRedirectOnError('/admin/tos', 'admin_tos');

		if (empty($_POST['page_tos'])) {
			return;
		}

		$displaytos = !empty($_POST['displaytos']);
		$displayprivstatement = !empty($_POST['displayprivstatement']);
		$tostext = (!empty($_POST['tostext']) ? strip_tags(trim($_POST['tostext'])) : '');

		DI::config()->set('system', 'tosdisplay', $displaytos);
		DI::config()->set('system', 'tosprivstatement', $displayprivstatement);
		DI::config()->set('system', 'tostext', $tostext);

		info(DI::l10n()->t('The Terms of Service settings have been updated.'));

		DI::baseUrl()->redirect('admin/tos');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$tos = new \Friendica\Module\Tos();
		$t = Renderer::getMarkupTemplate('admin/tos.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Terms of Service'),
			'$displaytos' => ['displaytos', DI::l10n()->t('Display Terms of Service'), DI::config()->get('system', 'tosdisplay'), DI::l10n()->t('Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.')],
			'$displayprivstatement' => ['displayprivstatement', DI::l10n()->t('Display Privacy Statement'), DI::config()->get('system', 'tosprivstatement'), DI::l10n()->t('Show some informations regarding the needed information to operate the node according e.g. to <a href="%s">EU-GDPR</a>.', 'https://en.wikipedia.org/wiki/General_Data_Protection_Regulation')],
			'$preview' => DI::l10n()->t('Privacy Statement Preview'),
			'$privtext' => $tos->privacy_complete,
			'$tostext' => ['tostext', DI::l10n()->t('The Terms of Service'), DI::config()->get('system', 'tostext'), DI::l10n()->t('Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.')],
			'$form_security_token' => parent::getFormSecurityToken('admin_tos'),
			'$submit' => DI::l10n()->t('Save Settings'),
		]);
	}
}
