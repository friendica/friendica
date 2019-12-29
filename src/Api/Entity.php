<?php

namespace Friendica\Api;

/**
 * The API entity classes are meant as data transfer objects. As such, their member should be protected and static factory
 * methods should be used to create instances. Then the JsonSerializable interface ensures the protected members will
 * be included in a JSON encode situation.
 */
abstract class Entity implements \JsonSerializable
{
	public function jsonSerialize()
	{
		return get_object_vars($this);
	}
}
