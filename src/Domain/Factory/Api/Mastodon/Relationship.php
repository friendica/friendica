<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Domain\Factory\Api\Mastodon;

use Friendica\Domain\Entity\Api\Mastodon\Relationship as MastodonRelationshipEntity;
use Friendica\Domain\BaseFactory;
use Friendica\Model\Contact;

class Relationship extends BaseFactory
{
	/**
	 * @param int $userContactId Contact row id with uid != 0
	 *
	 * @return MastodonRelationshipEntity
	 * @throws \Exception
	 */
	public function createFromContactId(int $userContactId)
	{
		return $this->createFromContact(Contact::getById($userContactId));
	}

	/**
	 * @param array $userContact Full contact row record with uid != 0
	 *
	 * @return MastodonRelationshipEntity
	 */
	public function createFromContact(array $userContact)
	{
		return new MastodonRelationshipEntity($userContact['id'], $userContact);
	}

	/**
	 * @param int $userContactId Contact row id with uid != 0
	 *
	 * @return MastodonRelationshipEntity
	 */
	public function createDefaultFromContactId(int $userContactId)
	{
		return new MastodonRelationshipEntity($userContactId);
	}
}
