<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

use Friendica\Core\Container;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Subset of the Container for an addon.
 */
final class AddonContainer implements ContainerInterface
{
	public static function fromContainer(Container $container, array $allowedServices): self
	{
		return new self($container, $allowedServices);
	}

	private Container $container;

	private array $allowedServices;

	private function __construct(Container $container, array $allowedServices)
	{
		$this->container       = $container;
		$this->allowedServices = $allowedServices;
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws \Psr\Container\NotFoundExceptionInterface  No entry was found for **this** identifier.
	 * @throws \Psr\Container\ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return mixed Entry.
	 */
	public function get(string $id)
	{
		if ($this->has($id)) {
			return $this->container->create($id);
		}

		$message = sprintf(
			'No entry was found for "%s"',
			$id,
		);

		throw new class ($message) extends \RuntimeException implements NotFoundExceptionInterface {};
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
	public function has(string $id): bool
	{
		return in_array($id, $this->allowedServices);
	}
}
