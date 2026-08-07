<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after a photo upload has been processed
 *
 * Can be used by addons to react when a photo upload has been completed.
 */
final class PhotoUploadEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.photo_upload_end';

	/**
	 * @internal
	 *
	 * @param int $id The resource ID of the uploaded photo, 0 if the upload failed
	 */
	public function __construct(
		private readonly int $id,
	) {
		parent::__construct(self::NAME);
	}

	public function getId(): int
	{
		return $this->id;
	}
}
