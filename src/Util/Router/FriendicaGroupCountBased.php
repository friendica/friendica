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

namespace Friendica\Util\Router;

use FastRoute\Dispatcher\GroupCountBased;

/**
 * Extends the Fast-Router collector for getting the possible HTTP method options for a given URI
 */
class FriendicaGroupCountBased extends GroupCountBased
{
	/**
	 * Returns all possible HTTP methods for a given URI
	 *
	 * @param $uri
	 *
	 * @return array
	 *
	 * @todo Distinguish between an invalid route and the asterisk (*) default route
	 */
	public function getOptions($uri): array
	{
		$varRouteData = $this->variableRouteData;

		// Find allowed methods for this URI by matching against all other HTTP methods as well
		$allowedMethods = [];

		foreach ($this->staticRouteMap as $method => $uriMap) {
			if (isset($uriMap[$uri])) {
				$allowedMethods[] = $method;
			}
		}

		foreach ($varRouteData as $method => $routeData) {
			$result = $this->dispatchVariableRoute($routeData, $uri);
			if ($result[0] === self::FOUND) {
				$allowedMethods[] = $method;
			}
		}

		return $allowedMethods;
	}
}
