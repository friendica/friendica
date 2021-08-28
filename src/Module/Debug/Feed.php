<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Model;
use Friendica\Protocol;

/**
 * Tests a given feed of a contact
 */
class Feed extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			notice(DI::l10n()->t('You must be logged in to use this module'));
			DI::baseUrl()->redirect();
		}
	}

	public static function content(array $parameters = [])
	{
		$result = [];
		if (!empty($_REQUEST['url'])) {
			$url = $_REQUEST['url'];

			$contact = Model\Contact::getByURLForUser($url, local_user(), null);

			$xml = DI::httpClient()->fetch($contact['poll']);

			$import_result = Protocol\Feed::import($xml);

			$result = [
				'input' => $xml,
				'output' => var_export($import_result, true),
			];
		}

		$tpl = Renderer::getMarkupTemplate('feedtest.tpl');
		return Renderer::replaceMacros($tpl, [
			'$url'    => ['url', DI::l10n()->t('Source URL'), $_REQUEST['url'] ?? '', ''],
			'$result' => $result
		]);
	}
}
