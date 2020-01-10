<?php


namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Factory as F;

/**
 * Registry for dynamic classes of the "Friendica\Factory" namespace
 */
abstract class Factory extends BaseRegistry
{
	/**
	 * @return F\Mastodon\Account
	 */
	public static function mstdnAccount()
	{
		return self::$dice->create(F\Mastodon\FollowRequest::class);
	}

	/**
	 * @return F\Mastodon\FollowRequest
	 */
	public static function mstdnFollowRequest()
	{
		return self::$dice->create(F\Mastodon\FollowRequest::class);
	}

	/**
	 * @return F\Mastodon\Relationship
	 */
	public static function mstdnRelationship()
	{
		return self::$dice->create(F\Mastodon\Relationship::class);
	}
}
