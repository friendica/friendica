<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Post\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Content\Post\Entity\PostMedia as PostMediaEntity;
use Friendica\Network\Entity\MimeType as MimeTypeEntity;
use Friendica\Network\Factory\MimeType as MimeTypeFactory;
use Friendica\Util\Network as UtilNetwork;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use stdClass;

class PostMedia extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var MimeTypeFactory */
	private $mimeTypeFactory;

	public function __construct(MimeTypeFactory $mimeTypeFactory, LoggerInterface $logger)
	{
		parent::__construct($logger);

		$this->mimeTypeFactory = $mimeTypeFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): PostMediaEntity
	{
		return new PostMediaEntity(
			$row['uri-id'],
			UtilNetwork::createUriFromString($row['url']),
			$row['type'],
			$this->mimeTypeFactory->createFromContentType($row['mimetype']),
			$row['media-uri-id'],
			$row['width'],
			$row['height'],
			$row['size'],
			UtilNetwork::createUriFromString($row['preview']),
			$row['preview-width'],
			$row['preview-height'],
			$row['description'],
			$row['name'],
			UtilNetwork::createUriFromString($row['author-url']),
			$row['author-name'],
			UtilNetwork::createUriFromString($row['author-image']),
			UtilNetwork::createUriFromString($row['publisher-url']),
			$row['publisher-name'],
			UtilNetwork::createUriFromString($row['publisher-image']),
			$row['blurhash'],
			$row['id']
		);
	}

	public function createFromBlueskyImageEmbed(int $uriId, stdClass $image): PostMediaEntity
	{
		return new PostMediaEntity(
			$uriId,
			new Uri($image->fullsize),
			PostMediaEntity::TYPE_IMAGE,
			new MimeTypeEntity('unkn', 'unkn'),
			null,
			null,
			null,
			null,
			new Uri($image->thumb),
			null,
			null,
			$image->alt,
		);
	}


	public function createFromBlueskyExternalEmbed(int $uriId, stdClass $external): PostMediaEntity
	{
		return new PostMediaEntity(
			$uriId,
			new Uri($external->uri),
			PostMediaEntity::TYPE_HTML,
			new MimeTypeEntity('text', 'html'),
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$external->description,
			$external->title
		);
	}

	public function createFromAttachment(int $uriId, array $attachment)
	{
		$attachment['uri-id'] = $uriId;
		return $this->createFromTableRow($attachment);
	}
}
