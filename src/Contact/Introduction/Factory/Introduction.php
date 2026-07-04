<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\Introduction\Factory;

use Friendica\BaseFactory;
use Friendica\Contact\Introduction\Entity;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class Introduction extends BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\Introduction
	{
		return new Entity\Introduction(
			$row['uid']         ?? 0,
			$row['contact-id']  ?? 0,
			$row['suggest-cid'] ?? null,
			!empty($row['knowyou']),
			$row['note'] ?? '',
			$row['hash'] ?? '',
			new \DateTime($row['datetime'] ?? 'now', new \DateTimeZone('UTC')),
			!empty($row['ignore']),
			$row['id'] ?? null,
		);
	}

	public function createNew(
		int $uid,
		int $cid,
		string $note,
		?int $sid = null,
		bool $knowyou = false,
	): Entity\Introduction {
		return $this->createFromTableRow([
			'uid'         => $uid,
			'suggest-cid' => $sid,
			'contact-id'  => $cid,
			'knowyou'     => $knowyou,
			'note'        => $note,
			'hash'        => Strings::getRandomHex(),
			'datetime'    => DateTimeFormat::utcNow(),
			'ignore'      => false,
		]);
	}

	public function createDummy(?int $id): Entity\Introduction
	{
		return $this->createFromTableRow(['id' => $id]);
	}
}
