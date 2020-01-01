<?php


namespace Friendica\Model\Repository;

use Friendica\Database\Database;
use Friendica\Model\Entity\BaseEntity;
use Friendica\Model\Entity\IStorable;

abstract class BaseRepository
{
	/** @var string */
	protected static $table;
	/** @var BaseEntity */
	protected static $entity;

	/** @var Database */
	protected $dba;

	/**
	 * BaseRepository constructor.
	 *
	 * @param Database $dba
	 */
	public function __construct(Database $dba)
	{
		$this->dba = $dba;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	abstract protected function createEntity(array $data);

	/**
	 * @param array $conditions
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function select(array $conditions)
	{
		$prototype = null;

		$selected = $this->dba->delete(static::$table, [], $conditions);

		$return = [];

		while ($data = $this->dba->fetch($selected)) {
			if ($prototype === null) {
				$prototype = new static::$entity($data);
				$return[] = $prototype;
			} else {
				$return[] = static::$entity::createFromPrototype($prototype, $data);
			}
		}

		return $data;
	}

	/**
	 * @param IStorable[] $entities
	 *
	 * @return boolean True, if deleted
	 */
	public function delete(IStorable ...$entities)
	{
		try {
			if (!$this->dba->transaction()) {
				return false;
			}

			foreach ($entities as $entity) {
				if ($entity->isStored()) {
					$this->dba->delete(static::$table, ['id' => $entity->id]);
				}
			}

			return $this->dba->commit();
		} catch (\Exception $e) {
			$this->dba->rollback();
			return false;
		}
	}

	/**
	 * @param IStorable[] $entities
	 *
	 * @return boolean True if updated successfully
	 */
	public function save(IStorable ...$entities)
	{
		try {
			if (!$this->dba->transaction()) {
				return false;
			}

			foreach ($entities as $entity) {
				if ($entity->isChanged() && $entity->isStored()) {
					$this->dba->update(static::$table, $entity->getChanged(), ['id' => $entity->id]);
				} elseif ($entity->isChanged() && !$entity->isStored()) {
					$this->dba->insert(static::$table, $entity->asArray());
					$entity->id = $this->dba->lastInsertId();
				}
			}

			return $this->dba->commit();
		} catch (\Exception $e) {
			$this->dba->rollback();
			return false;
		}
	}
}
