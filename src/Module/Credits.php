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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Show a credits page for all the developers who helped with the project
 * (only contributors to the git repositories for friendica core and the
 * addons repository will be listed though ATM)
 */
class Credits extends BaseModule
{
	protected function content(array $request = []): string
	{
		/* fill the page with credits */
		$credits_string = file_get_contents('CREDITS.txt');

		$names = explode("\n", $credits_string);

		$tpl = Renderer::getMarkupTemplate('credits.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'  => DI::l10n()->t('Credits'),
			'$thanks' => DI::l10n()->t('Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'),
			'$names'  => $names,
		]);
	}
}
