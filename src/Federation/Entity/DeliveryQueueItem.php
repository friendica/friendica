<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public function __construct(protected int $targetServerId, protected int $postUriId, protected DateTimeImmutable $created, protected string $command, protected int $targetContactId, protected int $senderUserId, protected int $failed = 0) {}
}
