<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a user attempts to login.
 */
final class AccountAuthenticateEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.account_authenticate';

	private bool $authenticated = false;
	/** @var array<string, mixed>|null */
	private ?array $userRecord = null;

	public function __construct(
		private readonly string $username,
		#[\SensitiveParameter]
		private readonly string $password,
	) {
		parent::__construct(self::NAME);
	}

	public function getUsername(): string
	{
		return $this->username;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function isAuthenticated(): bool
	{
		return $this->authenticated;
	}

	public function setAuthenticated(bool $authenticated): void
	{
		$this->authenticated = $authenticated;
	}

	/** @return array<string, mixed>|null */
	public function getUserRecordArray(): ?array
	{
		return $this->userRecord;
	}

	/** @param array<string, mixed>|null $userRecord */
	public function setUserRecordArray(?array $userRecord): void
	{
		$this->userRecord = $userRecord;
	}
}
