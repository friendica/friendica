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
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Shows the maintenance reason
 * or redirects to the alternate location
 */
class Maintenance extends BaseModule
{
	protected function content(array $request = []): string
	{
		$reason = DI::config()->get('system', 'maintenance_reason') ?? '';

		if ((substr(Strings::normaliseLink($reason), 0, 7) === 'http://') ||
			(substr(Strings::normaliseLink($reason), 0, 8) === 'https://')) {
			System::externalRedirect($reason, 307);
		}

		$exception = new HTTPException\ServiceUnavailableException($reason);

		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . DI::l10n()->t('System down for maintenance'));

		$tpl = Renderer::getMarkupTemplate('exception.tpl');

		return Renderer::replaceMacros($tpl, [
			'$title' => DI::l10n()->t('System down for maintenance'),
			'$message' => DI::l10n()->t('This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'),
			'$thrown' => $reason,
			'$stack_trace' => '',
			'$trace' => '',
		]);
	}
}
