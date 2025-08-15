<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;

/**
 * Class Mention
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#mentions
 */
class Mention extends BaseDataTransferObject
{
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var array */
	protected $indices;
	/** @var string */
	protected $name;
	/** @var string */
	protected $screen_name;

	/**
	 * Creates a mention record from an tag-view record.
	 *
	 * @param array   $tag     tag-view record
	 * @param array   $contact contact table record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $tag, array $contact, array $indices)
	{
		$this->id          = (int)($contact['id'] ?? 0);
		$this->id_str      = (string)($contact['id'] ?? 0);
		$this->indices     = $indices;
		$this->name        = $tag['name'];
		$this->screen_name = $contact['nick'];
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['indices'])) {
			unset($status['indices']);
		}

		return $status;
	}
}
