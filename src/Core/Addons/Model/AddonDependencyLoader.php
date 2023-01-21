<?php

namespace Friendica\Core\Addons\Model;

use Dice\Dice;
use Friendica\Core\Addons\Capabilities\ILoadAddonDependencies;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Util\Strings;

class AddonDependencyLoader implements ILoadAddonDependencies
{
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;
	}

	/** {@inheritDoc} */
	public function withAddonDependencies(Dice $dice): Dice
	{
		$rules = [];

		$addons = array_filter($this->config->get('addons') ?? []);

		foreach ($addons as $name => $data) {
			$addonName     = Strings::sanitizeFilePathItem(trim($name));
			$addonFilePath = sprintf('addon/%s/%s/%s', $addonName, static::ADDON_STATIC_DIR, static::ADDON_DEPENDENCY_FILE);
			if (!file_exists($addonFilePath)) {
				continue;
			}

			$rules = array_merge($rules, @include_once($addonFilePath));
		}

		return $dice->addRules($rules);
	}
}
