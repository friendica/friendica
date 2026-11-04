<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\Account\Factory;

use Friendica\BaseFactory;
use Friendica\Security\Account\ValueObject\Account as AccountObject;

class Account extends BaseFactory
{
	/**
	 * @param array{uid: int|string, username: string, nickname: string, email: string, language: string} $row
	 */
	public function createFromTableRow(array $row): AccountObject
	{
		return new AccountObject((int) $row['uid'], $row['username'], $row['nickname'], $row['email'], $row['language']);
	}
}
