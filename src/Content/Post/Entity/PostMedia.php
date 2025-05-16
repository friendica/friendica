<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Post\Entity;

use Friendica\BaseEntity;
use Friendica\Network\Entity\MimeType;
use Friendica\Util\Images;
use Friendica\Util\Proxy;
use Psr\Http\Message\UriInterface;

/**
 * @property-read int $id
 * @property-read int $uriId
 * @property-read ?int $activityUriId
 * @property-read UriInterface $url
 * @property-read int $type
 * @property-read MimeType $mimetype
 * @property-read ?int $width
 * @property-read ?int $height
 * @property-read ?int $size
 * @property-read ?UriInterface $preview
 * @property-read ?int $previewWidth
 * @property-read ?int $previewHeight
 * @property-read ?string $description
 * @property-read ?string $name
 * @property-read ?UriInterface $authorUrl
 * @property-read ?string $authorName
 * @property-read ?UriInterface $authorImage
 * @property-read ?UriInterface $publisherUrl
 * @property-read ?string $publisherName
 * @property-read ?UriInterface $publisherImage
 * @property-read ?string $blurhash
 */
class PostMedia extends BaseEntity
{
	const TYPE_UNKNOWN     = 0;
	const TYPE_IMAGE       = 1;
	const TYPE_VIDEO       = 2;
	const TYPE_AUDIO       = 3;
	const TYPE_TEXT        = 4;
	const TYPE_APPLICATION = 5;
	const TYPE_TORRENT     = 16;
	const TYPE_HTML        = 17;
	const TYPE_XML         = 18;
	const TYPE_PLAIN       = 19;
	const TYPE_ACTIVITY    = 20;
	const TYPE_ACCOUNT     = 21;
	const TYPE_HLS         = 22;
	const TYPE_DOCUMENT    = 128;

	/** @var int */
	protected $id;
	/** @var int */
	protected $uriId;
	/** @var UriInterface */
	protected $url;
	/** @var int One of TYPE_* */
	protected $type;
	/** @var MimeType */
	protected $mimetype;
	/** @var ?int */
	protected $activityUriId;
	/** @var ?int In pixels */
	protected $width;
	/** @var ?int In pixels */
	protected $height;
	/** @var ?int In bytes */
	protected $size;
	/** @var ?UriInterface Preview URL */
	protected $preview;
	/** @var ?int In pixels */
	protected $previewWidth;
	/** @var ?int In pixels */
	protected $previewHeight;
	/** @var ?string Alternative text like for images */
	protected $description;
	/** @var ?string */
	protected $name;
	/** @var ?UriInterface */
	protected $authorUrl;
	/** @var ?string */
	protected $authorName;
	/** @var ?UriInterface Image URL */
	protected $authorImage;
	/** @var ?UriInterface */
	protected $publisherUrl;
	/** @var ?string */
	protected $publisherName;
	/** @var ?UriInterface Image URL */
	protected $publisherImage;
	/** @var ?string Blurhash string representation for images
	 * @see https://github.com/woltapp/blurhash
	 * @see https://blurha.sh/
	 */
	protected $blurhash;

	public function __construct(
		int $uriId,
		UriInterface $url,
		int $type,
		MimeType $mimetype,
		?int $activityUriId,
		?int $width = null,
		?int $height = null,
		?int $size = null,
		?UriInterface $preview = null,
		?int $previewWidth = null,
		?int $previewHeight = null,
		?string $description = null,
		?string $name = null,
		?UriInterface $authorUrl = null,
		?string $authorName = null,
		?UriInterface $authorImage = null,
		?UriInterface $publisherUrl = null,
		?string $publisherName = null,
		?UriInterface $publisherImage = null,
		?string $blurhash = null,
		int $id = null
	) {
		$this->uriId          = $uriId;
		$this->url            = $url;
		$this->type           = $type;
		$this->mimetype       = $mimetype;
		$this->activityUriId  = $activityUriId;
		$this->width          = $width;
		$this->height         = $height;
		$this->size           = $size;
		$this->preview        = $preview;
		$this->previewWidth   = $previewWidth;
		$this->previewHeight  = $previewHeight;
		$this->description    = $description;
		$this->name           = $name;
		$this->authorUrl      = $authorUrl;
		$this->authorName     = $authorName;
		$this->authorImage    = $authorImage;
		$this->publisherUrl   = $publisherUrl;
		$this->publisherName  = $publisherName;
		$this->publisherImage = $publisherImage;
		$this->blurhash       = $blurhash;
		$this->id             = $id;
	}


	/**
	 * Get media link for given media id
	 *
	 * @param string  $size One of the Proxy::SIZE_* constants
	 * @return string media link
	 */
	public function getPhotoPath(string $size = ''): string
	{
		return '/photo/media/' .
			(Proxy::getPixelsFromSize($size) ? Proxy::getPixelsFromSize($size) . '/' : '') .
			$this->id;
	}

	/**
	 * Get preview path for given media id relative to the base URL
	 *
	 * @param string  $size  One of the Proxy::SIZE_* constants
	 * @param bool    $blur  If "true", the preview will be blurred
	 * @return string preview link
	 */
	public function getPreviewPath(string $size = '', bool $blur = false): string
	{
		$path = '/photo/preview/' .
			(Proxy::getPixelsFromSize($size) ? Proxy::getPixelsFromSize($size) . '/' : '') .
			$this->id;

		if ($blur) {
			$path .= '?' . http_build_query(['blur' => true]);
		}
		return $path;
	}

	/**
	 * Computes the allocated height value used in the content/image/single_with_height_allocation.tpl template
	 *
	 * Either base or preview dimensions need to be set at runtime.
	 *
	 * @return string
	 */
	public function getAllocatedHeight(): string
	{
		if (!$this->hasDimensions()) {
			throw new \RangeException('Either width and height or previewWidth and previewHeight must be defined to use this method.');
		}

		if ($this->width && $this->height) {
			$width  = $this->width;
			$height = $this->height;
		} else {
			$width  = $this->previewWidth;
			$height = $this->previewHeight;
		}

		return (100 * $height / $width) . '%';
	}

	/**
	 * Return a new PostMedia entity with a different preview URI and an optional proxy size name.
	 * The new entity preview's width and height are rescaled according to the provided size.
	 *
	 * @param \GuzzleHttp\Psr7\Uri $preview
	 * @param string               $size
	 * @return self
	 */
	public function withPreview(\GuzzleHttp\Psr7\Uri $preview, string $size = ''): self
	{
		if ($this->width || $this->height) {
			$newWidth  = $this->width;
			$newHeight = $this->height;
		} else {
			$newWidth  = $this->previewWidth;
			$newHeight = $this->previewHeight;
		}

		if ($newWidth && $newHeight && $size) {
			$dimensionts = Images::getScalingDimensions($newWidth, $newHeight, Proxy::getPixelsFromSize($size));
			$newWidth    = $dimensionts['width'];
			$newHeight   = $dimensionts['height'];
		}

		return new self(
			$this->uriId,
			$this->url,
			$this->type,
			$this->mimetype,
			$this->activityUriId,
			$this->width,
			$this->height,
			$this->size,
			$preview,
			$newWidth,
			$newHeight,
			$this->description,
			$this->name,
			$this->authorUrl,
			$this->authorName,
			$this->authorImage,
			$this->publisherUrl,
			$this->publisherName,
			$this->publisherImage,
			$this->blurhash,
			$this->id,
		);
	}

	public function withUrl(\GuzzleHttp\Psr7\Uri $url): self
	{
		return new self(
			$this->uriId,
			$url,
			$this->type,
			$this->mimetype,
			$this->activityUriId,
			$this->width,
			$this->height,
			$this->size,
			$this->preview,
			$this->previewWidth,
			$this->previewHeight,
			$this->description,
			$this->name,
			$this->authorUrl,
			$this->authorName,
			$this->authorImage,
			$this->publisherUrl,
			$this->publisherName,
			$this->publisherImage,
			$this->blurhash,
			$this->id,
		);
	}

	/**
	 * Checks the media has at least one full set of dimensions, needed for the height allocation feature
	 *
	 * @return bool
	 */
	public function hasDimensions(): bool
	{
		return $this->width && $this->height || $this->previewWidth && $this->previewHeight;
	}
}
