<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Federation\Entity;

use DateTimeImmutable;

/**
 * @property-read int               $targetServerId
 * @property-read int               $postUriId
 * @property-read DateTimeImmutable $created
 * @property-read string            $command         One of the Protocol\Delivery command constant values
 * @property-read int               $targetContactId
 * @property-read int               $senderUserId
 * @property-read int               $failed          Number of delivery failures for this post and target server
 */
final class DeliveryQueueItem extends \Friendica\BaseEntity
{
	/** @var int */
	protected $targetServerId;
	/** @var int */
	protected $postUriId;
	/** @var DateTimeImmutable */
	protected $created;
	/** @var string */
	protected $command;
	/** @var int */
	protected $targetContactId;
	/** @var int */
	protected $senderUserId;
	/** @var int */
	protected $failed;

	public function __construct(int $targetServerId, int $postUriId, DateTimeImmutable $created, string $command, int $targetContactId, int $senderUserId, int $failed = 0)
	{
		$this->targetServerId  = $targetServerId;
		$this->postUriId       = $postUriId;
		$this->created         = $created;
		$this->command         = $command;
		$this->targetContactId = $targetContactId;
		$this->senderUserId    = $senderUserId;
		$this->failed          = $failed;
	}
}
