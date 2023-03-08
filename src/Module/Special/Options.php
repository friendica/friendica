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

namespace Friendica\Module\Special;

use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Module\Response;

/**
 * Returns the allowed HTTP methods based on the route information
 *
 * It's a special class which shouldn't be called directly
 *
 * @see Router::getModuleClass()
 */
class Options extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$allowedMethods = $this->parameters['AllowedMethods'] ?? Router::ALLOWED_METHODS;

		// @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
		$this->response->setHeader(implode(',', $allowedMethods), 'Allow');
		$this->response->setStatus(204);
		$this->response->setType(Response::TYPE_BLANK);
	}
}
