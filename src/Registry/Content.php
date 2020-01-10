<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Content as ContentNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Content" namespace
 */
abstract class Content extends BaseRegistry
{
	/**
	 * @return ContentNamespace\Item
	 */
	public static function item()
	{
		return self::$dice->create(ContentNamespace\Item::class);
	}

	/**
	 * @return ContentNamespace\Text\BBCode\Video
	 */
	public static function bbCodeVideo()
	{
		return self::$dice->create(ContentNamespace\Text\BBCode\Video::class);
	}
}
