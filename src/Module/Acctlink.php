<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Network\Probe;

/**
 * Redirects to another URL based on the parameter 'addr'
 */
class Acctlink extends BaseModule
{
	public static function content()
	{
		$addr = defaults($_GET, 'addr', false);

		if ($addr) {
			$url = defaults(Probe::uri(trim($addr)), 'url', false);

			if ($url) {
				self::getApp()->redirect($url);
				exit();
			}
		}
	}
}
