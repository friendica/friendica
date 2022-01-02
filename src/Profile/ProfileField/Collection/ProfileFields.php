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

namespace Friendica\Profile\ProfileField\Collection;

use Friendica\BaseCollection;
use Friendica\Profile\ProfileField\Entity;

class ProfileFields extends BaseCollection
{
	public function current(): Entity\ProfileField
	{
		return parent::current();
	}

	/**
	 * @param callable $callback
	 * @return ProfileFields
	 */
	public function map(callable $callback): ProfileFields
	{
		return parent::map($callback);
	}

	/**
	 * @param callable|null $callback
	 * @param int           $flag
	 * @return ProfileFields
	 */
	public function filter(callable $callback = null, int $flag = 0): ProfileFields
	{
		return parent::filter($callback, $flag);
	}
}
