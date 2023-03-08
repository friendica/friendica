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
use Friendica\Content;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;

/**
 * Prints the possible Smilies of this node
 */
class Smilies extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (!empty(DI::args()->getArgv()[1]) && (DI::args()->getArgv()[1] === "json")) {
			$smilies = Content\Smilies::getList();
			$results = [];
			for ($i = 0; $i < count($smilies['texts']); $i++) {
				$results[] = ['text' => $smilies['texts'][$i], 'icon' => $smilies['icons'][$i]];
			}
			System::jsonExit($results);
		}
	}

	protected function content(array $request = []): string
	{
		$smilies = Content\Smilies::getList();
		$count = count($smilies['texts'] ?? []);

		$tpl = Renderer::getMarkupTemplate('smilies.tpl');
		return Renderer::replaceMacros($tpl, [
			'$count'   => $count,
			'$smilies' => $smilies,
		]);
	}
}
