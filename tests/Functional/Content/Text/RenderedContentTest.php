<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Content\Text;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\RenderedContent;
use Friendica\Content\Text\Repository\RenderedContent as RenderedContentRepository;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Worker\ExpireRenderedContent;
use PHPUnit\Framework\MockObject\Stub;

class RenderedContentTest extends FixtureTestCase
{
	// Post of the fixture in api.fixture.php
	private const URI_ID = 1;
	private const TEXT   = 'Some [b]bold[/b] text';
	private const MODE   = BBCode::MASTODON_API;

	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'ignore_cache', false);
		DI::dba()->delete('rendered-content', ['uri-id' => self::URI_ID]);
	}

	/**
	 * @param int[] $translationCounts Values the translation counter returns one after the other
	 * @param int[] $dateFormatCounts  Values the date format counter returns one after the other
	 */
	private function create(array $translationCounts = [0, 0], array $dateFormatCounts = [0, 0], string $lang = 'en'): RenderedContent
	{
		/** @var L10n&Stub $l10n */
		$l10n = $this->createStub(L10n::class);
		$l10n->method('getCurrentLang')->willReturn($lang);
		$l10n->method('getTranslationCount')->willReturnOnConsecutiveCalls(...array_values($translationCounts));
		$l10n->method('getDateFormatCount')->willReturnOnConsecutiveCalls(...array_values($dateFormatCounts));

		return new RenderedContent(new RenderedContentRepository(DI::dba()), DI::config(), $l10n);
	}

	private function rows(): array
	{
		return DI::dba()->selectToArray('rendered-content', [], ['uri-id' => self::URI_ID], ['order' => ['lang']]);
	}

	public function testMissIsRenderedAndStored(): void
	{
		$html = $this->create()->convert(self::URI_ID, self::TEXT, self::MODE);

		self::assertSame(BBCode::convertForUriId(self::URI_ID, self::TEXT, self::MODE), $html);

		$rows = $this->rows();
		self::assertCount(1, $rows);
		self::assertSame($html, $rows[0]['html']);
		self::assertSame('', $rows[0]['lang']);
		self::assertSame(self::MODE, (int) $rows[0]['mode']);
	}

	public function testHitIsReadFromTheStoredEntry(): void
	{
		$this->create()->convert(self::URI_ID, self::TEXT, self::MODE);
		DI::dba()->update('rendered-content', ['html' => '<p>stored</p>'], ['uri-id' => self::URI_ID]);

		self::assertSame('<p>stored</p>', $this->create()->convert(self::URI_ID, self::TEXT, self::MODE));
	}

	public function testHitUpdatesTheLastAccessOncePerDay(): void
	{
		$this->create()->convert(self::URI_ID, self::TEXT, self::MODE);
		DI::dba()->update('rendered-content', ['last-access' => '2020-01-01'], ['uri-id' => self::URI_ID]);

		$this->create()->convert(self::URI_ID, self::TEXT, self::MODE);

		self::assertSame(DateTimeFormat::utcNow('Y-m-d'), $this->rows()[0]['last-access']);
	}

	public function testChangedTextReplacesTheStoredEntry(): void
	{
		$this->create()->convert(self::URI_ID, self::TEXT, self::MODE);
		$html = $this->create()->convert(self::URI_ID, 'Another [i]text[/i]', self::MODE);

		$rows = $this->rows();
		self::assertCount(1, $rows);
		self::assertSame($html, $rows[0]['html']);
		self::assertSame(hash('md5', BBCode::VERSION . '::Another [i]text[/i]'), $rows[0]['hash']);
	}

	public function testTranslatedTextIsStoredPerLanguage(): void
	{
		// The translation counter changes while rendering
		$this->create([0, 1], [0, 0], 'de')->convert(self::URI_ID, self::TEXT, self::MODE);

		$rows = $this->rows();
		self::assertCount(1, $rows);
		self::assertSame('de', $rows[0]['lang']);

		DI::dba()->update('rendered-content', ['html' => '<p>de</p>'], ['uri-id' => self::URI_ID]);

		// The entry is only used for the same language
		self::assertSame('<p>de</p>', $this->create([0, 0], [0, 0], 'de')->convert(self::URI_ID, self::TEXT, self::MODE));
		self::assertNotSame('<p>de</p>', $this->create([0, 1], [0, 0], 'fr')->convert(self::URI_ID, self::TEXT, self::MODE));
		self::assertCount(2, $this->rows());
	}

	public function testEntryWithoutLanguageIsUsedForEveryLanguage(): void
	{
		$this->create()->convert(self::URI_ID, self::TEXT, self::MODE);
		DI::dba()->update('rendered-content', ['html' => '<p>any</p>'], ['uri-id' => self::URI_ID]);

		self::assertSame('<p>any</p>', $this->create([0, 0], [0, 0], 'fr')->convert(self::URI_ID, self::TEXT, self::MODE));
	}

	public function testFormattedDatesAreNotStored(): void
	{
		$this->create([0, 0], [0, 1])->convert(self::URI_ID, self::TEXT, self::MODE);

		self::assertSame([], $this->rows());
	}

	public function testIgnoreCacheNeitherReadsNorWrites(): void
	{
		DI::config()->set('system', 'ignore_cache', true);

		$html = $this->create()->convert(self::URI_ID, self::TEXT, self::MODE);

		self::assertSame(BBCode::convertForUriId(self::URI_ID, self::TEXT, self::MODE), $html);
		self::assertSame([], $this->rows());
	}

	public function testModesThatAreNotCachedAreNotStored(): void
	{
		$this->create()->convert(self::URI_ID, self::TEXT, BBCode::ACTIVITYPUB);
		$this->create()->convert(0, self::TEXT, self::MODE);
		$this->create()->convert(self::URI_ID, '', self::MODE);

		self::assertSame([], $this->rows());
	}

	public function testExpiryRemovesOnlyEntriesNotAccessedRecently(): void
	{
		$old    = DateTimeFormat::utc('now - ' . (ExpireRenderedContent::EXPIRE_DAYS + 1) . ' days', 'Y-m-d');
		$recent = DateTimeFormat::utc('now - ' . (ExpireRenderedContent::EXPIRE_DAYS - 1) . ' days', 'Y-m-d');

		DI::dba()->insert('rendered-content', ['uri-id' => self::URI_ID, 'mode' => self::MODE, 'lang' => '', 'hash' => 'a', 'html' => 'old', 'last-access' => $old]);
		DI::dba()->insert('rendered-content', ['uri-id' => self::URI_ID, 'mode' => BBCode::EXTERNAL, 'lang' => '', 'hash' => 'b', 'html' => 'recent', 'last-access' => $recent]);

		ExpireRenderedContent::execute();

		$rows = $this->rows();
		self::assertCount(1, $rows);
		self::assertSame('recent', $rows[0]['html']);
	}
}
