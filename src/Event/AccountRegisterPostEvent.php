<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when the registration form is submitted.
 */
final class AccountRegisterPostEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.account_register_post';

	public function __construct(
		private array $post,
	) {
		parent::__construct(self::NAME);
	}

	public function getPostArray(): array
	{
		return $this->post;
	}

	public function setPostArray(array $post): void
	{
		$this->post = $post;
	}
}
