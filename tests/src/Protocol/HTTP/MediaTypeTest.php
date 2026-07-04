<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Protocol\HTTP;

use Friendica\Protocol\HTTP\MediaType;

class MediaTypeTest extends \PHPUnit\Framework\TestCase
{
	public static function dataValid(): array
	{
		return [
			'HTML UTF-8' => [
				'expected'    => new MediaType('text', 'html', ['charset' => 'utf-8']),
				'contentType' => 'text/html; charset=utf-8',
			],
			'HTML Northern Europe' => [
				'expected'    => new MediaType('text', 'html', ['charset' => 'ISO-8859-4']),
				'contentType' => 'text/html; charset=ISO-8859-4',
			],
			'multipart/form-data' => [
				'expected'    => new MediaType('multipart', 'form-data', ['boundary' => '---------------------------974767299852498929531610575']),
				'contentType' => 'multipart/form-data; boundary=---------------------------974767299852498929531610575',
			],
			'Multiple parameters' => [
				'expected'    => new MediaType('application', 'octet-stream', ['charset' => 'ISO-8859-4', 'another' => 'parameter']),
				'contentType' => 'application/octet-stream; charset=ISO-8859-4 ; another=parameter',
			],
			'No parameters' => [
				'expected'    => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'contentType' => 'application/vnd.adobe.air-application-installer-package+zip',
			],
			'No parameters colon' => [
				'expected'    => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'contentType' => 'application/vnd.adobe.air-application-installer-package+zip;',
			],
			'No parameters space colon' => [
				'expected'    => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'contentType' => 'application/vnd.adobe.air-application-installer-package+zip ;',
			],
			'No parameters space colon space' => [
				'expected'    => new MediaType('application', 'vnd.adobe.air-application-installer-package+zip'),
				'contentType' => 'application/vnd.adobe.air-application-installer-package+zip ; ',
			],
			'Parameter quoted string' => [
				'expected'    => new MediaType('text', 'html', ['parameter' => 'Quoted string with a space and a "double-quote"']),
				'contentType' => 'text/html; parameter="Quoted string with a space and a \"double-quote\""',
			],
		];
	}

	/**
	 *
	 * @param MediaType $expected
	 * @param string    $contentType
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataValid')]
	public function testValid(MediaType $expected, string $contentType): void
	{
		$this->assertEquals($expected, MediaType::fromContentType($contentType));
	}

	public static function dataInvalid(): array
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
	 *
	 * @param string $contentType
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataInvalid')]
	public function testInvalid(string $contentType): void
	{
		$this->expectException(\InvalidArgumentException::class);

		MediaType::fromContentType($contentType);
	}

	public static function dataToString(): array
	{
		return [
			'HTML UTF-8' => [
				'expected'  => 'text/html; charset=utf-8',
				'mediaType' => new MediaType('text', 'html', ['charset' => 'utf-8']),
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
	 *
	 * @param string    $expected
	 * @param MediaType $mediaType
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataToString')]
	public function testToString(string $expected, MediaType $mediaType): void
	{
		$this->assertEquals($expected, $mediaType->__toString());
	}
}
