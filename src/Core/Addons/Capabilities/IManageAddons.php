<?php

namespace Friendica\Core\Addons\Capabilities;

use Friendica\Core\Hooks\Capabilities\ICanManageInstances;

interface IManageAddons
{
	public const ADDON_DIRECTORY = 'addon';

	public function loadToInstances(ICanManageInstances $instanceManager);
}
