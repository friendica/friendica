<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\Model\GServer;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class GServerTest extends \PHPUnit\Framework\TestCase
{
	public static function dataCleanUri(): array
	{
		return [
			'full-monty' => [
				'expected' => new Uri('https://example.com/path'),
				'dirtyUri' => new Uri('https://user:password@example.com/path?query=string#fragment'),
			],
			'index.php' => [
				'expected' => new Uri('https://example.com'),
				'dirtyUri' => new Uri('https://example.com/index.php'),
			],
			'index.php-2' => [
				'expected' => new Uri('https://example.com/path/to/resource'),
				'dirtyUri' => new Uri('https://example.com/index.php/path/to/resource'),
			],
			'index.php-path' => [
				'expected' => new Uri('https://example.com/path/to'),
				'dirtyUri' => new Uri('https://example.com/path/to/index.php'),
			],
			'index.php-path-2' => [
				'expected' => new Uri('https://example.com/path/to/path/to/resource'),
				'dirtyUri' => new Uri('https://example.com/path/to/index.php/path/to/resource'),
			],
			'index.php-slash' => [
				'expected' => new Uri('https://example.com'),
				'dirtyUri' => new Uri('https://example.com/index.php/'),
			],
			'index.php-slash-2' => [
				'expected' => new Uri('https://example.com/path/to/resource'),
				'dirtyUri' => new Uri('https://example.com/index.php/path/to/resource/'),
			],
		];
	}

	/**
	 *
	 * @param UriInterface $expected
	 * @param UriInterface $dirtyUri
	 * @return void
	 * @throws \Exception
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataCleanUri')]
	public function testCleanUri(UriInterface $expected, UriInterface $dirtyUri): void
	{
		$this->assertEquals($expected, GServer::cleanUri($dirtyUri));
	}
}
