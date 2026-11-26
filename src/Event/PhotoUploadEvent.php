<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a photo upload is being processed
 *
 * Can be used by addons to modify the uploaded photo data.
 */
final class PhotoUploadEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.photo_upload';

	/**
	 * @internal
	 *
	 * @param string $src
	 * @param string $filename
	 * @param int    $filesize
	 * @param string $type
	 */
	public function __construct(
		private string $src,
		private string $filename,
		private int $filesize,
		private string $type,
	) {
		parent::__construct(self::NAME);
	}

	public function getSrc(): string
	{
		return $this->src;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function getFilesize(): int
	{
		return $this->filesize;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setSrc(string $src): void
	{
		$this->src = $src;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}

	public function setFilesize(int $filesize): void
	{
		$this->filesize = $filesize;
	}

	public function setType(string $type): void
	{
		$this->type = $type;
	}
}
