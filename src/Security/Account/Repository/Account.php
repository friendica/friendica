<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\Account\Repository;

use Friendica\Database\Database;
use Friendica\Security\Account\Factory\Account as AccountFactory;
use Friendica\Security\Account\ValueObject\Account as AccountObject;

/**
 * Finds the local accounts that may sign in or recover access.
 */
class Account
{
	private const FIELDS = ['uid', 'username', 'nickname', 'email', 'language'];

	public function __construct(
		private readonly Database $dba,
		private readonly AccountFactory $factory,
	) {}

	/**
	 * @param string $login Nickname or e-mail address
	 */
	public function selectByLogin(string $login): ?AccountObject
	{
		if ($login === '') {
			return null;
		}

		$condition = ['(`email` = ? OR `nickname` = ?) AND `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`', $login, $login];
		$row       = $this->dba->selectFirst('user', self::FIELDS, $condition);
		if (!$this->dba->isResult($row)) {
			return null;
		}

		return $this->factory->createFromTableRow($row);
	}
}
