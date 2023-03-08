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

namespace Friendica\Contact\LocalRelationship\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Contact\LocalRelationship\Entity;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;

class LocalRelationship extends BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\LocalRelationship
	{
		return new Entity\LocalRelationship(
			$row['uid'],
			$row['cid'],
			$row['blocked'] ?? false,
			$row['ignored'] ?? false,
			$row['collapsed'] ?? false,
			$row['hidden'] ?? false,
			$row['pending'] ?? false,
			$row['rel'] ?? Contact::NOTHING,
			$row['info'] ?? '',
			$row['notify_new_posts'] ?? false,
			$row['remote_self'] ?? false,
			$row['fetch_further_information'] ?? 0,
			$row['ffi_keyword_denylist'] ?? '',
			$row['subhub'] ?? false,
			$row['hub-verify'] ?? '',
			$row['protocol'] ?? Protocol::PHANTOM,
			$row['rating'] ?? null,
			$row['priority'] ?? 0
		);
	}
}
