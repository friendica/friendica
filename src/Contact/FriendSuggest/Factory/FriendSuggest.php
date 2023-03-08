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

namespace Friendica\Contact\FriendSuggest\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Contact\FriendSuggest\Entity;

class FriendSuggest extends BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\FriendSuggest
	{
		return new Entity\FriendSuggest(
			$row['uid'] ?? 0,
			$row['cid'] ?? 0,
			$row['name'] ?? '',
			$row['url'] ?? '',
			$row['request'] ?? '',
			$row['photo'] ?? '',
			$row['note'] ?? '',
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC')),
			$row['id'] ?? null
		);
	}

	public function createNew(
		int $uid,
		int $cid,
		string $name = '',
		string $url = '',
		string $request = '',
		string $photo = '',
		string $note = ''
	): Entity\FriendSuggest {
		return $this->createFromTableRow([
			'uid'     => $uid,
			'cid'     => $cid,
			'name'    => $name,
			'url'     => $url,
			'request' => $request,
			'photo'   => $photo,
			'note'    => $note,
		]);
	}

	public function createEmpty(int $id): Entity\FriendSuggest
	{
		return $this->createFromTableRow(['id' => $id]);
	}
}
