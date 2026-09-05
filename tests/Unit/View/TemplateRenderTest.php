<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\View;

use DOMElement;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Network\Entity\MimeType;
use Friendica\Test\TemplateTestCase;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders templates and asserts on the resulting markup.
 *
 * Regression targets: the findings of PR #16047 (media browser lost its alt texts,
 * block element inside the alt-text <button>) - both were found by hand on a local
 * Docker stack.
 */
class TemplateRenderTest extends TemplateTestCase
{
	/**
	 * @return array<string, mixed>
	 */
	private static function browserVars(): array
	{
		return [
			// Mirrors Friendica\Module\Media\Photo\Browser::mapFiles():
			// [link, filename, thumbnail, description]
			'$files' => [
				['https://example.com/photos/alice/image/abc', 'described.jpg', 'https://example.com/photo/abc-2.jpg', 'A cat on a keyboard'],
				['https://example.com/photos/alice/image/def', 'plain.jpg', 'https://example.com/photo/def-2.jpg', ''],
			],
			'$folders'              => false,
			'$path'                 => ['alice' => 'Alice'],
			'$upload'               => 'Upload',
			'$wait'                 => 'Please wait',
			'$cancel'               => 'Cancel',
			'$folder_text'          => 'Photos',
			'$files_text'           => 'Files',
			'$aria_close'           => 'Close',
			'$aria_breadcrumb'      => 'Breadcrumb',
			'$aria_mode_switch'     => 'Switch between photo and attachment mode',
			'$aria_album_nav'       => 'Album navigation',
			'$aria_browser_content' => 'Browser content',
		];
	}

	#[DataProvider('themeProvider')]
	public function testMediaBrowserMarkupIsValid(string $theme): void
	{
		$this->assertTemplateRendersValidMarkup('media/browser.tpl', self::browserVars(), $theme);
	}

	/**
	 * The file name is printed right below the thumbnail, so a picture without a
	 * description is redundant to its own caption - and a file name is not a description.
	 */
	#[DataProvider('themeProvider')]
	public function testMediaBrowserLeavesTheAltTextEmptyWithoutDescription(string $theme): void
	{
		$html = $this->renderTemplate('media/browser.tpl', self::browserVars(), $theme);

		$images = $this->elements($html, '//div[@class="photo-album-image-wrapper"]//img');

		self::assertSame('A cat on a keyboard', $images[0]->getAttribute('alt'), 'the description is the alt text');
		self::assertSame('', $images[1]->getAttribute('alt'), 'no description means decorative, not the file name');
	}

	/**
	 * Every theme must describe the same picture the same way.
	 *
	 * This is an invariant, not a pinned string: it stays valid however the browser gets
	 * restyled, and it catches the "fixed in frio, still broken in vier" class of bug that
	 * #15529/#15928 were about - without anyone having to decide up front which alt text
	 * is the correct one.
	 */
	public function testMediaBrowserAltTextsAreIdenticalAcrossThemes(): void
	{
		$altTexts = [];

		foreach (array_keys(self::themeProvider()) as $theme) {
			$html = $this->renderTemplate('media/browser.tpl', self::browserVars(), $theme);

			$altTexts[$theme] = array_map(
				fn (DOMElement $image): string => $image->getAttribute('alt'),
				$this->elements($html, '//div[@class="photo-album-image-wrapper"]//img'),
			);
		}

		self::assertSame(
			$altTexts['frio'],
			$altTexts['vier'],
			"The themes render different alt texts for the same picture.\n"
			. 'Compare view/templates/media/browser.tpl with view/theme/frio/templates/media/browser.tpl.',
		);
	}

	/**
	 * Built from the real entity instead of a hand-written array: if the template's data
	 * contract changes, this test fails instead of silently drifting out of sync.
	 */
	private static function postMediaImage(?string $description): PostMedia
	{
		return new PostMedia(
			23,
			new Uri('https://example.com/photo/abc-2.jpg'),
			PostMedia::TYPE_IMAGE,
			new MimeType('image', 'jpeg'),
			null,
			800,
			600,
			null,
			new Uri('https://example.com/photo/abc-1.jpg'),
			400,
			300,
			$description,
		);
	}

	#[DataProvider('themeProvider')]
	public function testSingleImageWithDescriptionIsValidMarkup(string $theme): void
	{
		$html = $this->assertTemplateRendersValidMarkup('content/image/single.tpl', [
			'$image' => self::postMediaImage('A cat on a keyboard'),
		], $theme);

		self::assertSame('A cat on a keyboard', $this->element($html, '//img')->getAttribute('alt'));
	}

	/**
	 * An image without a description must still render an alt attribute - an empty one,
	 * which is how HTML marks a picture as decorative.
	 */
	#[DataProvider('themeProvider')]
	public function testSingleImageWithoutDescriptionStillHasAltAttribute(string $theme): void
	{
		$html = $this->renderTemplate('content/image/single.tpl', [
			'$image' => self::postMediaImage(null),
		], $theme);

		$image = $this->element($html, '//img');

		self::assertTrue($image->hasAttribute('alt'));
		self::assertSame('', $image->getAttribute('alt'));
	}
}
