<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Dice\Dice;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\DI;
use Friendica\Util\BasePath;
use Friendica\Util\JsonLD;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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

	private function initSignableEnvironment(): void
	{
		$cache = \Mockery::mock(ICanCache::class);
		$cache->shouldReceive('get')->andReturn(null);
		$cache->shouldReceive('set')->andReturn(true);

		$basePath = \Mockery::mock(BasePath::class);
		$basePath->shouldReceive('getPath')->andReturn(dirname(__DIR__, 3));

		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(LoggerInterface::class)->andReturn(new NullLogger());
		$dice->shouldReceive('create')->with(ICanCache::class)->andReturn($cache);
		$dice->shouldReceive('create')->with(BasePath::class)->andReturn($basePath);

		DI::init($dice, true);
	}

	private function getSignedActivity(): array
	{
		return [
			'@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1', ['sensitive' => 'as:sensitive']],
			'id'       => 'https://social.example/users/alice/statuses/1/activity',
			'type'     => 'Create',
			'actor'    => 'https://social.example/users/alice',
			'to'       => ['https://www.w3.org/ns/activitystreams#Public'],
			'object'   => [
				'id'           => 'https://social.example/users/alice/statuses/1',
				'type'         => 'Note',
				'attributedTo' => 'https://social.example/users/alice',
				'summary'      => 'Content warning',
				'sensitive'    => true,
				'content'      => '<p>Honest post</p>',
				'contentMap'   => ['en' => '<p>Honest post</p>'],
				'replies'      => ['type' => 'Collection', 'first' => ['type' => 'CollectionPage', 'items' => []]],
			],
		];
	}

	public static function dataSignable(): array
	{
		return [
			'honest' => [
				'changes'  => [],
				'signable' => true,
			],
			'scheme-less-ids' => [
				'changes' => ['id' => 'social.example/users/alice/statuses/2/activity', 'object' => [
					'id'      => 'social.example/users/alice/statuses/2',
					'type'    => 'Note',
					'content' => '<p>Forged post</p>',
				]],
				'signable' => false,
			],
			'scheme-less-object' => [
				'changes'  => ['type' => 'Delete', 'object' => 'social.example/users/alice/statuses/1'],
				'signable' => false,
			],
			'blank-node-id' => [
				'changes'  => ['id' => '_:b0'],
				'signable' => false,
			],
			'undefined-term' => [
				'changes'  => ['identifier' => 'en'],
				'signable' => false,
			],
			'undefined-type' => [
				'changes'  => ['type' => 'Review'],
				'signable' => false,
			],
			'invalid-language' => [
				'changes'  => ['contentMap' => ['not a language' => 'Forged post']],
				'signable' => false,
			],
			'differently-described-node' => [
				'changes'  => ['instrument' => ['id' => 'https://social.example/users/alice/statuses/1', 'content' => 'Additional content']],
				'signable' => false,
			],
			'identically-described-node' => [
				'changes'  => ['target' => ['id' => 'https://social.example/users/alice', 'type' => 'Person'], 'actor' => ['id' => 'https://social.example/users/alice', 'type' => 'Person']],
				'signable' => true,
			],
			'changed-by-fixes' => [
				'changes'  => ['@context' => ['https://www.w3.org/ns/activitystreams', ['sensitive' => 'as:sensitive']], 'owner' => 'https://social.example/users/alice'],
				'signable' => false,
			],
			'suspicious-command' => [
				'changes'  => ['@graph' => []],
				'signable' => false,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSignable')]
	public function testIsSignable(array $changes, bool $signable): void
	{
		$this->initSignableEnvironment();

		self::assertSame($signable, JsonLD::isSignable(array_merge($this->getSignedActivity(), $changes)));
	}
}
