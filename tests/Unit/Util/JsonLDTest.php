<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Dice\Dice;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
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

	public static function dataSignableDocuments(): array
	{
		return [
			'mastodon-create' => [
				'document' => [
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						[
							'ostatus'          => 'http://ostatus.org#',
							'atomUri'          => 'ostatus:atomUri',
							'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
							'conversation'     => 'ostatus:conversation',
							'sensitive'        => 'as:sensitive',
							'toot'             => 'http://joinmastodon.org/ns#',
							'votersCount'      => 'toot:votersCount',
							'blurhash'         => 'toot:blurhash',
							'focalPoint'       => ['@container' => '@list', '@id' => 'toot:focalPoint'],
							'Hashtag'          => 'as:Hashtag',
							'Emoji'            => 'toot:Emoji',
						],
						'https://w3id.org/security/v1',
					],
					'id'        => 'https://social.example/users/alice/statuses/1/activity',
					'type'      => 'Create',
					'actor'     => 'https://social.example/users/alice',
					'published' => '2026-11-21T05:59:13Z',
					'to'        => ['https://www.w3.org/ns/activitystreams#Public'],
					'cc'        => ['https://social.example/users/alice/followers'],
					'object'    => [
						'id'               => 'https://social.example/users/alice/statuses/1',
						'type'             => 'Note',
						'summary'          => null,
						'inReplyTo'        => null,
						'published'        => '2026-11-21T05:59:13Z',
						'url'              => 'https://social.example/@alice/1',
						'attributedTo'     => 'https://social.example/users/alice',
						'to'               => ['https://www.w3.org/ns/activitystreams#Public'],
						'cc'               => ['https://social.example/users/alice/followers'],
						'sensitive'        => false,
						'atomUri'          => 'https://social.example/users/alice/statuses/1',
						'inReplyToAtomUri' => null,
						'conversation'     => 'tag:social.example,2026-11-21:objectId=1:objectType=Conversation',
						'content'          => '<p>Post with <a href="https://social.example/tags/test" class="mention hashtag" rel="tag">#<span>test</span></a> :blobcat:</p>',
						'contentMap'       => ['en' => '<p>Post with <a href="https://social.example/tags/test" class="mention hashtag" rel="tag">#<span>test</span></a> :blobcat:</p>'],
						'attachment'       => [
							[
								'type'       => 'Document',
								'mediaType'  => 'image/png',
								'url'        => 'https://social.example/media/1.png',
								'name'       => null,
								'blurhash'   => 'UFRfh2~qD%of',
								'focalPoint' => [0.0, 0.0],
								'width'      => 640,
								'height'     => 480,
							],
						],
						'tag' => [
							['type' => 'Hashtag', 'href' => 'https://social.example/tags/test', 'name' => '#test'],
							[
								'id'      => 'https://social.example/emojis/1',
								'type'    => 'Emoji',
								'name'    => ':blobcat:',
								'updated' => '2026-11-01T00:00:00Z',
								'icon'    => ['type' => 'Image', 'mediaType' => 'image/png', 'url' => 'https://social.example/emoji/blobcat.png'],
							],
						],
						'replies' => [
							'id'    => 'https://social.example/users/alice/statuses/1/replies',
							'type'  => 'Collection',
							'first' => [
								'type'   => 'CollectionPage',
								'next'   => 'https://social.example/users/alice/statuses/1/replies?only_other_accounts=true&page=true',
								'partOf' => 'https://social.example/users/alice/statuses/1/replies',
								'items'  => [],
							],
						],
					],
				],
			],
			'friendica-create' => [
				'document' => [
					'@context' => ActivityPub::CONTEXT,
					'id'       => 'https://friendica.example/objects/ec054ce7-636a-c21f-e6b2-926313628263/Create',
					'type'     => 'Create',
					'actor'    => [
						'id'                        => 'https://friendica.example/profile/alice',
						'type'                      => 'Person',
						'url'                       => 'https://friendica.example/~alice',
						'preferredUsername'         => 'alice',
						'name'                      => 'Alice',
						'icon'                      => ['type' => 'Image', 'url' => 'https://friendica.example/photo/contact/1'],
						'manuallyApprovesFollowers' => false,
						'discoverable'              => true,
					],
					'published'  => '2026-10-04T09:44:06Z',
					'instrument' => ['id' => 'https://friendica.example/friendica', 'type' => 'Application', 'name' => 'Friendica', 'url' => 'https://friendica.example'],
					'to'         => ['https://www.w3.org/ns/activitystreams#Public'],
					'cc'         => ['https://friendica.example/followers/alice'],
					'object'     => [
						'id'            => 'https://friendica.example/objects/ec054ce7-636a-c21f-e6b2-926313628263',
						'type'          => 'Note',
						'inReplyTo'     => 'https://social.example/users/bob/statuses/1',
						'diaspora:guid' => 'ec054ce7-636a-c21f-e6b2-926313628263',
						'published'     => '2026-10-04T09:44:06Z',
						'url'           => 'https://friendica.example/display/ec054ce7-636a-c21f-e6b2-926313628263',
						'attributedTo'  => [
							'id'                        => 'https://friendica.example/profile/alice',
							'type'                      => 'Person',
							'url'                       => 'https://friendica.example/~alice',
							'preferredUsername'         => 'alice',
							'name'                      => 'Alice',
							'icon'                      => ['type' => 'Image', 'url' => 'https://friendica.example/photo/contact/1'],
							'manuallyApprovesFollowers' => false,
							'discoverable'              => true,
						],
						'sensitive'         => false,
						'conversation'      => 'https://social.example/contexts/1',
						'content'           => '<p>Reply</p>',
						'contentMap'        => ['en' => '<p>Reply</p>'],
						'source'            => ['content' => 'Reply', 'mediaType' => 'text/bbcode'],
						'interactionPolicy' => ['canQuote' => ['automaticApproval' => ['https://www.w3.org/ns/activitystreams#Public']]],
						'attachment'        => [],
						'tag'               => [['type' => 'Mention', 'href' => 'https://social.example/users/bob', 'name' => '@bob@social.example']],
						'replies'           => ['id' => 'https://friendica.example/objects/ec054ce7-636a-c21f-e6b2-926313628263/replies', 'type' => 'Collection', 'totalItems' => 0],
						'to'                => ['https://www.w3.org/ns/activitystreams#Public'],
						'cc'                => ['https://friendica.example/followers/alice'],
					],
				],
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSignableDocuments')]
	public function testIsSignableDocument(array $document): void
	{
		$this->initSignableEnvironment();

		self::assertTrue(JsonLD::isSignable($document));
	}
}
