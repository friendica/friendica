<?php
/**
 * @file src/Worker/ForkHook.php
 */

namespace Friendica\Worker;

use Friendica\Core\Addon;

Class ForkHook
{
	public static function execute($name, $hook, $data)
	{
		$a = \Friendica\BaseObject::getApp();

		Addon::callSingleHook($a, $name, $hook, $data);
	}
}
