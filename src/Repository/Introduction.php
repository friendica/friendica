<?php

namespace Friendica\Repository;

use Friendica\Collection;
use Friendica\Core\Protocol;
use Friendica\Model;


use Friendica\Protocol\Diaspora;
use Friendica\Repository;
use Friendica\Util\DateTimeFormat;

/**
 * @method Model\Introduction       selectFirst(array $condition)
 * @method Collection\Introductions select(array $condition = [], array $params = [])
 * @method Collection\Introductions selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
 */
class Introduction extends Repository
{
	protected static $table_name = 'intro';

	protected static $model_class = Model\Introduction::class;

	protected static $collection_class = Collection\Introductions::class;
}
