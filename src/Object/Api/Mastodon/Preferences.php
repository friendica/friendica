<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Preferences
 *
 * @see https://docs.joinmastodon.org/entities/preferences/
 */
class Preferences extends BaseDataTransferObject
{
	/**
	 * Creates a preferences record.
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(
		/**
		 * @var string (Enumerable, oneOf)
		 */
		private readonly string $visibility,
		private readonly bool $sensitive,
		/**
		 * @var string (ISO 639-1 language two-letter code), or null
		 */
		private readonly string $language,
		/**
		 * @var string (Enumerable, oneOf)
		 */
		private readonly string $media,
		private readonly bool $spoilers,
	) {}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'posting:default:visibility' => $this->visibility,
			'posting:default:sensitive'  => $this->sensitive,
			'posting:default:language'   => $this->language,
			'reading:expand:media'       => $this->media,
			'reading:expand:spoilers'    => $this->spoilers,
		];
	}
}
