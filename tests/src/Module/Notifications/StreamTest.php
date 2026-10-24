<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Notifications;

use Friendica\DI;
use Friendica\Model\Post\UserNotification;
use Friendica\Model\Verb;
use Friendica\Module\Notifications\Stream;
use Friendica\Protocol\Activity;
use Friendica\Test\ApiTestCase;
use Friendica\Test\FixtureTestCase;

class StreamTest extends FixtureTestCase
{
	private const UID = ApiTestCase::SELF_USER['id'];

	// Public contacts of the fixture
	private const ACTOR_1 = 43;
	private const ACTOR_2 = 45;
	private const ACTOR_3 = 47;

	/**
	 * Adds a notification and returns its id
	 */
	private function addNotification(string $verb, int $type, int $actorId, ?int $targetUriId, array $fields = []): int
	{
		DI::dba()->insert('notification', array_merge([
			'uid'           => self::UID,
			'vid'           => Verb::getID($verb),
			'type'          => $type,
			'actor-id'      => $actorId,
			'target-uri-id' => $targetUriId,
			'parent-uri-id' => $targetUriId,
			'created'       => '2026-10-01 12:00:00',
		], $fields));

		return DI::dba()->lastInsertId();
	}

	/**
	 * The ids of the notifications that are selected with the condition of the stream
	 *
	 * @return int[]
	 */
	private function getIds(?int $afterId = null, int $uid = self::UID): array
	{
		$stream = (new \ReflectionClass(Stream::class))->newInstanceWithoutConstructor();
		$method = new \ReflectionMethod(Stream::class, 'getCondition');

		$notifications = DI::notification()->selectByBoundaries(
			$method->invoke($stream, $uid, $afterId),
			['order' => ['id' => true]],
			null,
			null,
			100,
		);

		$ids = array_map(fn ($notification) => $notification->id, $notifications->getArrayCopy());
		sort($ids);

		return $ids;
	}

	public function testRenderableTypesAreSelected(): void
	{
		$expected = [
			$this->addNotification(Activity::POST, UserNotification::TYPE_EXPLICIT_TAGGED, self::ACTOR_1, 1),
			$this->addNotification(Activity::POST, UserNotification::TYPE_IMPLICIT_TAGGED, self::ACTOR_1, 2),
			$this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 3),
			$this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_THREAD_COMMENT, self::ACTOR_1, 4),
			$this->addNotification(Activity::POST, UserNotification::TYPE_THREAD_COMMENT, self::ACTOR_1, 5),
			$this->addNotification(Activity::POST, UserNotification::TYPE_SHARED, self::ACTOR_2, 1),
			$this->addNotification(Activity::LIKE, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_2, 2),
			$this->addNotification(Activity::ANNOUNCE, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_2, 3),
			$this->addNotification(Activity::FOLLOW, UserNotification::TYPE_NONE, self::ACTOR_3, null),
		];
		sort($expected);

		self::assertSame($expected, $this->getIds());
	}

	public function testTypesWithoutRepresentationAreNotSelected(): void
	{
		$this->addNotification(Activity::POST, UserNotification::TYPE_COMMENT_PARTICIPATION, self::ACTOR_1, 1);
		$this->addNotification(Activity::POST, UserNotification::TYPE_ACTIVITY_PARTICIPATION, self::ACTOR_1, 2);
		$this->addNotification(Activity::POST, UserNotification::TYPE_FOLLOW, self::ACTOR_1, 3);
		$this->addNotification(Activity::POST, UserNotification::TYPE_QUOTED, self::ACTOR_1, 4);
		// Without the follow verb a notification without a type has nothing to show
		$this->addNotification(Activity::POST, UserNotification::TYPE_NONE, self::ACTOR_1, 5);

		self::assertSame([], $this->getIds());
	}

	public function testDismissedNotificationsAreNotSelected(): void
	{
		$this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 1, ['dismissed' => true]);
		$shown = $this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 2);

		self::assertSame([$shown], $this->getIds());
	}

	public function testNotificationsOfOtherUsersAreNotSelected(): void
	{
		$shown = $this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 1);

		self::assertSame([$shown], $this->getIds());
		self::assertSame([], $this->getIds(null, 43));
	}

	public function testNotificationsOfBlockedActorsAreNotSelected(): void
	{
		DI::dba()->insert('user-contact', ['cid' => self::ACTOR_1, 'uid' => self::UID, 'blocked' => true]);
		DI::dba()->insert('user-contact', ['cid' => self::ACTOR_2, 'uid' => self::UID, 'is-blocked' => true]);

		$this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 1);
		$this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_2, 2);
		$shown = $this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_3, 3);

		self::assertSame([$shown], $this->getIds());
	}

	public function testOnlyNewerNotificationsAreSelectedAfterAnId(): void
	{
		$old    = $this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 1);
		$newer  = $this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 2);
		$newest = $this->addNotification(Activity::POST, UserNotification::TYPE_DIRECT_COMMENT, self::ACTOR_1, 3);

		self::assertSame([$old, $newer, $newest], $this->getIds());
		self::assertSame([$newer, $newest], $this->getIds($old));
		self::assertSame([], $this->getIds($newest));
	}
}
