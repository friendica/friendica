<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Report
 *
 * @see https://docs.joinmastodon.org/entities/Admin_Report/
 */
class Report extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var bool */
	protected $action_taken;
	/** @var string|null (Datetime) */
	protected $action_taken_at;
	/** @var string */
	protected $category;
	/** @var string */
	protected $comment;
	/** @var bool */
	protected $forwarded;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var string (Datetime) */
	protected $updated_at;
	/** @var array|null */
	protected $account;
	/** @var array|null */
	protected $target_account;
	/** @var array|null */
	protected $assigned_account;
	/** @var array|null */
	protected $action_taken_by_account;
	/** @var array */
	protected $statuses;
	/** @var array */
	protected $rules;

	public function __construct(
		int $id,
		bool $actionTaken,
		?string $actionTakenAt,
		string $category,
		string $comment,
		bool $forwarded,
		string $createdAt,
		string $updatedAt,
		?array $account,
		?array $targetAccount,
		?array $assignedAccount,
		?array $actionTakenByAccount,
		array $statuses,
		array $rules,
	) {
		$this->id                      = (string) $id;
		$this->action_taken            = $actionTaken;
		$this->action_taken_at         = $actionTakenAt;
		$this->category                = $category;
		$this->comment                 = $comment;
		$this->forwarded               = $forwarded;
		$this->created_at              = $createdAt;
		$this->updated_at              = $updatedAt;
		$this->account                 = $account;
		$this->target_account          = $targetAccount;
		$this->assigned_account        = $assignedAccount;
		$this->action_taken_by_account = $actionTakenByAccount;
		$this->statuses                = $statuses;
		$this->rules                   = $rules;
	}
}
