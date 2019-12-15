<?php
/**
 * @file src/Worker/ForkHook.php
 */

namespace Friendica\Worker;

use Friendica\Core\Hook;
use Friendica\DI;

Class ForkHook
{
	public static function execute($name, $hook, $data)
	{
		$a = DI::app();

		Hook::callSingle($a, $name, $hook, $data);
	}
}
