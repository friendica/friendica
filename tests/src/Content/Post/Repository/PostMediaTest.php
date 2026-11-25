<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Post\Repository;

use Friendica\Content\Post\Collection\PostMedias;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Test\FixtureTestCase;
use Friendica\Test\src\Content\Post\PostMediaRowTrait;

class PostMediaTest extends FixtureTestCase
{
	use PostMediaRowTrait;

	private const UID = 42;

	private const IFRAME = '<span class="embedded-media"><iframe class="embed" src="https://media.example/player" style="" height="480px" width="620px" data-host="media.example" data-preview=""></iframe></span>';

	protected function setUp(): void
	{
		parent::setUp();
		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);
		DI::config()->set('system', 'url', 'https://friendica.local');
		DI::pConfig()->delete(self::UID, 'system', 'trusted_iframe_hosts');
		DI::pConfig()->delete(self::UID, 'system', 'embed_external_media');
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

	private function disableExternalMedia(): void
	{
		DI::pConfig()->set(self::UID, 'system', 'embed_external_media', false);
	}

	public function testExternalMediaIsEmbeddedByDefault(): void
	{
		self::assertTrue(DI::postMediaRepository()->mayEmbedUrl(self::UID, 'https://remote.example/video.mp4'));
		self::assertTrue(DI::postMediaRepository()->mayEmbedUrl(self::UID, '<iframe src="https://remote.example/"></iframe>', true));
	}

	public function testLocalUrlMayBeEmbeddedWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();

		self::assertTrue(DI::postMediaRepository()->mayEmbedUrl(self::UID, DI::baseUrl() . '/photo/video.mp4'));
	}

	public function testExternalUrlMayNotBeEmbeddedWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();

		self::assertFalse(DI::postMediaRepository()->mayEmbedUrl(self::UID, 'https://remote.example/video.mp4'));
		self::assertFalse(DI::postMediaRepository()->mayEmbedUrl(self::UID, ''));
	}

	public function testEmbedHtmlMayNotBeEmbeddedWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();

		// Embed HTML can reference arbitrary hosts, even when it contains a local URL
		self::assertFalse(DI::postMediaRepository()->mayEmbedUrl(self::UID, '<iframe src="' . DI::baseUrl() . '/player"></iframe>', true));
	}

	public function testTrustedLocalIframeIsKeptWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();
		$html = str_replace('media.example', 'friendica.local', self::IFRAME);
		DI::pConfig()->set(self::UID, 'system', 'trusted_iframe_hosts', ['friendica.local']);

		self::assertSame($html, DI::postMediaRepository()->gateEmbeddedIframes($html, self::UID));
	}

	public function testVideoIsShownAsAttachmentWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();
		$video = $this->createMedia(['url' => 'https://remote.example/clips/holiday.mp4', 'type' => PostMedia::TYPE_VIDEO, 'mimetype' => 'video/mp4']);

		$result = DI::postMediaRepository()->getVideoAttachment($video, self::UID);

		self::assertStringContainsString('holiday.mp4', $result);
		self::assertStringNotContainsString('<video', $result);
	}

	public function testAudioIsShownAsAttachmentWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();
		$audio = $this->createMedia(['url' => 'https://remote.example/music/song.mp3', 'type' => PostMedia::TYPE_AUDIO, 'mimetype' => 'audio/mpeg']);

		$result = DI::postMediaRepository()->getAudioAttachment($audio, self::UID);

		self::assertStringContainsString('song.mp3', $result);
		self::assertStringNotContainsString('<audio', $result);
	}

	public function testPlayerMediaIsReplacedByLinkInEmbedWhenExternalMediaIsDisabled(): void
	{
		$this->disableExternalMedia();
		$url = 'https://remote.example/watch/42';
		DBA::insert('post-media', [
			'uri-id'        => 1,
			'url'           => $url,
			'type'          => PostMedia::TYPE_HTML,
			'name'          => 'A page with player',
			'player-url'    => 'https://remote.example/player/42',
			'player-height' => 360,
			'player-width'  => 640,
		]);

		$result = DI::postMediaRepository()->addEmbed('<a class="embed" href="' . $url . '">link</a>', self::UID, 1);

		self::assertStringContainsString($url, $result);
		self::assertStringNotContainsString('<iframe', $result);
		self::assertStringNotContainsString('iframe-placeholder', $result);
	}

	public function testFileAttachmentNamePrefersDescription(): void
	{
		$media = $this->createMedia([
			'url'         => 'https://remote.example/files/9f3a7c1e5b.pdf',
			'type'        => PostMedia::TYPE_DOCUMENT,
			'mimetype'    => 'application/pdf',
			'name'        => '9f3a7c1e5b.pdf',
			'description' => "Agenda of\nthe   meeting",
		]);

		$data = DI::postMediaRepository()->getFileAttachmentData($media, 'https://remote.example/files/9f3a7c1e5b.pdf', self::UID);

		self::assertSame('Agenda of the meeting', $data['name']);
	}

	public function testFileAttachmentNameShortensLongDescription(): void
	{
		$media = $this->createMedia(['type' => PostMedia::TYPE_DOCUMENT, 'mimetype' => 'application/pdf', 'description' => str_repeat('word ', 60)]);

		$data = DI::postMediaRepository()->getFileAttachmentData($media, 'https://remote.example/file.pdf', self::UID);

		self::assertLessThanOrEqual(100, mb_strlen($data['name']));
		self::assertStringEndsWith('…', $data['name']);
	}

	public function testFileAttachmentNameFallsBackToNameAndFileName(): void
	{
		$named = $this->createMedia(['url' => 'https://remote.example/files/a.pdf', 'type' => PostMedia::TYPE_DOCUMENT, 'mimetype' => 'application/pdf', 'name' => 'Report']);
		$plain = $this->createMedia(['url' => 'https://remote.example/files/a.pdf', 'type' => PostMedia::TYPE_DOCUMENT, 'mimetype' => 'application/pdf']);

		self::assertSame('Report', DI::postMediaRepository()->getFileAttachmentData($named, 'https://remote.example/files/a.pdf', self::UID)['name']);
		self::assertSame('a.pdf', DI::postMediaRepository()->getFileAttachmentData($plain, 'https://remote.example/files/a.pdf', self::UID)['name']);
	}

	public function testFileAttachmentOffersPlayerOnlyForPlayableMedia(): void
	{
		$video = $this->createMedia(['id' => 5, 'type' => PostMedia::TYPE_VIDEO, 'mimetype' => 'video/mp4']);
		$pdf   = $this->createMedia(['id' => 6, 'type' => PostMedia::TYPE_DOCUMENT, 'mimetype' => 'application/pdf']);

		self::assertSame(DI::baseUrl() . '/media/player/5', DI::postMediaRepository()->getFileAttachmentData($video, 'https://remote.example/a.mp4', self::UID)['player']);
		self::assertSame('', DI::postMediaRepository()->getFileAttachmentData($pdf, 'https://remote.example/a.pdf', self::UID)['player']);
		self::assertSame('', DI::postMediaRepository()->getFileAttachmentData($video, 'https://remote.example/a.mp4', 0)['player']);
	}

	private function createPlayerVideo(array $fields = []): PostMedia
	{
		return $this->createMedia(array_merge([
			'url'           => 'https://peertube.example/videos/embed/abc',
			'type'          => PostMedia::TYPE_VIDEO,
			'player-url'    => 'https://peertube.example/videos/embed/abc',
			'player-height' => 360,
		], $fields));
	}

	public function testVideoCardIsCreatedForVideoWithPlayer(): void
	{
		$card = DI::postMediaRepository()->getVideoCard($this->createPlayerVideo(), new PostMedias(), ['plink' => 'https://peertube.example/w/abc', 'title' => 'A video', 'author-name' => 'Author', 'author-link' => 'https://peertube.example/a/author']);

		self::assertNotNull($card);
		self::assertSame(PostMedia::TYPE_HTML, $card->type);
		self::assertSame('https://peertube.example/w/abc', (string) $card->url);
		self::assertSame('A video', $card->name);
	}

	public function testVideoCardUsesExistingLinkOfThePost(): void
	{
		$link = $this->createMedia(['url' => 'https://peertube.example/w/abc', 'type' => PostMedia::TYPE_HTML, 'name' => 'Existing']);

		$card = DI::postMediaRepository()->getVideoCard($this->createPlayerVideo(), new PostMedias([$link]), ['plink' => 'https://peertube.example/w/abc']);

		self::assertSame($link, $card);
	}

	public function testNoVideoCardWithoutPlayerOrExternalPostLink(): void
	{
		$repository = DI::postMediaRepository();
		$item       = ['plink' => 'https://peertube.example/w/abc'];

		self::assertNull($repository->getVideoCard($this->createMedia(['type' => PostMedia::TYPE_VIDEO]), new PostMedias(), $item), 'video without player');
		self::assertNull($repository->getVideoCard($this->createPlayerVideo(['type' => PostMedia::TYPE_IMAGE]), new PostMedias(), $item), 'no video');
		self::assertNull($repository->getVideoCard($this->createPlayerVideo(), new PostMedias(), []), 'no plink');
		self::assertNull($repository->getVideoCard($this->createPlayerVideo(), new PostMedias(), ['plink' => DI::baseUrl() . '/display/abc']), 'local plink');
	}
}
