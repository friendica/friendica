<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\HTTPInputDataDouble;

/**
 * Testing HTTPInputData
 *
 * @see	HTTPInputData
 */
class HTTPInputDataTest extends MockedTestCase
{
	/**
	 * Returns the data stream for the unit test
	 * Each array element of the first hierarchy represents one test run
	 * Each array element of the second hierarchy represents the parameters, passed to the test function
	 *
	 * @return array[]
	 */
	public static function dataStream()
	{
		return [
			'multipart' => [
				'contentType' => 'multipart/form-data;boundary=43395968-f65c-437e-b536-5b33e3e3c7e5;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../Fixtures/http/multipart.httpinput'),
				'expected'    => [
					'variables' => [
						'display_name'      => 'User Name',
						'note'              => 'About me',
						'locked'            => 'false',
						'fields_attributes' => [
							0 => [
								'name'  => 'variable 1',
								'value' => 'value 1',
							],
							1 => [
								'name'  => 'variable 2',
								'value' => 'value 2',
							],
						],
					],
					'files' => [],
				],
			],
			'multipart-file' => [
				'contentType' => 'multipart/form-data;boundary=6d4d5a40-651a-4468-a62e-5a6ca2bf350d;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../Fixtures/http/multipart-file.httpinput'),
				'expected'    => [
					'variables' => [
						'display_name'      => 'Vorname Nachname',
						'note'              => 'About me',
						'fields_attributes' => [
							0 => [
								'name'  => 'variable 1',
								'value' => 'value 1',
							],
							1 => [
								'name'  => 'variable 2',
								'value' => 'value 2',
							],
						],
					],
					'files' => [
						'avatar' => [
							'name'     => '8ZUCS34Y5XNH',
							'type'     => 'image/png',
							'tmp_name' => '8ZUCS34Y5XNH',
							'error'    => 0,
							'size'     => 349330,
						],
						'header' => [
							'name'     => 'V2B6Z1IICGPM',
							'type'     => 'image/png',
							'tmp_name' => 'V2B6Z1IICGPM',
							'error'    => 0,
							'size'     => 1323635,
						],
					],
				],
			],
			'form-urlencoded' => [
				'contentType' => 'application/x-www-form-urlencoded;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../Fixtures/http/form-urlencoded.httpinput'),
				'expected'    => [
					'variables' => [
						'title' => 'Test2',
					],
					'files' => [],
				],
			],
			'form-urlencoded-json' => [
				'contentType' => 'application/x-www-form-urlencoded;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../Fixtures/http/form-urlencoded-json.httpinput'),
				'expected'    => [
					'variables' => [
						'media_ids'    => [],
						'sensitive'    => false,
						'status'       => 'Test Status',
						'visibility'   => 'private',
						'spoiler_text' => 'Title',
					],
					'files' => [],
				],
			],
		];
	}

	/**
	 * Tests the HTTPInputData::process() method
	 *
	 * @param string $contentType The content typer of the transmitted data
	 * @param string $input       The input, we got from the data stream
	 * @param array  $expected    The expected output
	 *
	 * @see HTTPInputData::process()
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataStream')]
	public function testHttpInput(string $contentType, string $input, array $expected): void
	{
		$httpInput = new HTTPInputDataDouble(['CONTENT_TYPE' => $contentType]);
		$httpInput->setPhpInputContent($input);

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $input);
		rewind($stream);

		$httpInput->setPhpInputStream($stream);
		$output = $httpInput->process();
		$this->assertEqualsCanonicalizing($expected, $output);
	}
}
