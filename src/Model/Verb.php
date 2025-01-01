<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Database\Database;
use Friendica\Database\DBA;

class Verb
{
	public static $verbs = [];

	/**
	 * Insert a verb record and return its id
	 *
	 * @param string $verb
	 *
	 * @return integer verb id
	 * @throws \Exception
	 */
	public static function getID(string $verb): int
	{
		if (empty($verb)) {
			return 0;
		}

		$id = array_search($verb, self::$verbs);
		if ($id !== false) {
			return $id;
		}

		$verb_record = DBA::selectFirst('verb', ['id'], ['name' => $verb]);
		if (DBA::isResult($verb_record)) {
			self::$verbs[$verb_record['id']] = $verb;
			return $verb_record['id'];
		}

		DBA::insert('verb', ['name' => $verb], Database::INSERT_IGNORE);

		$id               = DBA::lastInsertId();
		self::$verbs[$id] = $verb;
		return $id;

	}

	/**
	 * Return verb name for the given ID
	 *
	 * @param integer $id
	 * @return string verb
	 */
	public static function getByID(int $id): string
	{
		if (empty($id)) {
			return '';
		}

		if (!empty(self::$verbs[$id])) {
			return self::$verbs[$id];
		}

		$verb_record = DBA::selectFirst('verb', ['name'], ['id' => $id]);
		if (!DBA::isResult($verb_record)) {
			return '';
		}

		self::$verbs[$id] = $verb_record['name'];

		return $verb_record['name'];
	}
}
