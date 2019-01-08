<?php

namespace Friendica;

use Psr\Container\ContainerInterface;
use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container extends PimpleContainer implements ContainerInterface
{
	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return mixed Entry.
	 */
	public function get($id)
	{
		if (!$this->offsetExists($id)) {
			throw new \Exception();
		}

		return $this->offsetGet($id);
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has($id)
	{
		return $this->offsetExists($id);
	}

	/********************************************************************************
	 * Magic methods for convenience
	 *******************************************************************************/

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __isset($name)
	{
		return $this->has($name);
	}
}
