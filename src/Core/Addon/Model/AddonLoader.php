<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Addon\Model;

use Friendica\Core\Addon\Capability\ICanLoadAddons;
use Friendica\Core\Addon\Exception\AddonInvalidConfigFileException;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Factory\LoggerFactory;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * @deprecated 2025.02 Use implementation of `\Friendica\Core\Addon\AddonHelper` instead.
 */
class AddonLoader implements ICanLoadAddons
{
	const STATIC_PATH = 'static';
	/** @var string */
	protected $basePath;
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(string $basePath, IManageConfigValues $config)
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use implementation of `Friendica\Core\Addon\AddonHelper` instead.', E_USER_DEPRECATED);

		$this->basePath = $basePath;
		$this->config   = $config;
	}

	/**
	 * @deprecated 2025.02 Use `\Friendica\Core\Addon\AddonHelper::getAddonDependencyConfig()` instead.
	 */
	public function getActiveAddonConfig(string $configName): array
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use `\Friendica\Core\Addon\AddonHelper::getAddonDependencyConfig()` instead.', E_USER_DEPRECATED);

		$addons       = array_keys(array_filter($this->config->get('addons') ?? []));
		$returnConfig = [];

		foreach ($addons as $addon) {
			$addonName = Strings::sanitizeFilePathItem(trim($addon));

			$configFile = $this->basePath . '/addon/' . $addonName . '/' . static::STATIC_PATH . '/' . $configName . '.config.php';

			if (!file_exists($configFile)) {
				// Addon unmodified, skipping
				continue;
			}

			$config = include $configFile;

			if (!is_array($config)) {
				throw new AddonInvalidConfigFileException('Error loading config file ' . $configFile);
			}

			if ($configName === 'strategies') {
				foreach ($config as $classname => $rule) {
					if ($classname === LoggerInterface::class) {
						@trigger_error(sprintf(
							'Providing a strategy for `%s` is deprecated since 2025.02 and will stop working in 5 months, please provide an implementation for `%s` via `dependency.config.php` and remove the `strategies.config.php` file in the `%s` addon.',
							$classname,
							LoggerFactory::class,
							$addonName,
						), \E_USER_DEPRECATED);
					} else {
						@trigger_error(sprintf(
							'Providing strategies for `%s` via addons is deprecated since 2025.02 and will stop working in 5 months, please stop using this and remove the `strategies.config.php` file in the `%s` addon.',
							$classname,
							$addonName,
						), \E_USER_DEPRECATED);
					}
				}
			}

			$returnConfig = array_merge_recursive($returnConfig, $config);
		}

		return $returnConfig;
	}
}
