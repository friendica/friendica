<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Attachment
 *
 * @see https://docs.joinmastodon.org/entities/attachment
 */
class Attachment extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $type;
	/** @var string */
	protected $url;
	/** @var string */
	protected $preview_url;
	/** @var string */
	protected $remote_url;
	/** @var string */
	protected $text_url;
	/** @var string */
	protected $description;
	/** @var string */
	protected $blurhash;
	/** @var array */
	protected $meta;

	/**
	 * Creates an attachment
	 *
	 * @param array $attachment Expected keys: id, description
	 *                          If $type == 'image': width, height[, preview-width, preview-height]
	 * @param string $type      One of: audio, video, gifv, image, unknown
	 * @param string $url
	 * @param string $preview
	 * @param string $remote
	 */
	public function __construct(array $attachment, string $type, string $url, string $preview, string $remote)
	{
		$this->id          = (string)$attachment['id'];
		$this->type        = $type;
		$this->url         = $url;
		$this->preview_url = $preview;
		$this->remote_url  = $remote;
		$this->text_url    = $this->remote_url ?? $this->url;
		$this->description = $attachment['description'];
		$this->blurhash    = $attachment['blurhash'];
		if ($type === 'image') {
			if ((int) $attachment['width'] > 0 && (int) $attachment['height'] > 0) {
				$this->meta['original']['width']  = (int) $attachment['width'];
				$this->meta['original']['height'] = (int) $attachment['height'];
				$this->meta['original']['size']   = (int) $attachment['width'] . 'x' . (int) $attachment['height'];
				$this->meta['original']['aspect'] = (float) ((int)  $attachment['width'] / (int) $attachment['height']);
			}

			if (isset($attachment['preview-width']) && (int) $attachment['preview-width'] > 0 && (int) $attachment['preview-height'] > 0) {
				$this->meta['small']['width']  = (int) $attachment['preview-width'];
				$this->meta['small']['height'] = (int) $attachment['preview-height'];
				$this->meta['small']['size']   = (int) $attachment['preview-width'] . 'x' . (int) $attachment['preview-height'];
				$this->meta['small']['aspect'] = (float) ((int)  $attachment['preview-width'] / (int) $attachment['preview-height']);
			}
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$attachment = parent::toArray();

		if (empty($attachment['remote_url'])) {
			$attachment['remote_url'] = null;
		}

		return $attachment;
	}
}
