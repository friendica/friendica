<?php

namespace Friendica\Api;

/**
 * The API entity classes are meant as data transfer objects. As such, their member should be protected.
 * Then the JsonSerializable interface ensures the protected members will be included in a JSON encode situation.
 *
 * Constructors are supposed to take as arguments the Friendica dependencies/model/collection/data it needs to
 * populate the class members.
 */
abstract class Entity implements \JsonSerializable
{
	public function jsonSerialize()
	{
		return get_object_vars($this);
	}
}
