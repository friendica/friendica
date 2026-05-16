<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Moderation\Entity;

use Friendica\Moderation\Collection;

/**
 * @property-read int                     $id
 * @property-read int                     $reporterCid
 * @property-read int                     $cid
 * @property-read int                     $gsid
 * @property-read string                  $comment
 * @property-read string                  $publicRemarks
 * @property-read string                  $privateRemarks
 * @property-read bool                    $forward
 * @property-read int                     $category
 * @property-read int                     $status
 * @property-read int|null                $resolution
 * @property-read int                     $reporterUid
 * @property-read int|null                $lastEditorUid
 * @property-read int|null                $assignedUid
 * @property-read \DateTimeImmutable      $created
 * @property-read \DateTimeImmutable|null $edited
 * @property-read Collection\Report\Posts $posts
 * @property-read Collection\Report\Rules $rules
 */
final class Report extends \Friendica\BaseEntity
{
	public const CATEGORY_OTHER     = 1;
	public const CATEGORY_SPAM      = 2;
	public const CATEGORY_ILLEGAL   = 4;
	public const CATEGORY_SAFETY    = 8;
	public const CATEGORY_UNWANTED  = 16;
	public const CATEGORY_VIOLATION = 32;

	public const CATEGORIES = [
		self::CATEGORY_OTHER,
		self::CATEGORY_SPAM,
		self::CATEGORY_ILLEGAL,
		self::CATEGORY_SAFETY,
		self::CATEGORY_UNWANTED,
		self::CATEGORY_VIOLATION,
	];

	public const STATUS_CLOSED = 1;
	public const STATUS_OPEN   = 0;

	public const RESOLUTION_ACCEPTED = 0;
	public const RESOLUTION_REJECTED = 1;

	/** @var int|null */
	protected $id;
	/** @var int ID of the contact making a moderation report */
	protected $reporterCid;
	/** @var int ID of the contact being reported */
	protected $cid;
	/** @var int ID of the gserver of the contact being reported */
	protected $gsid;
	/** @var string Reporter comment */
	protected $comment;
	/** @var int One of CATEGORY_* */
	protected $category;
	/** @var int ID of the user making a moderation report, null in case of an incoming forwarded report */
	protected $reporterUid;
	/** @var bool Whether this report should be forwarded to the remote server */
	protected $forward;
	/** @var \DateTimeImmutable When the report was created */
	protected $created;
	/** @var Collection\Report\Rules List of terms of service rule lines being possibly violated */
	protected $rules;
	/** @var Collection\Report\Posts List of URI IDs of posts supporting the report */
	protected $posts;
	/** @var string Remarks shared with the reporter */
	protected $publicRemarks;
	/** @var string Remarks shared with the moderation team */
	protected $privateRemarks;
	/** @var \DateTimeImmutable|null When the report was last edited */
	protected $edited;
	/** @var int One of STATUS_* */
	protected $status;
	/** @var int|null One of RESOLUTION_* if any */
	protected $resolution;
	/** @var int|null Assigned moderator user id if any */
	protected $assignedUid;
	/** @var int|null Last editor user ID if any */
	protected $lastEditorUid;

	public function __construct(
		int $reporterCid,
		int $cid,
		int $gsid,
		\DateTimeImmutable $created,
		int $category,
		int $reporterUid = null,
		string $comment = '',
		bool $forward = false,
		Collection\Report\Posts $posts = null,
		Collection\Report\Rules $rules = null,
		string $publicRemarks = '',
		string $privateRemarks = '',
		\DateTimeImmutable $edited = null,
		int $status = self::STATUS_OPEN,
		int $resolution = null,
		int $assignedUid = null,
		int $lastEditorUid = null,
		int $id = null
	) {
		$this->reporterCid    = $reporterCid;
		$this->cid            = $cid;
		$this->gsid           = $gsid;
		$this->created        = $created;
		$this->category       = $category;
		$this->reporterUid    = $reporterUid;
		$this->comment        = $comment;
		$this->forward        = $forward;
		$this->posts          = $posts ?? new Collection\Report\Posts();
		$this->rules          = $rules ?? new Collection\Report\Rules();
		$this->publicRemarks  = $publicRemarks;
		$this->privateRemarks = $privateRemarks;
		$this->edited         = $edited;
		$this->status         = $status;
		$this->resolution     = $resolution;
		$this->assignedUid    = $assignedUid;
		$this->lastEditorUid  = $lastEditorUid;
		$this->id             = $id;
	}
}
