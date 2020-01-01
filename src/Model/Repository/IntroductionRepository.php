<?php

namespace Friendica\Model\Repository;

use Friendica\Model\Entity\Introduction;
use Friendica\Model\Repository\Traits\UniqueIdSelectableTrait;

class IntroductionRepository extends BaseRepository
{
	use UniqueIdSelectableTrait;

	static $table = 'intro';
	/** @var Introduction */
	static $entity = Introduction::class;

	/**
	 * @param array $conditions
	 *
	 * @return Introduction[]
	 * @throws \Exception
	 */
	public function select(array $conditions)
	{
		return parent::select($conditions);
	}

	/**
	 * @param array $data
	 *
	 * @return Introduction
	 */
	protected function createEntity(array $data)
	{
		return self::$entity::create($data);
	}
}
