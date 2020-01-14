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
}
