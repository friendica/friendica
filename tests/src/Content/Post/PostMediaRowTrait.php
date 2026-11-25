<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Content\Post;

use Friendica\Content\Post\Entity\PostMedia as PostMediaEntity;
use Friendica\DI;

/**
 * Creates post media entities from the fields that are relevant for a test
 */
trait PostMediaRowTrait
{
	private function createMedia(array $fields = []): PostMediaEntity
	{
		return DI::postMediaFactory()->createFromTableRow(array_merge([
			'id'              => 0,
			'uri-id'          => 1,
			'url'             => 'https://remote.example/media/file',
			'type'            => PostMediaEntity::TYPE_UNKNOWN,
			'mimetype'        => null,
			'media-uri-id'    => null,
			'width'           => null,
			'height'          => null,
			'size'            => null,
			'preview'         => null,
			'preview-width'   => null,
			'preview-height'  => null,
			'description'     => null,
			'name'            => null,
			'author-url'      => null,
			'author-name'     => null,
			'author-image'    => null,
			'publisher-url'   => null,
			'publisher-name'  => null,
			'publisher-image' => null,
			'blurhash'        => null,
			'player-url'      => null,
			'player-width'    => null,
			'player-height'   => null,
			'embed-type'      => null,
			'embed-html'      => null,
			'embed-width'     => null,
			'embed-height'    => null,
			'page-type'       => null,
			'schematypes'     => null,
			'attach-id'       => null,
			'language'        => null,
			'published'       => null,
			'modified'        => null,
		], $fields));
	}
}
