<?php

namespace Friendica\Model\Entity;

use DateTime;
use Friendica\Model\Entity\Traits\StorableTrait;

/**
 * @property int      uid
 * @property int      fid
 * @property Contact  contact
 * @property boolean  knowyou
 * @property boolean  duplex
 * @property string   note
 * @property string   hash
 * @property DateTime datetime
 * @property boolean  blocked
 * @property boolean  ignored
 */
class Introduction extends BaseEntity implements IStorable
{
	use StorableTrait;
}
