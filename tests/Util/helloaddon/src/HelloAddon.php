<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Name: Hello Addon
 * Description: For testing purpose only
 * Version: 1.0
 * Author: Artur Weigandt <dont-mail-me@example.com>
 */

declare(strict_types=1);

namespace FriendicaAddons\HelloAddon;

use Friendica\Addon\AddonBootstrap;
use Friendica\Addon\DependencyProvider;
use Friendica\Addon\Event\AddonStartEvent;
use Friendica\Addon\InstallableAddon;
use Friendica\Event\HtmlFilterEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HelloAddon implements AddonBootstrap, DependencyProvider, InstallableAddon
{
	private LoggerInterface $logger;

	/**
	 * Returns an array of services that are required by this addon.
	 *
	 * The array should contain FQCN of the required services.
	 *
	 * The dependencies will be passed as a PSR-11 Container to the initAddon() method via AddonStartEvent::getContainer().
	 */
	public function getRequiredDependencies(): array
	{
		return [
			LoggerInterface::class,
		];
	}

	/**
	 * Return an array of events to subscribe to.
	 *
	 * The keys MUST be the event name.
	 * The values MUST be the method of the implementing class to call.
	 *
	 * Example:
	 *
	 * ```php
	 * return [Event::NAME => 'onEvent'];
	 * ```
	 *
	 * @return array<string, string>
	 */
	public function getSubscribedEvents(): array
	{
		return [
			HtmlFilterEvent::PAGE_END => 'onPageEnd',
		];
	}

	/**
	 * Returns an array of Dice rules.
	 */
	public function provideDependencyRules(): array
	{
		// replaces require($path_to_dependencies_file);
		return [
			LoggerInterface::class => [
				'instanceOf' => NullLogger::class,
				'call'       => null,
			],
		];
	}

	/**
	 * Returns an array of strategy rules.
	 */
	public function provideStrategyRules(): array
	{
		// replaces require($path_to_strategies_file);
		return [];
	}

	public function initAddon(AddonStartEvent $event): void
	{
		$container = $event->getContainer();

		$this->logger = $container->get(LoggerInterface::class);

		$this->logger->info('Hello from HelloAddon');
	}

	/**
	 * Runs after AddonBootstrap::initAddon()
	 */
	public function install(): void
	{
		$this->logger->info('HelloAddon installed');
	}

	/**
	 * Runs after AddonBootstrap::initAddon()
	 */
	public function uninstall(): void
	{
		$this->logger->info('HelloAddon uninstalled');
	}

	public function onPageEnd(HtmlFilterEvent $event): void
	{
		$event->setHtml($event->getHtml() . '<p>Hello, World!</p>');
	}
}
