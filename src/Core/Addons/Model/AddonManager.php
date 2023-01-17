<?php

namespace Friendica\Core\Addons\Model;

use Friendica\Core\Addons\Capabilities\ILoadAddonHooks;
use Friendica\Core\Addons\Capabilities\IManageAddons;
use Friendica\Core\Hooks\Capabilities\ICanManageInstances;
use Friendica\Util\Strings;

class AddonManager implements IManageAddons
{
	/** @var ILoadAddonHooks */
	protected $hookManager;

	public function load(): self
	{

	}

	public function loadToInstances(ICanManageInstances $instanceManager)
	{
		$this->hookManager->addStrategies($instanceManager);
		$this->hookManager->addDecorators($instanceManager);
	}

	public function install(string $addon): bool
	{
		$addonName = Strings::sanitizeFilePathItem($addon);

		$addon_file_path = 'addon/' . $addon . '/' . $addon . '.php';
		if (file_exists($addon_file_path)) {
			return $this->installLegacy($addonName);
		}
	}

	protected function installLegacy(string $addonName): bool
	{

	}
}
