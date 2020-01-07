<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Content as C;

/**
 * Registry for dynamic classes of the "Friendica\Content" namespace
 */
abstract class Content extends BaseRegistry
{
	/**
	 * @return C\Item
	 */
	public static function item()
	{
		return self::$dice->create(C\Item::class);
	}

	/**
	 * @return C\Text\BBCode\Video
	 */
	public static function bbCodeVideo()
	{
		return self::$dice->create(C\Text\BBCode\Video::class);
	}
}
