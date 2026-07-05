<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

use Friendica\Addon\AddonBootstrap;
use Friendica\Addon\DependencyProvider;
use Friendica\Addon\Event\AddonStartEvent;
use Friendica\Addon\InstallableAddon;
use Psr\Container\ContainerInterface;

/**
 * Proxy object for an addon.
 */
final class AddonProxy implements Addon
{
	private string $id;

	private AddonBootstrap $bootstrap;

	private bool $isInit = false;

	public function __construct(string $id, AddonBootstrap $bootstrap)
	{
		$this->id        = $id;
		$this->bootstrap = $bootstrap;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getRequiredDependencies(): array
	{
		return $this->bootstrap->getRequiredDependencies();
	}

	public function getSubscribedEvents(): array
	{
		$events = [];

		foreach ($this->bootstrap->getSubscribedEvents() as $eventName => $methodName) {
			$events[] = [$eventName, [$this->bootstrap, $methodName]];
		}

		return $events;
	}

	public function getProvidedDependencyRules(): array
	{
		if ($this->bootstrap instanceof DependencyProvider) {
			return $this->bootstrap->provideDependencyRules();
		}

		return [];
	}

	public function initAddon(ContainerInterface $container): void
	{
		if ($this->isInit) {
			return;
		}

		$this->isInit = true;

		$event = new AddonStartEvent($container);

		$this->bootstrap->initAddon($event);
	}

	public function installAddon(): void
	{
		if ($this->bootstrap instanceof InstallableAddon) {
			$this->bootstrap->install();
		}
	}

	public function uninstallAddon(): void
	{
		if ($this->bootstrap instanceof InstallableAddon) {
			$this->bootstrap->uninstall();
		}
	}
}
