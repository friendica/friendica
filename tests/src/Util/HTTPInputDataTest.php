<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTest;
use Friendica\Test\Util\HTTPInputDataDouble;
use Friendica\Util\HTTPInputData;

/**
 * Testing HTTPInputData
 *
 * @see	HTTPInputData
 */
class HTTPInputDataTest extends MockedTest
{
	/**
	 * Returns the data stream for the unit test
	 * Each array element of the first hierarchy represents one test run
	 * Each array element of the second hierarchy represents the parameters, passed to the test function
	 *
	 * @return array[]
	 */
	public function dataStream()
	{
		return [
			'multipart' => [
				'contenttype' => 'multipart/form-data;boundary=43395968-f65c-437e-b536-5b33e3e3c7e5;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../datasets/http/multipart.httpinput'),
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
							]
						]
					],
					'files' => []
				]
			],
			'multipart-file' => [
				'contenttype' => 'multipart/form-data;boundary=6d4d5a40-651a-4468-a62e-5a6ca2bf350d;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../datasets/http/multipart-file.httpinput'),
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
							]
						]
					],
					'files' => [
						'avatar' => [
							'name'     => '8ZUCS34Y5XNH',
							'type'     => 'image/png',
							'tmp_name' => '8ZUCS34Y5XNH',
							'error'    => 0,
							'size'     => 349330
						],
						'header' => [
							'name'     => 'V2B6Z1IICGPM',
							'type'     => 'image/png',
							'tmp_name' => 'V2B6Z1IICGPM',
							'error'    => 0,
							'size'     => 1323635
						]
					]
				]
			],
			'form-urlencoded' => [
				'contenttype' => 'application/x-www-form-urlencoded;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../datasets/http/form-urlencoded.httpinput'),
				'expected'    => [
					'variables' => [
						'title' => 'Test2',
					],
					'files' => []
				]
			],
			'form-urlencoded-json' => [
				'contenttype' => 'application/x-www-form-urlencoded;charset=utf8',
				'input'       => file_get_contents(__DIR__ . '/../../datasets/http/form-urlencoded-json.httpinput'),
				'expected'    => [
					'variables' => [
						'media_ids'    => [],
						'sensitive'    => false,
						'status'       => 'Test Status',
						'visibility'   => 'private',
						'spoiler_text' => 'Title'
					],
					'files' => []
				]
			]
		];
	}

	/**
	 * Tests the HTTPInputData::process() method
	 *
	 * @param string $contentType The content typer of the transmitted data
	 * @param string $input       The input, we got from the data stream
	 * @param array  $expected    The expected output
	 *
	 * @dataProvider dataStream
	 * @see HTTPInputData::process()
	 */
	public function testHttpInput(string $contentType, string $input, array $expected)
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
