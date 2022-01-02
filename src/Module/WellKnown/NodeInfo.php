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

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;

/**
 * Standardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		self::printWellKnown();
	}

	/**
	 * Prints the well-known nodeinfo redirect
	 *
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	private static function printWellKnown()
	{
		$nodeinfo = [
			'links' => [
				['rel'  => 'http://nodeinfo.diaspora.software/ns/schema/1.0',
				'href' => DI::baseUrl()->get() . '/nodeinfo/1.0'],
				['rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
				'href' => DI::baseUrl()->get() . '/nodeinfo/2.0'],
			]
		];

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		exit;
	}
}
