<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class SavedSearch
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-list
 */
class SavedSearch extends BaseDataTransferObject
{
	/** @var string|null (Datetime) */
	protected $created_at;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var string */
	protected $name;
	/** @var string|null */
	protected $position;
	/** @var string */
	protected $query;

	/**
	 * Creates a saved search record from a search record.
	 *
	 * @param array   $search Full search table record
	 */
	public function __construct(array $search)
	{
		$this->created_at = DateTimeFormat::utcNow(DateTimeFormat::JSON);
		$this->id         = (int)$search['id'];
		$this->id_str     = (string)$search['id'];
		$this->name       = $search['term'];
		$this->position   = null;
		$this->query      = $search['term'];
	}
}
