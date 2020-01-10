<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Protocol as ProtocolNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Protocol" namespace
 */
abstract class Protocol extends BaseRegistry
{
	/**
	 * @return ProtocolNamespace\Activity
	 */
	public static function activity()
	{
		return self::$dice->create(ProtocolNamespace\Activity::class);
	}
}
