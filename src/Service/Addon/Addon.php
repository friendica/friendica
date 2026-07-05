<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

use Psr\Container\ContainerInterface;

/**
 * Interface to communicate with an addon.
 */
interface Addon
{
	public function getId(): string;

	public function getRequiredDependencies(): array;

	public function getSubscribedEvents(): array;

	public function getProvidedDependencyRules(): array;

	public function initAddon(ContainerInterface $container): void;

	public function installAddon(): void;

	public function uninstallAddon(): void;
}
