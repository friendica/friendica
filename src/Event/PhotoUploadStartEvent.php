<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a photo is about to be uploaded
 *
 * Can be used by addons to modify the photo upload request data.
 */
final class PhotoUploadStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.photo_upload_start';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $request
	 */
	public function __construct(
		private array $request,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getRequestArray(): array
	{
		return $this->request;
	}

	/** @param array<string, mixed> $request */
	public function setRequestArray(array $request): void
	{
		$this->request = $request;
	}
}
