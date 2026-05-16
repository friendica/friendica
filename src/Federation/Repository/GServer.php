<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Federation\Repository;

use Friendica\Database\Database;
use Friendica\Federation\Entity\GServer as GServerEntity;
use Friendica\Federation\Factory\GServer as GServerFactory;
use Friendica\Network\HTTPException\NotFoundException;

final class GServer
{
	private string $table_name = 'gserver';

	private Database $db;

	private GServerFactory $factory;

	public function __construct(Database $database, GServerFactory $factory)
	{
		$this->db      = $database;
		$this->factory = $factory;
	}

	/**
	 * @param int $gsid
	 *
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectOneById(int $gsid): GServerEntity
	{
		$fields = $this->db->selectFirst($this->table_name, [], ['id' => $gsid], []);

		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		return $this->factory->createFromTableRow($fields);
	}
}
