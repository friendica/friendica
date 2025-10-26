<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Collection\Api\Mastodon\Fields;

/**
 * Class Source
 *
 * @see https://docs.joinmastodon.org/entities/Account/#source
 */
class Source extends BaseDataTransferObject
{
	/** @var array */
	protected $attribution_domains;
	/** @var string */
	protected $note;
	/** @var array */
	protected $fields;
	/** @var string */
	protected $privacy;
	/** @var bool */
	protected $sensitive;
	/** @var string */
	protected $language;
	/** @var int */
	protected $follow_requests_count;
	/** @var bool  */
	protected $hide_collections;
	/** @var bool */
	protected $discoverable;
	/** @var bool */
	protected $indexable;
	/** @var Role */
	protected $role;

	/**
	 * @param array  $attribution_domains
	 * @param string $note
	 * @param Fields $fields
	 * @param string $privacy
	 * @param bool   $sensitive
	 * @param string $language
	 * @param int    $follow_requests_count
	 * @param bool   $hide_collections
	 * @param bool   $discoverable
	 * @param bool   $indexable
	 * @param Role  $role
	 */
	public function __construct(
		array  $attribution_domains,
		string $note,
		Fields $fields,
		string $privacy,
		bool   $sensitive,
		string $language,
		int    $follow_requests_count,
		?bool  $hide_collections,
		bool   $discoverable,
		bool   $indexable,
		Role   $role
	) {
		$this->attribution_domains   = $attribution_domains;
		$this->note                  = $note;
		$this->fields                = $fields->getArrayCopy();
		$this->privacy               = $privacy;
		$this->sensitive             = $sensitive;
		$this->language              = $language;
		$this->follow_requests_count = $follow_requests_count;
		$this->hide_collections      = $hide_collections;
		$this->discoverable          = $discoverable;
		$this->indexable             = $indexable;
		$this->role                  = $role;
	}
}
