<?php

namespace Friendica\Core\Addons\Model;

use Friendica\Core\Addons\Capabilities\ILoadAddonHooks;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capabilities\ICanManageInstances;
use Friendica\Util\Strings;

class AddonHookLoader implements ILoadAddonHooks
{
	/** @var IManageConfigValues */
	protected $config;
	/** @var array */
	protected $hooks;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;
	}

	public function load(): self
	{
		$hooks = [];

		$addons = array_filter($this->config->get('addons') ?? []);

		foreach ($addons as $name => $data) {
			$addonName     = Strings::sanitizeFilePathItem(trim($name));

			// save all given hooks
			$hookFilePath = sprintf('addon/%s/%s/%s', $addonName, static::ADDON_STATIC_DIR, static::ADDON_HOOKS_FILE);
			if (file_exists($hookFilePath)) {
				$hooks = array_replace_recursive($hooks, @include_once($hookFilePath));
			}

			// load the composer specific autoload.php
			/// @todo just load it in case a corresponding hook needs it (performance)
			$autoloadFile = sprintf('addon/%s/vendor/autoload.php', $addonName);
			if (file_exists($autoloadFile)) {
				require_once($autoloadFile);
			}
		}

		$this->hooks = $hooks;

		return $this;
	}

	/** {@inheritDoc} */
	public function loadStrategies(ICanManageInstances $instanceManager): ILoadAddonHooks
	{
		foreach ($this->hooks[static::ADDON_HOOK_STRATEGIES] as $class => $data) {
			foreach ($data as $name => [$instance, $arguments]) {
				$instanceManager->registerStrategy($class, $name, $instance, $arguments ?? []);
			}
		}

		return $this;
	}

	/** {@inheritDoc} */
	public function loadDecorators(ICanManageInstances $instanceManager): ILoadAddonHooks
	{
		foreach ($this->hooks[static::ADDON_HOOK_DECORATORS] as $class => $data) {
			foreach ($data as [$instance, $arguments]) {
				$instanceManager->registerDecorator($class, $instance, $arguments ?? []);
			}
		}

		return $this;
	}
}
