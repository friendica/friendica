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

namespace Friendica\Moderation\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Moderation\Entity;

class Report extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @param array $row        `report` table row
	 * @param array $postUriIds List of post URI ids from the `report-post` table
	 * @return Entity\Report
	 * @throws \Exception
	 */
	public function createFromTableRow(array $row, array $postUriIds = []): Entity\Report
	{
		return new Entity\Report(
			$row['reporter-id'],
			$row['cid'],
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC')),
			$row['comment'],
			$row['category'],
			$row['rules'],
			$row['forward'],
			$postUriIds,
			$row['uid'],
			$row['id'],
		);
	}

	/**
	 * Creates a Report entity from a Mastodon API /reports request
	 *
	 * @see \Friendica\Module\Api\Mastodon\Reports::post()
	 *
	 * @param int    $uid
	 * @param int    $reporterId
	 * @param int    $cid
	 * @param string $comment
	 * @param bool   $forward
	 * @param array  $postUriIds
	 * @return Entity\Report
	 * @throws \Exception
	 */
	public function createFromReportsRequest(int $reporterId, int $cid, string $comment = '', string $category = null, string $rules = '', bool $forward = false, array $postUriIds = [], int $uid = null): Entity\Report
	{
		return new Entity\Report(
			$reporterId,
			$cid,
			new \DateTime('now', new \DateTimeZone('UTC')),
			$comment,
			$category,
			$rules,
			$forward,
			$postUriIds,
			$uid,
		);
	}
}
