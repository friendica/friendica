<?php

namespace Friendica\Model\Repository\Traits;

use Friendica\Database\Database;
use Friendica\Model\Entity\BaseEntity;

trait UniqueIdSelectableTrait
{
	/** @var Database */
	protected $dba;

	/**
	 * @param int $id
	 *
	 * @return BaseEntity
	 * @throws \Exception
	 */
	public function getById(int $id)
	{
		$selected = $this->dba->selectToArray(static::$table, [], ['id' => $id]);
		return $this->createEntity($selected);
	}
}
