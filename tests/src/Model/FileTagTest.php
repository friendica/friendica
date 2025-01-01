<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\Model\FileTag;
use PHPUnit\Framework\TestCase;

class FileTagTest extends TestCase
{
	public function dataArrayToFile()
	{
		return [
			'list-category' => [
				'array' => ['1', '2', '3', 'a', 'b', 'c'],
				'type'  => 'category',
				'file'  => '<1><2><3><a><b><c>',
			],
			'list-file' => [
				'array' => ['1', '2', '3', 'a', 'b', 'c'],
				'type'  => 'file',
				'file'  => '[1][2][3][a][b][c]',
			],
			'chevron-category' => [
				'array' => ['Left < Center > Right'],
				'type'  => 'category',
				'file'  => '<Left %3c Center %3e Right>',
			],
			'bracket-file' => [
				'array' => ['Glass [half-full]'],
				'type'  => 'file',
				'file'  => '[Glass %5bhalf-full%5d]',
			],
			/** @see https://github.com/friendica/friendica/issues/7171 */
			'bug-7171-category' => [
				'array' => ['Science, Health, Medicine'],
				'type'  => 'category',
				'file'  => '<Science, Health, Medicine>',
			],
			'bug-7171-file' => [
				'array' => ['Science, Health, Medicine'],
				'type'  => 'file',
				'file'  => '[Science, Health, Medicine]',
			],
		];
	}

	/**
	 * Test convert saved folders arrays to a file/category field
	 * @dataProvider dataArrayToFile
	 *
	 * @param array  $array
	 * @param string $type
	 * @param string $file
	 */
	public function testArrayToFile(array $array, string $type, string $file)
	{
		self::assertEquals($file, FileTag::arrayToFile($array, $type));
	}

	public function dataFileToArray()
	{
		return [
			'list-category' => [
				'file'  => '<1><2><3><a><b><c>',
				'type'  => 'category',
				'array' => ['1', '2', '3', 'a', 'b', 'c'],
			],
			'list-file' => [
				'file'  => '[1][2][3][a][b][c]',
				'type'  => 'file',
				'array' => ['1', '2', '3', 'a', 'b', 'c'],
			],
			'combinedlist-category' => [
				'file'  => '[1][2][3]<a><b><c>',
				'type'  => 'category',
				'array' => ['a', 'b', 'c'],
			],
			'combinedlist-file' => [
				'file'  => '[1][2][3]<a><b><c>',
				'type'  => 'file',
				'array' => ['1', '2', '3'],
			],
			'chevron-category' => [
				'file'  => '<Left %3c Center %3e Right>',
				'type'  => 'category',
				'array' => ['Left < Center > Right'],
			],
			'bracket-file' => [
				'file'  => '[Glass %5bhalf-full%5d]',
				'type'  => 'file',
				'array' => ['Glass [half-full]'],
			],
			/** @see https://github.com/friendica/friendica/issues/7171 */
			'bug-7171-category' => [
				'file'  => '<Science, Health, Medicine>',
				'type'  => 'category',
				'array' => ['Science, Health, Medicine'],
			],
			'bug-7171-file' => [
				'file'  => '[Science, Health, Medicine]',
				'type'  => 'file',
				'array' => ['Science, Health, Medicine'],
			],
		];
	}

	/**
	 * Test convert different saved folders to a file/category field
	 * @dataProvider dataFileToArray
	 *
	 * @param string $file
	 * @param string $type
	 * @param array  $array
	 */
	public function testFileToArray(string $file, string $type, array $array)
	{
		self::assertEquals($array, FileTag::fileToArray($file, $type));
	}
}
