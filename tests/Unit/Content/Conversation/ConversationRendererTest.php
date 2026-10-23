<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content\Conversation;

use Friendica\Content\Conversation\ConversationRenderer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * The tree handling behind the targeted insert of a freshly posted reply (Module\Item\Comment).
 *
 * Runs without a database or templates: only the private tree helpers are used, on plain arrays.
 */
class ConversationRendererTest extends TestCase
{
	private ConversationRenderer $renderer;

	protected function setUp(): void
	{
		parent::setUp();

		$this->renderer = (new ReflectionClass(ConversationRenderer::class))->newInstanceWithoutConstructor();
	}

	private function call(string $method, array $args): mixed
	{
		return (new ReflectionMethod(ConversationRenderer::class, $method))->invoke($this->renderer, ...$args);
	}

	/**
	 * Root 1 with the comments 2 (answered by 4, which is answered by 5) and 3.
	 */
	private function thread(): array
	{
		return [
			'uriid'    => 1,
			'children' => [
				[
					'uriid'    => 2,
					'children' => [
						['uriid' => 4, 'children' => [['uriid' => 5, 'children' => []]]],
					],
				],
				['uriid' => 3, 'children' => []],
			],
		];
	}

	public function testFindNodeFindsRootAndNestedNodes(): void
	{
		self::assertSame(1, $this->call('findNodeByUriId', [$this->thread(), 1])['uriid']);
		self::assertSame(3, $this->call('findNodeByUriId', [$this->thread(), 3])['uriid']);
		self::assertSame(5, $this->call('findNodeByUriId', [$this->thread(), 5])['uriid']);
	}

	public function testFindNodeReturnsNullForUnknownNode(): void
	{
		self::assertNull($this->call('findNodeByUriId', [$this->thread(), 99]));
	}

	public function testIsolateReplyKeepsOnlyTheReplyBelowTheRoot(): void
	{
		$result = $this->call('isolateReply', [$this->thread(), 3]);

		self::assertSame(1, $result['uriid']);
		self::assertCount(1, $result['children']);
		self::assertSame(3, $result['children'][0]['uriid']);
		self::assertSame([], $result['children'][0]['children']);
	}

	public function testIsolateReplyDropsTheRepliesOfTheReply(): void
	{
		$result = $this->call('isolateReply', [$this->thread(), 2]);

		self::assertCount(1, $result['children']);
		self::assertSame(2, $result['children'][0]['uriid']);
		self::assertSame([], $result['children'][0]['children']);
	}

	public function testIsolateReplyReturnsNullForUnknownReply(): void
	{
		self::assertNull($this->call('isolateReply', [$this->thread(), 99]));
	}

	public function testSelectInsertNodeIsolatesAReplyToTheThreadStarter(): void
	{
		$comment = ['uri-id' => 3, 'thr-parent-id' => 1, 'parent-uri-id' => 1];

		$result = $this->call('selectInsertNode', [$this->thread(), $comment]);

		self::assertSame(1, $result['uriid']);
		self::assertCount(1, $result['children']);
		self::assertSame(3, $result['children'][0]['uriid']);
	}

	public function testSelectInsertNodeReturnsTheParentSubtreeOfANestedReply(): void
	{
		$comment = ['uri-id' => 5, 'thr-parent-id' => 4, 'parent-uri-id' => 1];

		$result = $this->call('selectInsertNode', [$this->thread(), $comment]);

		self::assertSame(4, $result['uriid']);
		self::assertSame(5, $result['children'][0]['uriid']);
	}

	public function testSelectInsertNodeReturnsNullForUnknownParent(): void
	{
		$comment = ['uri-id' => 8, 'thr-parent-id' => 99, 'parent-uri-id' => 1];

		self::assertNull($this->call('selectInsertNode', [$this->thread(), $comment]));
	}

	public function testIsolateReplyLeavesTheSuppliedThreadUntouched(): void
	{
		$thread = $this->thread();

		$this->call('isolateReply', [$thread, 3]);

		self::assertSame($this->thread(), $thread);
	}
}
