<?php

namespace Friendica\Factory\Mastodon;

use Friendica\Api\Entity\Mastodon\Relationship as RelationshipEntity;
use Friendica\Factory;

class Relationship extends Factory
{
	/**
	 * @param array $userContact Full contact row record with uid != 0
	 * @return RelationshipEntity
	 */
	public function createFromContact(array $userContact)
	{
		return new RelationshipEntity($userContact['id'], $userContact);
	}

	/**
	 * @param int $userContactId Contact row id with uid != 0
	 * @return RelationshipEntity
	 */
	public function createDefaultFromContactId(int $userContactId)
	{
		return new RelationshipEntity($userContactId);
	}
}
