<?php

namespace Friendica\Model\Entity;

use DateTime;
use Friendica\Model\Entity\Traits\StorableTrait;

/**
 * @property int      uid
 * @property DateTime created
 * @property boolean  self
 * @property boolean  remote_self
 * @property boolean  writable
 * @property boolean  blocker
 * @property boolean  pending
 * @property boolean  blocked
 * @property boolean  hidden
 * @property string   name
 * @property string   nickname
 * @property string   url
 * @property int      relation
 * @property string   protocol
 * @property string   network
 */
class Contact extends BaseEntity implements IStorable
{
	use StorableTrait;
}
