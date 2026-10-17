<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\View;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Friendica\Render\FriendicaSmarty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Tests escaping in the private message form. */
class PrvMessageTemplateTest extends TestCase
{
	private const XSS_NAME    = '<img src=x onerror=alert(1)>';
	private const XSS_REPLYTO = 'https://evil.example/m/1"><img src=x onerror=alert(2)>';

	private string $workDir;

	protected function setUp(): void
	{
		parent::setUp();

		// FriendicaSmarty resolves template directories from the project root.
		chdir(dirname(__DIR__, 3));

		$this->workDir = sys_get_temp_dir() . '/friendica-tpl-test-' . getmypid();
	}

	protected function tearDown(): void
	{
		if (is_dir($this->workDir)) {
			exec('rm -rf ' . escapeshellarg($this->workDir));
		}

		parent::tearDown();
	}

	/** @return array<string, array{string}> */
	public static function themeProvider(): array
	{
		return [
			'frio' => ['frio'],
			'vier' => ['vier'],
		];
	}

	#[DataProvider('themeProvider')]
	public function testReplyFormEscapesContactNameAndParentUri(string $theme): void
	{
		$html = $this->render($theme, [
			'recipient' => ['name' => self::XSS_NAME, 'id' => 52],
			'replyto'   => self::XSS_REPLYTO,
			'select'    => '',
		]);

		$xpath = $this->xpath($html);

		// Ignore unrelated images in the base template.
		self::assertSame(0, $xpath->query('//img[@src="x"]')->length, 'contact name was rendered as markup');
		self::assertSame(0, $xpath->query('//*[@onerror]')->length, 'an event handler attribute was injected');

		self::assertStringContainsString(htmlspecialchars(self::XSS_NAME, ENT_QUOTES), $html);

		self::assertSame(1, $xpath->query('//input[@name="recipient"]')->length);
		self::assertSame('52', $this->element($xpath, '//input[@name="recipient"]')->getAttribute('value'));

		self::assertSame(1, $xpath->query('//input[@name="replyto"]')->length);
		self::assertSame(self::XSS_REPLYTO, $this->element($xpath, '//input[@name="replyto"]')->getAttribute('value'));
	}

	/** @return array<string, array{string}> */
	public static function displayNameProvider(): array
	{
		return [
			'plain'             => ['Tobias'],
			'two words'         => ['Michael Vogel'],
			'apostrophe'        => ["Sean O'Brien"],
			'ampersand'         => ['Alice & Bob'],
			'double quotes'     => ['Jean-Luc "Q" Picard'],
			'angle bracket art' => ['<3 friendica'],
			'diacritics'        => ['Ana María Ñúñez'],
			'german umlauts'    => ['Jürgen Schrödinger'],
			'cyrillic'          => ['Пользователь Сети'],
			'greek'             => ['Δημήτριος'],
			'cjk'               => ['日本語のユーザー'],
			'korean'            => ['프렌디카 사용자'],
			'rtl arabic'        => ['مستخدم فرينديكا'],
			'rtl hebrew'        => ['משתמש פרנדיקה'],
			'emoji'             => ['🦣 Mastodon Fan 🐘'],
			'anarchy symbol'    => ['Ⓐ anarchist'],
			'zero width joiner' => ['Family 👨‍👩‍👧‍👦'],
			'handle as name'    => ['gargron@mastodon.social'],
			'url as name'       => ['https://friendi.ca/'],
			'bot suffix'        => ['News Bot [bot]'],
			'pronouns in name'  => ['Sam (they/them)'],
			'custom emoji code' => [':verified: Alice'],
			'math symbols'      => ['∀x ∈ fediverse'],
			'long name'         => [str_repeat('Very Long Display Name ', 10)],
			'whitespace padded' => ['  spaced out  '],
		];
	}

	#[DataProvider('displayNameProvider')]
	public function testDisplayNamesSurviveEscaping(string $name): void
	{
		foreach (array_keys(self::themeProvider()) as $theme) {
			$html = $this->render($theme, [
				'recipient' => ['name' => $name, 'id' => 52],
				'replyto'   => 'https://friendica.example/objects/1a2b3c4d-5e6f-7890-abcd-ef1234567890',
				'select'    => '',
			]);

			$label = $this->xpath($html)->query('//div[@id="prvmail-to-label"]')->item(0);
			self::assertNotNull($label, $theme . ': recipient label is missing');
			self::assertStringContainsString($name, $label->textContent, $theme . ': display name was altered');
		}
	}

	/** @return array<string, array{string}> */
	public static function parentUriProvider(): array
	{
		return [
			'friendica object'   => ['https://friendica.example/objects/1a2b3c4d-5e6f-7890-abcd-ef1234567890'],
			'diaspora handle'    => ['alice@diaspora.example:5f4dcc3b5aa765d61d83'],
			'conversation uri'   => ['bob@friendica.example:1a2b3c4d-5e6f-7890-abcd-ef1234567890'],
			'mastodon status'    => ['https://mastodon.social/users/Gargron/statuses/109252195269200000'],
			'diaspora scheme'    => ['diaspora://alice@diaspora.example/conversation/5f4dcc3b5aa765d61d83'],
			'urn uuid'           => ['urn:uuid:1a2b3c4d-5e6f-7890-abcd-ef1234567890'],
			'tag uri'            => ['tag:friendica.example,2026-08:objectId=1:objectType=Mail'],
			'with query string'  => ['https://example.org/objects/abc?context=1&reply=2'],
			'with fragment'      => ['https://example.org/objects/abc#part-2'],
			'explicit port'      => ['https://example.org:8443/objects/abc'],
			'unicode path'       => ['https://präsenz.example/objects/josé'],
			'percent encoded'    => ['https://example.org/objects/%C3%BCber'],
			'apostrophe in path' => ["https://example.org/objects/o'brien"],
		];
	}

	#[DataProvider('parentUriProvider')]
	public function testParentUriRoundTripsUnchanged(string $uri): void
	{
		foreach (array_keys(self::themeProvider()) as $theme) {
			$html = $this->render($theme, [
				'recipient' => ['name' => 'Alice', 'id' => 52],
				'replyto'   => $uri,
				'select'    => '',
			]);

			$xpath = $this->xpath($html);
			self::assertSame(1, $xpath->query('//input[@name="replyto"]')->length, $theme . ': reply-to field is missing');
			self::assertSame(
				$uri,
				$this->element($xpath, '//input[@name="replyto"]')->getAttribute('value'),
				$theme . ': parent uri was altered',
			);
		}
	}

	#[DataProvider('themeProvider')]
	public function testNewMessageFormKeepsRenderedRecipientWidget(string $theme): void
	{
		$html = $this->render($theme, [
			'recipient' => null,
			'replyto'   => '',
			'select'    => '<select name="recipient"><option value="52">Alice</option></select>',
		]);

		$xpath = $this->xpath($html);

		self::assertSame(1, $xpath->query('//select[@name="recipient"]')->length);
		self::assertSame(0, $xpath->query('//input[@name="replyto"]')->length);
	}

	private function render(string $theme, array $vars): string
	{
		$smarty = new FriendicaSmarty($theme, [], $this->workDir, false);

		foreach ($vars as $key => $value) {
			$smarty->assign($key, $value);
		}

		return $smarty->fetch('file:prv_message.tpl');
	}

	private function element(DOMXPath $xpath, string $query): DOMElement
	{
		$node = $xpath->query($query)->item(0);
		if (!$node instanceof DOMElement) {
			self::fail($query . ' did not match an element');
		}

		return $node;
	}

	private function xpath(string $html): DOMXPath
	{
		$doc = new DOMDocument();
		// Prevent DOMDocument from treating non-ASCII names as ISO-8859-1.
		self::assertTrue(@$doc->loadHTML(
			'<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
		));

		return new DOMXPath($doc);
	}
}
