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

namespace Friendica\Federation\Collection;

use Friendica\Federation\Entity;

final class DeliveryQueueAggregates extends \Friendica\BaseCollection
{
	/**
	 * @param Entity\DeliveryQueueAggregate[] $entities
	 * @param int|null                        $totalCount
	 */
	public function __construct(array $entities = [], int $totalCount = null)
	{
		parent::__construct($entities, $totalCount);
	}

	/**
	 * @return Entity\DeliveryQueueAggregate
	 */
	public function current(): Entity\DeliveryQueueAggregate
	{
		return parent::current();
	}
}
