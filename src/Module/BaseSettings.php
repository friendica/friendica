<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\Renderer;
use Friendica\DI;

class BaseSettings extends BaseModule
{
	public static function createAside()
	{
		$tpl = Renderer::getMarkupTemplate('settings/head.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => DI::l10n()->t('everybody')
		]);

		$tabs = [];

		$tabs[] = [
			'label' => DI::l10n()->t('Account'),
			'url' => 'settings',
			'selected' => ((DI::args()->getArgc() == 1) && (DI::args()->getArgv() === 'settings') ? 'active' : ''),
			'accesskey' => 'o',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Two-factor authentication'),
			'url' => 'settings/2fa',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === '2fa') ? 'active' : ''),
			'accesskey' => 'o',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Profile'),
			'url' => 'settings/profile',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'profile') ? 'active' : ''),
			'accesskey' => 'p',
		];

		if (Feature::get()) {
			$tabs[] = [
				'label' => DI::l10n()->t('Additional features'),
				'url' => 'settings/features',
				'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'features') ? 'active' : ''),
				'accesskey' => 't',
			];
		}

		$tabs[] = [
			'label' => DI::l10n()->t('Display'),
			'url' => 'settings/display',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'display') ? 'active' : ''),
			'accesskey' => 'i',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Social Networks'),
			'url' => 'settings/connectors',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'connectors') ? 'active' : ''),
			'accesskey' => 'w',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Addons'),
			'url' => 'settings/addon',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'addon') ? 'active' : ''),
			'accesskey' => 'l',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Manage Accounts'),
			'url' => 'settings/delegation',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'delegation') ? 'active' : ''),
			'accesskey' => 'd',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Connected apps'),
			'url' => 'settings/oauth',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'oauth') ? 'active' : ''),
			'accesskey' => 'b',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Export personal data'),
			'url' => 'settings/userexport',
			'selected' => ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'userexport') ? 'active' : ''),
			'accesskey' => 'e',
		];

		$tabs[] = [
			'label' => DI::l10n()->t('Remove account'),
			'url' => 'removeme',
			'selected' => ((DI::args()->getArgc() == 1) && (DI::args()->getArgv() === 'removeme') ? 'active' : ''),
			'accesskey' => 'r',
		];


		$tabtpl = Renderer::getMarkupTemplate("generic_links_widget.tpl");
		DI::page()['aside'] = Renderer::replaceMacros($tabtpl, [
			'$title' => DI::l10n()->t('Settings'),
			'$class' => 'settings-widget',
			'$items' => $tabs,
		]);
	}

	protected function content(array $request = []): string
	{
		$a = DI::app();

		static::createAside();

		return '';
	}
}
