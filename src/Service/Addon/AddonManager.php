<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

use Psr\Container\ContainerInterface;

/**
 * Manager for all addons.
 */
final class AddonManager
{
	private AddonLoader $addonFactory;

	/** @var Addon[] */
	private array $addons = [];

	public function __construct(AddonLoader $addonFactory)
	{
		$this->addonFactory = $addonFactory;
	}

	public function bootstrapAddons(array $addonNames): void
	{
		$this->addons = $this->addonFactory->getAddons($addonNames);
	}

	public function getRequiredDependencies(): array
	{
		$dependencies = [];

		foreach ($this->addons as $addon) {
			// @TODO Here we can filter or deny dependencies from addons
			$dependencies[$addon->getId()] = $addon->getRequiredDependencies();
		}

		return $dependencies;
	}

	public function getProvidedDependencyRules(): array
	{
		$dependencyRules = [];

		foreach ($this->addons as $addon) {
			// @TODO At this point we can handle duplicate rules and handle possible conflicts
			$dependencyRules = array_merge($dependencyRules, $addon->getProvidedDependencyRules());
		}

		return $dependencyRules;
	}

	public function getAllSubscribedEvents(): array
	{
		$events = [];

		foreach ($this->addons as $addon) {
			$events = array_merge($events, $addon->getSubscribedEvents());
		}

		return $events;
	}

	/**
	 * @param ContainerInterface[] $containers
	 */
	public function initAddons(array $containers): void
	{
		foreach ($this->addons as $addon) {
			$container = $containers[$addon->getId()] ?? null;

			if ($container === null) {
				throw new \RuntimeException(sprintf('Container for addon "%s" is missing.', $addon->getId()));
			}

			$addon->initAddon($container);
		}
	}
}
