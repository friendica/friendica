<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Federation\Entity;

/**
 * @property-read int $targetServerId
 * @property-read int $failed         Maximum number of delivery failures among the delivery queue items targeting the server
 */
final class DeliveryQueueAggregate extends \Friendica\BaseEntity
{
	public function __construct(protected int $targetServerId, protected int $failed) {}
}
