<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license   GNU AGPL version 3 or any later version
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

namespace Friendica\Module\Api\Friendica;

use Friendica\DI;
use Friendica\Module\BaseApi;
require_once __DIR__ . '/../../../../include/api.php';

/**
 * api/friendica
 *
 * @package Friendica\Module\Api\Friendica
 */
class Index extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);		
	}

	protected function delete(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
	}

	protected function rawContent(array $request = [])
	{
		echo api_call(DI::args()->getCommand(), $this->parameters['extension'] ?? 'json');
		exit();
	}
}
