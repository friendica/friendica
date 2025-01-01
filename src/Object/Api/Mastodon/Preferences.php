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
	 * @var string (Enumerable, oneOf)
	 */
	private $visibility;

	/**
	 * @var bool
	 */
	private $sensitive;

	/**
	 * @var string (ISO 639-1 language two-letter code), or null
	 */
	private $language;

	/**
	 * @var string (Enumerable, oneOf)
	 */
	private $media;

	/**
	 * @var bool
	 */
	private $spoilers;

	/**
	 * Creates a preferences record.
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $visibility, bool $sensitive, string $language, string $media, bool $spoilers)
	{
		$this->visibility = $visibility;
		$this->sensitive  = $sensitive;
		$this->language   = $language;
		$this->media      = $media;
		$this->spoilers   = $spoilers;
	}

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
