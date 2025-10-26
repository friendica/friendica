<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Hooks\Util;

use Friendica\Core\Addon\Exception\AddonInvalidConfigFileException;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Exceptions\HookConfigException;
use Friendica\Core\Logger\Factory\LoggerFactory;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Manage all strategies.config.php files
 */
class StrategiesFileManager
{
	/**
	 * The default hook-file-key of strategies
	 * -> it's an empty string to cover empty/missing config values
	 */
	const STRATEGY_DEFAULT_KEY = '';
	const STATIC_DIR           = 'static';
	const CONFIG_NAME          = 'strategies';

	private IManageConfigValues $configuration;
	protected array $config = [];
	/** @var string */
	protected $basePath;

	public function __construct(string $basePath, IManageConfigValues $configuration)
	{
		$this->basePath      = $basePath;
		$this->configuration = $configuration;
	}

	/**
	 * Loads all kinds of hooks and registers the corresponding instances
	 *
	 * @param ICanRegisterStrategies $instanceRegister The instance register
	 *
	 * @return void
	 */
	public function setupStrategies(ICanRegisterStrategies $instanceRegister)
	{
		foreach ($this->config as $interface => $strategy) {
			foreach ($strategy as $dependencyName => $names) {
				if (is_array($names)) {
					foreach ($names as $name) {
						$instanceRegister->registerStrategy($interface, $dependencyName, $name);
					}
				} else {
					$instanceRegister->registerStrategy($interface, $dependencyName, $names);
				}
			}
		}
	}

	/**
	 * Reloads all hook config files into the config cache for later usage
	 *
	 * Merges all hook configs from every addon - if present - as well
	 *
	 * @return void
	 */
	public function loadConfig()
	{
		// load core hook config
		$configFile = $this->basePath . '/' . static::STATIC_DIR . '/' . static::CONFIG_NAME . '.config.php';

		if (!file_exists($configFile)) {
			throw new HookConfigException(sprintf('config file %s does not exist.', $configFile));
		}

		$config = include $configFile;

		if (!is_array($config)) {
			throw new HookConfigException(sprintf('Error loading config file %s.', $configFile));
		}

		/**
		 * @deprecated 2025.07 Providing strategies via addons is deprecated and will be removed in 5 months.
		 */
		$this->config = array_merge_recursive($config, $this->getActiveAddonConfig());
	}

	private function getActiveAddonConfig(): array
	{
		$addons       = array_keys(array_filter($this->configuration->get('addons') ?? []));
		$returnConfig = [];

		foreach ($addons as $addon) {
			$addonName = Strings::sanitizeFilePathItem(trim($addon));

			$configFile = $this->basePath . '/addon/' . $addonName . '/' . static::STATIC_DIR . '/strategies.config.php';

			if (!file_exists($configFile)) {
				// Addon unmodified, skipping
				continue;
			}

			$config = include $configFile;

			if (!is_array($config)) {
				throw new AddonInvalidConfigFileException('Error loading config file ' . $configFile);
			}

			foreach ($config as $classname => $rule) {
				if ($classname === LoggerInterface::class) {
					@trigger_error(sprintf(
						'Providing a strategy for `%s` is deprecated since 2025.07 and will stop working in 5 months, please provide an implementation for `%s` via `dependency.config.php` and remove the `strategies.config.php` file in the `%s` addon.',
						$classname,
						LoggerFactory::class,
						$addonName,
					), \E_USER_DEPRECATED);
				} else {
					@trigger_error(sprintf(
						'Providing strategies for `%s` via addons is deprecated since 2025.07 and will stop working in 5 months, please stop using this and remove the `strategies.config.php` file in the `%s` addon.',
						$classname,
						$addonName,
					), \E_USER_DEPRECATED);
				}
			}

			$returnConfig = array_merge_recursive($returnConfig, $config);
		}

		return $returnConfig;
	}
}
