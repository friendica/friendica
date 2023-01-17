<?php

namespace Friendica\Core\Addons\Model;

use Friendica\Core\Addons\Capabilities\IManageAddons;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Model\InstanceManager;
use Friendica\Util\Strings;

class AddonManager implements IManageAddons
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
			$addonFilePath = sprintf('addon/%s/%s/%s', $addonName, static::ADDON_STATIC_DIR, static::ADDON_HOOKS_FILE);
			if (!file_exists($addonFilePath)) {
				continue;
			}

			$hooks = array_replace_recursive($hooks, @include_once($addonFilePath));

			$addonFilePath = sprintf('addon/%s/vendor/autoload.php', $addonName);
			if (!file_exists($addonFilePath)) {
				continue;
			}

			require_once($addonFilePath);
		}

		$this->hooks = $hooks;

		return $this;
	}

	public function loadStrategies(InstanceManager $instanceManager): IManageAddons
	{
		foreach ($this->hooks[static::ADDON_HOOK_STRATEGIES] as $class => $data) {
			foreach ($data as $name => [$instance, $arguments]) {
				$instanceManager->registerStrategy($class, $name, $instance, $arguments ?? []);
			}
		}

		return $this;
	}

	public function loadDecorators(InstanceManager $instanceManager): IManageAddons
	{
		return $this;
	}
}
