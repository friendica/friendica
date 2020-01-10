<?php


namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Factory as FactoryNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Factory" namespace
 */
abstract class Factory extends BaseRegistry
{
	/**
	 * @return FactoryNamespace\Mastodon\Account
	 */
	public static function mstdnAccount()
	{
		return self::$dice->create(FactoryNamespace\Mastodon\FollowRequest::class);
	}

	/**
	 * @return FactoryNamespace\Mastodon\FollowRequest
	 */
	public static function mstdnFollowRequest()
	{
		return self::$dice->create(FactoryNamespace\Mastodon\FollowRequest::class);
	}

	/**
	 * @return FactoryNamespace\Mastodon\Relationship
	 */
	public static function mstdnRelationship()
	{
		return self::$dice->create(FactoryNamespace\Mastodon\Relationship::class);
	}
}
