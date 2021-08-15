<?php

namespace Friendica\Collection;

use Friendica\BaseCollection;
use Friendica\Model;

class Hosts extends BaseCollection
{
	/**
	 * @return Model\Host
	 */
	public function current(): Model\Host
	{
		return parent::current();
	}
}
