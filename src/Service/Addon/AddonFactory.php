<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

use Friendica\Addon\AddonBootstrap;
use Psr\Log\LoggerInterface;

/**
 * Factory for all addons.
 */
final class AddonFactory implements AddonLoader
{
	private string $addonPath;

	private LoggerInterface $logger;

	/** @var Addon[] */
	private array $addons = [];

	public function __construct(string $addonPath, LoggerInterface $logger)
	{
		$this->addonPath = $addonPath;
		$this->logger    = $logger;
	}

	/**
	 * @return Addon[] Returns an array of Addon instances.
	 */
	public function getAddons(array $addonNames): array
	{
		foreach ($addonNames as $addonName => $addonDetails) {
			try {
				$this->addons[$addonName] = $this->bootstrapAddon($addonName);
			} catch (\Throwable $th) {
				// @TODO Here we can check if we have a Legacy addon and try to load it
				// throw $th;
			}
		}

		return $this->addons;
	}

	private function bootstrapAddon(string $addonName): Addon
	{
		$bootstrapFile = sprintf('%s/%s/bootstrap.php', $this->addonPath, $addonName);

		if (!file_exists($bootstrapFile)) {
			throw new \RuntimeException(sprintf('Bootstrap file for addon "%s" not found.', $addonName));
		}

		try {
			$bootstrap = require $bootstrapFile;
		} catch (\Throwable $th) {
			throw new \RuntimeException(sprintf('Something went wrong loading the Bootstrap file for addon "%s".', $addonName), $th->getCode(), $th);
		}

		if (!($bootstrap instanceof AddonBootstrap)) {
			throw new \RuntimeException(sprintf('Bootstrap file for addon "%s" MUST return an instance of AddonBootstrap.', $addonName));
		}

		$addon = new AddonProxy($addonName, $bootstrap);

		$this->logger->info(sprintf('Addon "%s" loaded.', $addonName));

		return $addon;
	}
}
