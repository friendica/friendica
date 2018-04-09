<?php
/**
 * @file src/Worker/ForkHook.php
 */

namespace Friendica\Worker;

use Friendica\Core\Addon;

Class ForkHook {
	public static function execute($name, $hook, $data) {
		global $a;

		Addon::callSingleHook($a, $name, $hook, $data);
	}
}
