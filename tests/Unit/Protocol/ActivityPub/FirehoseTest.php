<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Protocol\ActivityPub;

use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Item as ContentItem;
use Friendica\Network\HTTPClient\Client\HttpClient;
use Friendica\Protocol\ActivityPub\Firehose;
use Friendica\Test\ProtocolTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class FirehoseTest extends ProtocolTestCase
{
	private const AUTHOR_ID = 23;

	private function createFirehose(
		?LoggerInterface $logger = null,
		?ContentItem $contentItem = null,
		?UserDefinedChannel $userDefinedChannel = null,
	) {
		$firehose = Mockery::mock(Firehose::class, [
			$logger ?? Mockery::mock(LoggerInterface::class),
			Mockery::mock(HttpClient::class),
			$contentItem        ?? Mockery::mock(ContentItem::class),
			$userDefinedChannel ?? Mockery::mock(UserDefinedChannel::class),
		])->makePartial();
		$firehose->shouldAllowMockingProtectedMethods();

		return $firehose;
	}

	private static function foundAuthor(): array
	{
		return ['id' => self::AUTHOR_ID, 'unsearchable' => false];
	}

	public static function dataSolicitedUpdates(): array
	{
		$status        = self::loadProtocolJsonFixture('mastodon/streaming/status-with-content-warning');
		$reblogWrapper = self::loadProtocolJsonFixture('mastodon/streaming/status-reblog-wrapper');

		return [
			'mastodon streaming update split over chunks' => [
				'chunks' => [
					"event: update\n",
					'data: ' . json_encode($status) . "\n\n",
				],
				'expectedData'       => $status,
				'expectedContactUrl' => 'https://example.org/users/ada',
				'expectedTags'       => ['friendica'],
				'expectedContent'    => "Content warning\nPlain body",
				'expectedUrl'        => 'https://example.org/users/ada/statuses/111111111111111111',
				'expectedLanguages'  => ['de'],
			],
			'mastodon streaming reblog dispatches nested status' => [
				'chunks'             => [self::serverSentEvent('update', $reblogWrapper)],
				'expectedData'       => $reblogWrapper['reblog'],
				'expectedContactUrl' => 'https://remote.example/@ben',
				'expectedTags'       => [],
				'expectedContent'    => 'Repeated body',
				'expectedUrl'        => 'https://remote.example/users/ben/statuses/222222222222222222',
				'expectedLanguages'  => [],
			],
		];
	}

	/**
	 * Test that solicited FediBuzz update events are normalized and dispatched.
	 */
	#[DataProvider('dataSolicitedUpdates')]
	public function testProcessStreamDispatchesSolicitedUpdate(
		array $chunks,
		array $expectedData,
		string $expectedContactUrl,
		array $expectedTags,
		string $expectedContent,
		string $expectedUrl,
		array $expectedLanguages,
	): void {
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->shouldReceive('info')->with('Matched post', ['url' => $expectedUrl])->once();
		$contentItem = Mockery::mock(ContentItem::class);
		$contentItem->shouldNotReceive('getLanguageArray');
		$userDefinedChannel = Mockery::mock(UserDefinedChannel::class);
		$userDefinedChannel->shouldNotReceive('match');

		$firehose = $this->createFirehose($logger, $contentItem, $userDefinedChannel);
		$firehose->shouldReceive('getContactByUrl')->with($expectedContactUrl, ['id', 'unsearchable'])->andReturn(self::foundAuthor())->once();
		$firehose->shouldReceive('isSolicitedPost')
			->with($expectedTags, $expectedContent, self::AUTHOR_ID, $expectedUrl, $expectedLanguages)
			->andReturn(true)
			->once();
		$firehose->shouldReceive('handlePost')->with($expectedUrl, $expectedData)->once();

		$firehose->processStream($this->streamForChunks($chunks));
		$this->addToAssertionCount(1);
	}

	public static function dataChannelMatches(): array
	{
		$status = self::loadProtocolJsonFixture('mastodon/streaming/status-acct-fallback');

		return [
			'mastodon status without uri uses url and account acct fallback' => [
				'chunks'             => [self::serverSentEvent('update', $status)],
				'expectedData'       => $status,
				'expectedContactUrl' => 'carol@example.net',
				'expectedTags'       => ['topic'],
				'expectedContent'    => 'Channel body',
				'expectedUrl'        => 'https://example.net/@carol/333333333333333333',
				'detectedLanguages'  => ['en' => 1],
				'expectedLanguage'   => 'en',
			],
		];
	}

	/**
	 * Test that non-solicited update events are dispatched when a user-defined channel matches.
	 */
	#[DataProvider('dataChannelMatches')]
	public function testProcessStreamDispatchesUserDefinedChannelMatch(
		array $chunks,
		array $expectedData,
		string $expectedContactUrl,
		array $expectedTags,
		string $expectedContent,
		string $expectedUrl,
		array $detectedLanguages,
		string $expectedLanguage,
	): void {
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->shouldReceive('info')->with('Matched channel', ['url' => $expectedUrl])->once();
		$contentItem = Mockery::mock(ContentItem::class);
		$contentItem->shouldReceive('getLanguageArray')
			->with($expectedContent, 1, 0, self::AUTHOR_ID)
			->andReturn($detectedLanguages)
			->once();
		$userDefinedChannel = Mockery::mock(UserDefinedChannel::class);
		$userDefinedChannel->shouldReceive('match')
			->with('search text', $expectedLanguage)
			->andReturn(true)
			->once();

		$firehose = $this->createFirehose($logger, $contentItem, $userDefinedChannel);
		$firehose->shouldReceive('getContactByUrl')->with($expectedContactUrl, ['id', 'unsearchable'])->andReturn(self::foundAuthor())->once();
		$firehose->shouldReceive('isSolicitedPost')
			->with($expectedTags, $expectedContent, self::AUTHOR_ID, $expectedUrl, [])
			->andReturn(false)
			->once();
		$firehose->shouldReceive('getSearchTextForActivity')->with($expectedContent, self::AUTHOR_ID, $expectedTags)->andReturn('search text')->once();
		$firehose->shouldReceive('handlePost')->with($expectedUrl, $expectedData)->once();

		$firehose->processStream($this->streamForChunks($chunks));
		$this->addToAssertionCount(1);
	}

	public static function dataSkippedAuthors(): array
	{
		$status = self::loadProtocolJsonFixture('mastodon/streaming/status-no-match');

		return [
			'unknown author is skipped' => [
				'chunks'             => [self::serverSentEvent('update', $status)],
				'expectedContactUrl' => 'https://other.example/users/eve',
				'author'             => [],
			],
			'unsearchable author is skipped' => [
				'chunks'             => [self::serverSentEvent('update', $status)],
				'expectedContactUrl' => 'https://other.example/users/eve',
				'author'             => ['id' => self::AUTHOR_ID, 'unsearchable' => true],
			],
		];
	}

	/**
	 * Test that posts from unknown or unsearchable authors are dropped without dispatching.
	 */
	#[DataProvider('dataSkippedAuthors')]
	public function testProcessStreamSkipsUnknownOrUnsearchableAuthor(
		array $chunks,
		string $expectedContactUrl,
		array $author,
	): void {
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->shouldReceive('info')->with('Skipping unsearchable or unknown author', Mockery::any())->once();

		$firehose = $this->createFirehose($logger);
		$firehose->shouldReceive('getContactByUrl')->with($expectedContactUrl, ['id', 'unsearchable'])->andReturn($author)->once();
		$firehose->shouldNotReceive('isSolicitedPost');
		$firehose->shouldNotReceive('handlePost');

		$firehose->processStream($this->streamForChunks($chunks));
		$this->addToAssertionCount(1);
	}

	public static function dataIgnoredEvents(): array
	{
		$notification = self::loadProtocolJsonFixture('mastodon/streaming/notification-mention');

		return [
			'mastodon notification event is ignored' => [
				'chunks' => [self::serverSentEvent('notification', $notification)],
			],
			'malformed update JSON is ignored' => [
				'chunks' => [self::serverSentEvent('update', '{invalid-json}')],
			],
			'mixed ignored stream events are ignored' => [
				'chunks' => [
					self::serverSentEvent('notification', $notification),
					self::serverSentEvent('update', '{invalid-json}'),
				],
			],
		];
	}

	/**
	 * Test that unsupported or malformed stream events do not dispatch posts.
	 */
	#[DataProvider('dataIgnoredEvents')]
	public function testProcessStreamIgnoresEvents(array $chunks): void
	{
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->shouldNotReceive('info');
		$contentItem = Mockery::mock(ContentItem::class);
		$contentItem->shouldNotReceive('getLanguageArray');
		$userDefinedChannel = Mockery::mock(UserDefinedChannel::class);
		$userDefinedChannel->shouldNotReceive('match');

		$firehose = $this->createFirehose($logger, $contentItem, $userDefinedChannel);
		$firehose->shouldNotReceive('getContactByUrl');
		$firehose->shouldNotReceive('isSolicitedPost');
		$firehose->shouldNotReceive('handlePost');

		$firehose->processStream($this->streamForChunks($chunks));
		$this->addToAssertionCount(1);
	}

	public static function dataNoMatch(): array
	{
		$status = self::loadProtocolJsonFixture('mastodon/streaming/status-no-match');

		return [
			'post matches neither solicited nor channel' => [
				'chunks'             => [self::serverSentEvent('update', $status)],
				'expectedContactUrl' => 'https://other.example/users/eve',
				'expectedTags'       => [],
				'expectedContent'    => 'Uninteresting post',
				'expectedUrl'        => 'https://other.example/users/eve/statuses/666666666666666666',
				'detectedLanguages'  => [],
				'expectedLanguage'   => '',
			],
		];
	}

	/**
	 * Test that posts matching neither solicited-post rules nor any user-defined channel are silently dropped.
	 */
	#[DataProvider('dataNoMatch')]
	public function testProcessStreamSkipsUnmatchedUpdate(
		array $chunks,
		string $expectedContactUrl,
		array $expectedTags,
		string $expectedContent,
		string $expectedUrl,
		array $detectedLanguages,
		string $expectedLanguage,
	): void {
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->shouldNotReceive('info');
		$contentItem = Mockery::mock(ContentItem::class);
		$contentItem->shouldReceive('getLanguageArray')
			->with($expectedContent, 1, 0, self::AUTHOR_ID)
			->andReturn($detectedLanguages)
			->once();
		$userDefinedChannel = Mockery::mock(UserDefinedChannel::class);
		$userDefinedChannel->shouldReceive('match')
			->with('search text', $expectedLanguage)
			->andReturn(false)
			->once();

		$firehose = $this->createFirehose($logger, $contentItem, $userDefinedChannel);
		$firehose->shouldReceive('getContactByUrl')->with($expectedContactUrl, ['id', 'unsearchable'])->andReturn(self::foundAuthor())->once();
		$firehose->shouldReceive('isSolicitedPost')
			->with($expectedTags, $expectedContent, self::AUTHOR_ID, $expectedUrl, [])
			->andReturn(false)
			->once();
		$firehose->shouldReceive('getSearchTextForActivity')->with($expectedContent, self::AUTHOR_ID, $expectedTags)->andReturn('search text')->once();
		$firehose->shouldNotReceive('handlePost');

		$firehose->processStream($this->streamForChunks($chunks));
		$this->addToAssertionCount(1);
	}

	public static function dataMultilineData(): array
	{
		$status = self::loadProtocolJsonFixture('mastodon/streaming/status-no-match');
		$json   = json_encode($status);
		$half   = (int) (strlen($json) / 2);

		return [
			'multi-line data: payload is reassembled correctly' => [
				'chunks' => [
					"event: update\n"
					. 'data: ' . substr($json, 0, $half) . "\n"
					. 'data: ' . substr($json, $half) . "\n\n",
				],
				'expectedContactUrl' => 'https://other.example/users/eve',
				'expectedTags'       => [],
				'expectedContent'    => 'Uninteresting post',
				'expectedUrl'        => 'https://other.example/users/eve/statuses/666666666666666666',
			],
		];
	}

	/**
	 * Test that a JSON payload split across multiple data: lines is correctly reassembled.
	 */
	#[DataProvider('dataMultilineData')]
	public function testProcessStreamReassemblesMultilineData(
		array $chunks,
		string $expectedContactUrl,
		array $expectedTags,
		string $expectedContent,
		string $expectedUrl,
	): void {
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->shouldReceive('info')->with('Matched post', ['url' => $expectedUrl])->once();
		$contentItem = Mockery::mock(ContentItem::class);
		$contentItem->shouldNotReceive('getLanguageArray');
		$userDefinedChannel = Mockery::mock(UserDefinedChannel::class);
		$userDefinedChannel->shouldNotReceive('match');

		$firehose = $this->createFirehose($logger, $contentItem, $userDefinedChannel);
		$firehose->shouldReceive('getContactByUrl')->with($expectedContactUrl, ['id', 'unsearchable'])->andReturn(self::foundAuthor())->once();
		$firehose->shouldReceive('isSolicitedPost')
			->with($expectedTags, $expectedContent, self::AUTHOR_ID, $expectedUrl, [])
			->andReturn(true)
			->once();
		$firehose->shouldReceive('handlePost')->with($expectedUrl, Mockery::type('array'))->once();

		$firehose->processStream($this->streamForChunks($chunks));
		$this->addToAssertionCount(1);
	}

	private function streamForChunks(array $chunks): StreamInterface
	{
		$stream = Mockery::mock(StreamInterface::class);
		$stream->shouldReceive('eof')->andReturn(...array_merge(array_fill(0, count($chunks), false), [true]));
		$stream->shouldReceive('read')->with(8192)->andReturn(...$chunks);

		return $stream; // @phpstan-ignore return.type
	}
}
