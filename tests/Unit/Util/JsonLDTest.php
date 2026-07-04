<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Friendica\Util\JsonLD;
use PHPUnit\Framework\TestCase;

/**
 * JsonLD utility test class
 */
class JsonLDTest extends TestCase
{
	public function testFetchElementArrayNotFound(): void
	{
		$object = [];

		$data = JsonLD::fetchElementArray($object, 'field');
		self::assertNull($data);
	}

	public function testFetchElementArrayFoundEmptyArray(): void
	{
		$object = ['field' => []];

		$data = JsonLD::fetchElementArray($object, 'field');
		self::assertSame([[]], $data);
	}

	public function testFetchElementArrayFoundID(): void
	{
		$object = ['field' => ['value1', ['@id' => 'value2'], ['@id' => 'value3']]];

		$data = JsonLD::fetchElementArray($object, 'field', '@id');
		self::assertSame(['value1', 'value2', 'value3'], $data);
	}

	public function testFetchElementArrayFoundID2(): void
	{
		$object = ['field' => [['subfield11' => 'value11', 'subfield12' => 'value12'],
			['subfield21' => 'value21', 'subfield22' => 'value22'],
			'value3', ['@id' => 'value4', 'subfield42' => 'value42']]];

		$data = JsonLD::fetchElementArray($object, 'field', '@id');
		self::assertSame(['value3', 'value4'], $data);
	}

	public function testFetchElementArrayFoundArrays(): void
	{
		$object = ['field' => [['subfield11' => 'value11', 'subfield12' => 'value12'],
			['subfield21' => 'value21', 'subfield22' => 'value22']]];

		$expect = [['subfield11' => 'value11', 'subfield12' => 'value12'],
			['subfield21' => 'value21', 'subfield22' => 'value22']];

		$data = JsonLD::fetchElementArray($object, 'field');
		self::assertSame($expect, $data);
	}

	public function testFetchElementArrayTypeValue(): void
	{
		$object = ['field' => [['subfield11' => 'value11', 'subfield12' => 'value12'],
			['subfield21' => 'value21', 'subfield22' => 'value22']]];

		$expect = [['subfield11' => 'value11', 'subfield12' => 'value12']];

		$data = JsonLD::fetchElementArray($object, 'field', null, 'subfield11', 'value11');
		self::assertSame($expect, $data);
	}

	public function testFetchElementNotFound(): void
	{
		$object = [];

		$data = JsonLD::fetchElement($object, 'field');
		self::assertNull($data);
	}

	public function testFetchElementFound(): void
	{
		$object = ['field' => 'value'];

		$data = JsonLD::fetchElement($object, 'field');
		self::assertSame('value', $data);
	}

	public function testFetchElementFoundEmptyString(): void
	{
		$object = ['field' => ''];

		$data = JsonLD::fetchElement($object, 'field');
		self::assertSame('', $data);
	}

	public function testFetchElementKeyFoundEmptyArray(): void
	{
		$object = ['field' => ['content' => []]];

		$data = JsonLD::fetchElement($object, 'field', 'content');
		self::assertSame([], $data);
	}

	public function testFetchElementFoundID(): void
	{
		$object = ['field' => ['field2' => 'value2', '@id' => 'value', 'field3' => 'value3']];

		$data = JsonLD::fetchElement($object, 'field');
		self::assertSame('value', $data);
	}

	public function testFetchElementType(): void
	{
		$object = ['source' => ['content' => 'body', 'mediaType' => 'text/bbcode']];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType', 'text/bbcode');
		self::assertSame('body', $data);
	}

	public function testFetchElementTypeValueNotFound(): void
	{
		$object = ['source' => ['content' => 'body', 'mediaType' => 'text/html']];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType', 'text/bbcode');
		self::assertNull($data);
	}

	public function testFetchElementTypeNotFound(): void
	{
		$object = ['source' => ['content' => 'body', 'mediaType' => 'text/html']];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType2', 'text/html');
		self::assertNull($data);
	}

	public function testFetchElementKeyWithoutType(): void
	{
		$object = ['source' => ['content' => 'body', 'mediaType' => 'text/bbcode']];

		$data = JsonLD::fetchElement($object, 'source', 'content');
		self::assertSame('body', $data);
	}

	public function testFetchElementTypeArray(): void
	{
		$object = ['source' => [['content' => 'body2', 'mediaType' => 'text/html'],
			['content' => 'body', 'mediaType' => 'text/bbcode']]];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType', 'text/bbcode');
		self::assertSame('body', $data);
	}

	public function testFetchElementTypeValueArrayNotFound(): void
	{
		$object = ['source' => [['content' => 'body2', 'mediaType' => 'text/html'],
			['content' => 'body', 'mediaType' => 'text/bbcode']]];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType', 'text/markdown');
		self::assertNull($data);
	}

	public function testFetchElementTypeArrayNotFound(): void
	{
		$object = ['source' => [['content' => 'body2', 'mediaType' => 'text/html'],
			['content' => 'body', 'mediaType' => 'text/bbcode']]];

		$data = JsonLD::fetchElement($object, 'source', 'content', 'mediaType2', 'text/bbcode');
		self::assertNull($data);
	}
}
