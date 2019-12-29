<?php

namespace Friendica\Api\Mastodon;

use Friendica\Api\Entity;

/**
 * Class Field
 *
 * @see https://docs.joinmastodon.org/api/entities/#field
 */
class Field extends Entity
{
	/** @var string */
	protected $name;
	/** @var string (HTML) */
	protected $value;
	/** @var string (Datetime)*/
	protected $verified_at;
}
