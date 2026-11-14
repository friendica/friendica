<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content\Text;

use DOMDocument;
use Friendica\Content\Text\HTML;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HTMLTest extends TestCase
{
	public static function provideTexts(): array
	{
		return [
			'ascii'       => ['Hello world!'],
			'umlauts'     => ['Grüße aus Österreich'],
			'accents'     => ['Voilà un été à Paris'],
			'cyrillic'    => ['Привет мир'],
			'cjk'         => ['日本語のテキスト'],
			'emoji'       => ['Friendica 🎉 federates'],
			'astralPlane' => ['𝔉𝔯𝔦𝔢𝔫𝔡𝔦𝔠𝔞'],
			'withEntity'  => ['Grüße &amp; 🎉'],
		];
	}

	public function testAsciiCharactersAndMarkupAreLeftUntouched(): void
	{
		self::assertSame('<p>Hello &amp; welcome</p>', HTML::toNumericEntities('<p>Hello &amp; welcome</p>'));
	}

	public function testNonAsciiCharactersAreEncodedAsNumericEntities(): void
	{
		self::assertSame('Gr&#252;&#223;e', HTML::toNumericEntities('Grüße'));
	}

	/**
	 * The encoding exists so that "DOMDocument::loadHTML()" - which assumes ISO-8859-1
	 * for markup without a charset declaration - does not mangle UTF-8 input.
	 */
	#[DataProvider('provideTexts')]
	public function testLoadHtmlPreservesTheTextAfterEncoding(string $text): void
	{
		$doc = new DOMDocument();
		$doc->loadHTML(HTML::toNumericEntities('<span>' . $text . '</span>'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		self::assertSame(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $doc->textContent);
	}

	public function testLoadHtmlWouldMangleUtf8WithoutTheEncoding(): void
	{
		$doc = new DOMDocument();
		$doc->loadHTML('<span>Grüße</span>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		self::assertNotSame('Grüße', $doc->textContent);
	}
}
