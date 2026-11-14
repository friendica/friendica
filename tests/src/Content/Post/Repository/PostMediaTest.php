<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Post\Repository;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Test\FixtureTestCase;

class PostMediaTest extends FixtureTestCase
{
	private const UID = 42;

	private const IFRAME = '<span class="embedded-media"><iframe class="embed" src="https://media.example/player" style="" height="480px" width="620px" data-host="media.example" data-preview=""></iframe></span>';

	protected function setUp(): void
	{
		parent::setUp();
		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);
		DI::config()->set('system', 'url', 'https://friendica.local');
		DI::pConfig()->delete(self::UID, 'system', 'trusted_iframe_hosts');
	}

	public function testUntrustedIframeIsReplacedByPlaceholder(): void
	{
		$result = DI::postMediaRepository()->gateEmbeddedIframes(self::IFRAME, self::UID);

		self::assertStringContainsString('iframe-placeholder', $result);
		self::assertStringContainsString('data-host="media.example"', $result);
		self::assertStringContainsString('<template>', $result);
	}

	public function testTrustedIframeIsKept(): void
	{
		DI::pConfig()->set(self::UID, 'system', 'trusted_iframe_hosts', ['media.example']);

		$result = DI::postMediaRepository()->gateEmbeddedIframes(self::IFRAME, self::UID);

		self::assertSame(self::IFRAME, $result);
	}

	public function testTrustedHostIsCaseInsensitive(): void
	{
		DI::pConfig()->set(self::UID, 'system', 'trusted_iframe_hosts', ['Media.Example']);

		$result = DI::postMediaRepository()->gateEmbeddedIframes(self::IFRAME, self::UID);

		self::assertSame(self::IFRAME, $result);
	}

	public function testGuestsAreNeverAsked(): void
	{
		$result = DI::postMediaRepository()->gateEmbeddedIframes(self::IFRAME, 0);

		self::assertSame(self::IFRAME, $result);
	}

	public function testHtmlWithoutIframeIsUntouched(): void
	{
		$html = '<p>No embed here</p>';

		self::assertSame($html, DI::postMediaRepository()->gateEmbeddedIframes($html, self::UID));
	}

	public function testIframeWithoutHostIsGatedWithoutAlways(): void
	{
		$html = str_replace('data-host="media.example"', 'data-host=""', self::IFRAME);

		$result = DI::postMediaRepository()->gateEmbeddedIframes($html, self::UID);

		self::assertStringContainsString('iframe-placeholder', $result);
		self::assertStringNotContainsString('iframePlaceholderAlways', $result);
	}

	public function testIframeWithHostOffersAlways(): void
	{
		$result = DI::postMediaRepository()->gateEmbeddedIframes(self::IFRAME, self::UID);

		self::assertStringContainsString('iframePlaceholderAlways', $result);
		self::assertStringContainsString('data-token="', $result);
	}
}
