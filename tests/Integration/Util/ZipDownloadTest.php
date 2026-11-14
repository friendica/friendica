<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Util;

use Friendica\Util\ZipDownload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use ZipArchive;

#[RequiresPhpExtension('zip')]
class ZipDownloadTest extends TestCase
{
	private string $zipfile = '';

	protected function tearDown(): void
	{
		if ($this->zipfile !== '' && file_exists($this->zipfile)) {
			unlink($this->zipfile);
		}
	}

	public static function dataNames(): array
	{
		return [
			'distinct names' => [
				'input'    => ['a.jpg', 'b.jpg'],
				'expected' => ['a.jpg', 'b.jpg'],
			],
			'duplicate names' => [
				'input'    => ['img.jpg', 'img.jpg', 'img.jpg'],
				'expected' => ['img.jpg', 'img-1.jpg', 'img-2.jpg'],
			],
			'renamed duplicate collides with an existing name' => [
				'input'    => ['img-1.jpg', 'img.jpg', 'img.jpg'],
				'expected' => ['img-1.jpg', 'img.jpg', 'img-2.jpg'],
			],
			'no extension' => [
				'input'    => ['readme', 'readme'],
				'expected' => ['readme', 'readme-1'],
			],
			'path parts are stripped' => [
				'input'    => ['../../evil.jpg', 'dir\\evil.jpg'],
				'expected' => ['evil.jpg', 'evil-1.jpg'],
			],
			'empty names' => [
				'input'    => ['', ' ', '..'],
				'expected' => ['file', 'file-1', 'file-2'],
			],
		];
	}

	#[DataProvider('dataNames')]
	public function testEntryNamesAreUnique(array $input, array $expected): void
	{
		$zip = new ZipDownload(sys_get_temp_dir());

		$stored = [];
		foreach ($input as $index => $name) {
			$stored[] = $zip->add($name, 'data-' . $index);
		}

		$this->zipfile = $zip->close();

		self::assertSame($expected, $stored);
		self::assertSame($expected, $this->readEntries($this->zipfile));
	}

	public function testEntriesKeepTheirContent(): void
	{
		$zip = new ZipDownload(sys_get_temp_dir());
		$zip->add('img.jpg', 'first');
		$zip->add('img.jpg', 'second');

		$this->zipfile = $zip->close();

		$archive = new ZipArchive();
		self::assertTrue($archive->open($this->zipfile));
		self::assertSame('first', $archive->getFromName('img.jpg'));
		self::assertSame('second', $archive->getFromName('img-1.jpg'));
		$archive->close();
	}

	public function testCloseRemovesBufferedEntries(): void
	{
		$tempPath = sys_get_temp_dir() . '/zipdownload-' . bin2hex(random_bytes(4));
		mkdir($tempPath);

		$zip = new ZipDownload($tempPath);
		$zip->add('a.jpg', 'a');
		$zip->add('b.jpg', 'b');

		$this->zipfile = $zip->close();

		self::assertSame([basename($this->zipfile)], array_values(array_diff(scandir($tempPath), ['.', '..'])));

		unlink($this->zipfile);
		rmdir($tempPath);
	}

	/** @return string[] */
	private function readEntries(string $file): array
	{
		$archive = new ZipArchive();
		self::assertTrue($archive->open($file));

		$names = [];
		for ($i = 0; $i < $archive->numFiles; $i++) {
			$names[] = $archive->getNameIndex($i);
		}
		$archive->close();

		return $names;
	}
}
