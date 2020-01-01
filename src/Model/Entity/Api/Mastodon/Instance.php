<?php

namespace Friendica\Model\Entity\Api\Mastodon;

use Friendica\Api\Mastodon\Account;
use Friendica\Api\Mastodon\Stats;
use Friendica\Model\Entity\BaseEntity;
use Friendica\Model\Entity\Traits\DataTransferObjectTrait;

/**
 * @property-read string uri
 * @property-read string title
 * @property-read string description
 * @property-read string email
 * @property-read string version
 * @property-read array urls
 * @property-read Stats stats
 * @property-read string thumbnail
 * @property-read array languages
 * @property-read int max_toot_chars
 * @property-read boolean registratrions
 * @property-read boolean approval_required
 * @property-read Account|null contact_account
 */
class Instance extends BaseEntity
{
	use DataTransferObjectTrait;
}
