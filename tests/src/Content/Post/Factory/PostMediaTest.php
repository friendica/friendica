<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Content\Post\Factory;

use Friendica\Content\Post\Entity\PostMedia;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;
use Friendica\Test\src\Content\Post\PostMediaRowTrait;

class PostMediaTest extends FixtureTestCase
{
	use PostMediaRowTrait;

	public function testLinkIsCreatedFromVideo(): void
	{
		$video = $this->createMedia([
			'uri-id'         => 7,
			'url'            => 'https://peertube.example/videos/embed/abc',
			'type'           => PostMedia::TYPE_VIDEO,
			'preview'        => 'https://peertube.example/preview.jpg',
			'preview-width'  => 640,
			'preview-height' => 360,
			'blurhash'       => 'LEHV6nWB2yk8pyo0adR*.7kCMdnj',
		]);

		$link = DI::postMediaFactory()->createLinkFromVideo($video, 'https://peertube.example/w/abc', 'A title', 'An author', 'https://peertube.example/a/author');

		self::assertSame(7, $link->uriId);
		self::assertSame(PostMedia::TYPE_HTML, $link->type);
		self::assertSame('https://peertube.example/w/abc', (string) $link->url);
		self::assertSame('A title', $link->name);
		self::assertSame('An author', $link->authorName);
		self::assertSame('https://peertube.example/a/author', (string) $link->authorUrl);
		self::assertSame('https://peertube.example/preview.jpg', (string) $link->preview);
		self::assertSame(640, $link->previewWidth);
		self::assertSame(360, $link->previewHeight);
		self::assertSame('LEHV6nWB2yk8pyo0adR*.7kCMdnj', $link->blurhash);
		self::assertFalse($link->hasPlayerUrl());
		self::assertFalse($link->hasEmbedHtml());
		self::assertFalse($link->isPlayable());
	}

	public function testEmptyTextsAndMissingPreviewAreEmpty(): void
	{
		$video = $this->createMedia(['type' => PostMedia::TYPE_VIDEO]);

		$link = DI::postMediaFactory()->createLinkFromVideo($video, 'https://peertube.example/w/abc', '', '', '');

		self::assertNull($link->name);
		self::assertNull($link->authorName);
		self::assertSame('', (string) $link->preview);
	}
}
