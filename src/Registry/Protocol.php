<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Protocol as P;

/**
 * Registry for dynamic classes of the "Friendica\Protocol" namespace
 */
abstract class Protocol extends BaseRegistry
{
	/**
	 * @return P\Activity
	 */
	public static function activity()
	{
		return self::$dice->create(P\Activity::class);
	}
}
