<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Moderation\Entity\Report;

/**
 * @property-read int $uriId URI Id of the reported post
 * @property-read int $status One of STATUS_*
 */
final class Post extends \Friendica\BaseEntity
{
	public const STATUS_NO_ACTION = 0;
	public const STATUS_UNLISTED  = 1;
	public const STATUS_DELETED   = 2;

	public function __construct(protected int $uriId, protected int $status = self::STATUS_NO_ACTION) {}
}
