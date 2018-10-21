<?php
/**
 * @file src/Worker/ForkHook.php
 */

namespace Friendica\Worker;

use Friendica\Core\Hook;

Class ForkHook
{
	public static function execute($name, $hook, $data)
	{
		$a = \Friendica\BaseObject::getApp();

		Hook::callSingle($a, $name, $hook, $data);
	}
}
