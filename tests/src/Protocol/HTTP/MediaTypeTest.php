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

namespace Friendica\Test\src\Protocol\HTTP;

use Friendica\Protocol\HTTP\MediaType;

class MediaTypeTest extends \PHPUnit\Framework\TestCase
{
	public function dataValid(): array
	{
		return [
			'HTML UTF-8' => [
				'expected'     => new MediaType('text', 'html', ['charset' => 'utf-8']),
				'content-type' => 'text/html; charset=utf-8',
			],
			'HTML Northern Europe' => [
				'expected'     => new MediaType('text', 'html', ['charset' => 'ISO-8859-4']),
				'content-type' => 'text/html; charset=ISO-8859-4',
			],
			'multipart/form-data' => [
				'expected'     => new MediaType('multipart', 'form-data', ['boundary' => '---------------------------974767299852498929531610575']),
				'content-type' => 'multipart/form-data; boundary=---------------------------974767299852498929531610575',
			],
			'Multiple parameters' => [
				'expected'     => new MediaType('application', 'octet-stream', ['charset' => 'ISO-8859-4', 'another' => 'parameter']),
				'content-type' => 'application/octet-stream; charset=ISO-8859-4 ; another=parameter',
			],
			'No parameters' => [
				'expected'     => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'content-type' => 'application/vnd.adobe.air-application-installer-package+zip',
			],
			'No parameters colon' => [
				'expected'     => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'content-type' => 'application/vnd.adobe.air-application-installer-package+zip;',
			],
			'No parameters space colon' => [
				'expected'     => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'content-type' => 'application/vnd.adobe.air-application-installer-package+zip ;',
			],
			'No parameters space colon space' => [
				'expected'     => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'content-type' => 'application/vnd.adobe.air-application-installer-package+zip ; ',
			],
			'Parameter quoted string' => [
				'expected'     => new MediaType('text', 'html', ['parameter' => 'Quoted string with a space and a "double-quote"']),
				'content-type' => 'text/html; parameter="Quoted string with a space and a \"double-quote\""',
			]
		];
	}

	/**
	 * @dataProvider dataValid
	 *
	 * @param MediaType $expected
	 * @param string    $contentType
	 * @return void
	 */
	public function testValid(MediaType $expected, string $contentType)
	{
		$this->assertEquals($expected, MediaType::fromContentType($contentType));
	}

	public function dataInvalid(): array
	{
		return [
			'no slash'                  => ['application'],
			'two slashes'               => ['application/octet/stream'],
			'parameter no value'        => ['application/octet-stream ; parameter'],
			'parameter too many values' => ['application/octet-stream ; parameter=value1=value2'],
			'type non token'            => ['appli"cation/octet-stream'],
			'subtype non token'         => ['application/octet\-stream'],
			'parameter name non token'  => ['application/octet-stream; para"meter=value'],
			'parameter value invalid'   => ['application/octet-stream; parameter="value"value'],
		];
	}

	/**
	 * @dataProvider dataInvalid
	 *
	 * @param string $contentType
	 * @return void
	 */
	public function testInvalid(string $contentType)
	{
		$this->expectException(\InvalidArgumentException::class);

		MediaType::fromContentType($contentType);
	}

	public function dataToString(): array
	{
		return [
			'HTML UTF-8' => [
				'content-type' => 'text/html; charset=utf-8',
				'mediaType'    => new MediaType('text', 'html', ['charset' => 'utf-8']),
			],
			'HTML Northern Europe' => [
				'expected'  => 'text/html; charset=ISO-8859-4',
				'mediaType' => new MediaType('text', 'html', ['charset' => 'ISO-8859-4']),
			],
			'multipart/form-data' => [
				'expected'  => 'multipart/form-data; boundary=---------------------------974767299852498929531610575',
				'mediaType' => new MediaType('multipart', 'form-data', ['boundary' => '---------------------------974767299852498929531610575']),
			],
			'Multiple parameters' => [
				'expected'  => 'application/octet-stream; charset=ISO-8859-4; another=parameter',
				'mediaType' => new MediaType('application', 'octet-stream', ['charset' => 'ISO-8859-4', 'another' => 'parameter']),
			],
			'No parameters' => [
				'expected'  => 'application/vnd.adobe.air-application-installer-package+zip',
				'mediaType' => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
			],
			'Parameter quoted string' => [
				'expected'  => 'text/html; parameter="Quoted string with a space and a \"double-quote\""',
				'mediaType' => new MediaType('text', 'html', ['parameter' => 'Quoted string with a space and a "double-quote"']),
			],
		];
	}

	/**
	 * @dataProvider dataToString
	 *
	 * @param string    $expected
	 * @param MediaType $mediaType
	 * @return void
	 */
	public function testToString(string $expected, MediaType $mediaType)
	{
		$this->assertEquals($expected, $mediaType->__toString());
	}
}
