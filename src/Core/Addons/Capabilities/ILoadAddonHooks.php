<?php

namespace Friendica\Core\Addons\Capabilities;

use Friendica\Core\Hooks\Capabilities\ICanManageInstances;

interface ILoadAddonHooks
{
	public const ADDON_STATIC_DIR      = 'static';
	public const ADDON_HOOKS_FILE      = 'hooks.config.php';
	public const ADDON_HOOK_STRATEGIES = 'strategies';
	public const ADDON_HOOK_DECORATORS = 'decorators';

	/**
	 * Loads all instance strategies of the active addons
	 *
	 * @param ICanManageInstances $instanceManager The given instance manager
	 *
	 * @return $this this instance for chain-calling
	 */
	public function loadStrategies(ICanManageInstances $instanceManager): self;

	/**
	 * Loads all decorators of the active addons
	 *
	 * @param ICanManageInstances $instanceManager The given instance manager
	 *
	 * @return $this this instance for chain-callig
	 */
	public function loadDecorators(ICanManageInstances $instanceManager): self;
}
