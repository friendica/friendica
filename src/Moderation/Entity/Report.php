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

	public function __construct(
		/** @var int ID of the contact making a moderation report */
		protected int $reporterCid,
		/** @var int ID of the contact being reported */
		protected int $cid,
		/** @var int ID of the gserver of the contact being reported */
		protected int $gsid,
		/** @var \DateTimeImmutable When the report was created */
		protected \DateTimeImmutable $created,
		/** @var int One of CATEGORY_* */
		protected int $category,
		/** @var int ID of the user making a moderation report, null in case of an incoming forwarded report */
		protected ?int $reporterUid = null,
		/** @var string Reporter comment */
		protected string $comment = '',
		/** @var bool Whether this report should be forwarded to the remote server */
		protected bool $forward = false,
		protected ?Collection\Report\Posts $posts = new Collection\Report\Posts(),
		protected ?Collection\Report\Rules $rules = new Collection\Report\Rules(),
		/** @var string Remarks shared with the reporter */
		protected string $publicRemarks = '',
		/** @var string Remarks shared with the moderation team */
		protected string $privateRemarks = '',
		/** @var \DateTimeImmutable|null When the report was last edited */
		protected ?\DateTimeImmutable $edited = null,
		/** @var int One of STATUS_* */
		protected int $status = self::STATUS_OPEN,
		/** @var int|null One of RESOLUTION_* if any */
		protected ?int $resolution = null,
		/** @var int|null Assigned moderator user id if any */
		protected ?int $assignedUid = null,
		/** @var int|null Last editor user ID if any */
		protected ?int $lastEditorUid = null,
		protected ?int $id = null,
	) {}
}
