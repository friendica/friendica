<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

use Psr\Container\ContainerInterface;

/**
 * Proxy object for a legacy addon.
 */
final class LegacyAddonProxy implements Addon
{
	private string $id;

	private string $path;

	private bool $isInit = false;

	public function __construct(string $id, string $path)
	{
		$this->id   = $id;
		$this->path = $path;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getRequiredDependencies(): array
	{
		return [];
	}

	public function getSubscribedEvents(): array
	{
		return [];
	}

	public function getProvidedDependencyRules(): array
	{
		$fileName = $this->path . '/' . $this->id . '/static/dependencies.config.php';

		if (is_file($fileName)) {
			return include_once($fileName);
		}

		return [];
	}

	public function initAddon(ContainerInterface $container): void
	{
		if ($this->isInit) {
			return;
		}

		$this->isInit = true;

		include_once($this->path . '/' . $this->id . '/' . $this->id . '.php');
	}

	public function installAddon(): void
	{
		if (function_exists($this->id . '_install')) {
			call_user_func($this->id . '_install');
		}
	}

	public function uninstallAddon(): void
	{
		if (function_exists($this->id . '_uninstall')) {
			call_user_func($this->id . '_uninstall');
		}
	}
}
