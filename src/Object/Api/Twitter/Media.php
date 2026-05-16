<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;
use Friendica\Model\Post;

/**
 * Class Media
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#media
 */
class Media extends BaseDataTransferObject
{
	/** @var string */
	protected $display_url;
	/** @var string */
	protected $expanded_url;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var array */
	protected $indices;
	/** @var string */
	protected $media_url;
	/** @var string */
	protected $media_url_https;
	/** @var array<string, array<string, mixed>> */
	protected $sizes;
	/** @var string */
	protected $type;
	/** @var string */
	protected $url;

	/**
	 * Creates a media entity array
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $media, string $url, array $indices)
	{
		$this->display_url     = $media['url'];
		$this->expanded_url    = $media['url'];
		$this->id              = $media['id'];
		$this->id_str          = (string)$media['id'];
		$this->indices         = $indices;
		$this->media_url       = $media['url'];
		$this->media_url_https = $media['url'];
		$this->type            = $media['type'] == Post\Media::IMAGE ? 'photo' : 'video';
		$this->url             = $url;

		if (!empty($media['height']) && !empty($media['width'])) {
			if (($media['height'] <= 680) && ($media['width'] <= 680)) {
				$size = 'small';
			} elseif (($media['height'] <= 1200) && ($media['width'] <= 1200)) {
				$size = 'medium';
			} else {
				$size = 'large';
			}

			$this->sizes = [
				$size => [
					'h'      => $media['height'],
					'resize' => 'fit',
					'w'      => $media['width'],
				]
			];
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['indices'])) {
			unset($status['indices']);
		}

		return $status;
	}
}
