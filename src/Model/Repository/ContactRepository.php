<?php

namespace Friendica\Model\Repository;

use Friendica\Model\Entity\Contact;
use Friendica\Model\Repository\Traits\UniqueIdSelectableTrait;

class ContactRepository extends BaseRepository
{
	use UniqueIdSelectableTrait;

	static $table = 'contact';
	/** @var Contact */
	static $entity = Contact::class;

	/**
	 * @param array $conditions
	 *
	 * @return Contact[]
	 * @throws \Exception
	 */
	public function select(array $conditions)
	{
		return parent::select($conditions);
	}

	/**
	 * @param array $data
	 *
	 * @return Contact
	 */
	protected function createEntity(array $data)
	{
		return static::$entity::create($data);
	}
}
