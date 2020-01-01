<?php

namespace Friendica\Model\Entity\Traits;

use Friendica\Network\HTTPException;

trait StorableTrait
{
	/**
	 * Array of keys, which are changed before they got saved (optimizing updating)
	 *
	 * @var array
	 */
	private $changed = [];

	/**
	 * True, if the current entity has changes
	 *
	 * @return boolean
	 */
	public function isChanged()
	{
		return count($this->changed) > 0;
	}

	public function getChanged()
	{
		$this->changed = array_unique($this->changed);
		return $this->changed;
	}

	public function isStored()
	{
		return $this->id ?? -1 > 0;
	}

	/** @return array */
	public function asArray()
	{
		return $this->data;
	}

	public function __set($name, $value)
	{
		if ($name === 'id') {
			throw new HTTPException\InternalServerErrorException("Setting 'id' manually is forbidden");
		}

		$this->changed[] = $name;
		$this->data[$name] = $value;

	}
}
