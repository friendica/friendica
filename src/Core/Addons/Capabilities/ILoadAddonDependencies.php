<?php

namespace Friendica\Core\Addons\Capabilities;

use Dice\Dice;

interface ILoadAddonDependencies
{
	public const ADDON_STATIC_DIR = 'static';
	public const ADDON_DEPENDENCY_FILE = 'dependencies.config.php';

	/**
	 * Takes the given Dice container (container for dependency injection)
	 * and adds the dependency-rules of all addons, we know
	 *
	 * @param Dice $dice The given Dice container
	 *
	 * @return Dice The given container including the dependency-rules of all addons
	 */
	public function withAddonDependencies(Dice $dice): Dice;
}
