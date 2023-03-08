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

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe as NetworkProbe;

/**
 * Fetch information (protocol endpoints and user information) about a given uri
 */
class Probe extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Only logged in users are permitted to perform a probing.'));
		}

		$addr = $_GET['addr'] ?? '';
		$res  = '';

		if (!empty($addr)) {
			$addr = NetworkProbe::cleanURI($addr);
			$res = NetworkProbe::uri($addr, '', 0);
			$res = print_r($res, true);
		}

		$tpl = Renderer::getMarkupTemplate('probe.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'  => DI::l10n()->t('Probe Diagnostic'),
			'$output' => DI::l10n()->t('Output'),
			'$submit' => DI::l10n()->t('Submit'),
			'$addr'   => ['addr',
				DI::l10n()->t('Lookup address'),
				$addr,
				'',
				DI::l10n()->t('Required')
			],
			'$res'    => $res,
		]);
	}
}
