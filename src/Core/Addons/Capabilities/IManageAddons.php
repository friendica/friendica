<?php

namespace Friendica\Core\Addons\Capabilities;

use Friendica\Core\Hooks\Model\InstanceManager;

interface IManageAddons
{
	public const ADDON_STATIC_DIR      = 'static';
	public const ADDON_HOOKS_FILE      = 'hooks.config.php';
	public const ADDON_HOOK_STRATEGIES = 'strategies';
	public const ADDON_HOOK_DECORATORS = 'decorators';

	public function loadStrategies(InstanceManager $instanceManager): self;
	public function loadDecorators(InstanceManager $instanceManager): self;
}
