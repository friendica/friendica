<?php

namespace Friendica\Collection;

use Friendica\BaseCollection;

class ProfileFields extends BaseCollection
{
	/**
	 * @param callable $callback
	 * @return ProfileFields
	 */
	public function map(callable $callback)
	{
		return parent::map($callback);
	}

	/**
	 * @param callable|null $callback
	 * @param int           $flag
	 * @return ProfileFields
	 */
	public function filter(callable $callback = null, int $flag = 0)
	{
		return parent::filter($callback, $flag);
	}
}
