<?php

namespace Friendica\Model\Entity\Traits;

use Friendica\Network\HTTPException;

trait DataTransferObjectTrait
{
	public function __set($name, $value)
	{
			throw new HTTPException\InternalServerErrorException("Setting values is forbidden for DTOs");
	}

	abstract public static function create(array $data);
}
