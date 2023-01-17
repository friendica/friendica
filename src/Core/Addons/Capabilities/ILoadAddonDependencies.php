<?php

namespace Friendica\Core\Addons\Capabilities;

use Dice\Dice;

interface ILoadAddonDependencies
{
	public const ADDON_STATIC_DIR = 'static';
	public const ADDON_DEPENDENCY_FILE = 'dependencies.config.php';

	public function withAddonDependencies(Dice $dice): Dice;
}
