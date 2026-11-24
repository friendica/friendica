<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter;

use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Object\Api\Twitter\User;
use Friendica\Test\ApiTestCase;

final class ContactEndpointTest extends ApiTestCase
{
	public function testIds(): void
	{
		self::assertSame([
			'ids'                 => [],
			'next_cursor'         => -1,
			'next_cursor_str'     => '-1',
			'previous_cursor'     => 0,
			'previous_cursor_str' => '0',
			'total_count'         => 0,
		], ContactEndpointMock::ids([], 0, -1));

		self::assertSame([
			'ids'                 => [3045],
			'next_cursor'         => 0,
			'next_cursor_str'     => '0',
			'previous_cursor'     => 0,
			'previous_cursor_str' => '0',
			'total_count'         => 1,
		], ContactEndpointMock::ids([3045], 1, -1));

		self::assertSame([
			'ids'                 => [3045, 3047],
			'next_cursor'         => 0,
			'next_cursor_str'     => '0',
			'previous_cursor'     => 0,
			'previous_cursor_str' => '0',
			'total_count'         => 2,
		], ContactEndpointMock::ids([3045, 3047], 2, -1));

		// The count is matched by the caller, a partial page keeps the next cursor
		self::assertSame([
			'ids'                 => [3045],
			'next_cursor'         => 3045,
			'next_cursor_str'     => '3045',
			'previous_cursor'     => 0,
			'previous_cursor_str' => '0',
			'total_count'         => 2,
		], ContactEndpointMock::ids([3045], 2, -1, 1));

		// Empty page after a next-cursor only has a previous cursor
		self::assertSame([
			'ids'                 => [],
			'next_cursor'         => 0,
			'next_cursor_str'     => '0',
			'previous_cursor'     => -3045,
			'previous_cursor_str' => '-3045',
			'total_count'         => 2,
		], ContactEndpointMock::ids([], 2, 3045, 1));

		// Empty page on the first page marks the end of the result set
		self::assertSame([
			'ids'                 => [],
			'next_cursor'         => -1,
			'next_cursor_str'     => '-1',
			'previous_cursor'     => 0,
			'previous_cursor_str' => '0',
			'total_count'         => 2,
		], ContactEndpointMock::ids([], 2, -1, 1));
	}

	public function testIdsStringify(): void
	{
		$result = ContactEndpointMock::ids([3045, 3047], 2, -1, ContactEndpoint::DEFAULT_COUNT, true);

		self::assertSame(['3045', '3047'], $result['ids']);

		foreach ($result['ids'] as $id) {
			self::assertIsString($id);
		}
		self::assertSame(0, $result['next_cursor']);
		self::assertSame('0', $result['next_cursor_str']);
		self::assertSame(0, $result['previous_cursor']);
		self::assertSame('0', $result['previous_cursor_str']);
		self::assertSame(2, $result['total_count']);
	}

	public function testIdsPagination(): void
	{
		$firstPage = ContactEndpointMock::ids([3045], 2, -1, 1);

		self::assertSame([
			'ids'                 => [3045],
			'next_cursor'         => 3045,
			'next_cursor_str'     => '3045',
			'previous_cursor'     => 0,
			'previous_cursor_str' => '0',
			'total_count'         => 2,
		], $firstPage);

		$secondPage = ContactEndpointMock::ids([3047], 2, $firstPage['next_cursor'], 1);

		self::assertSame([
			'ids'                 => [3047],
			'next_cursor'         => 3047,
			'next_cursor_str'     => '3047',
			'previous_cursor'     => -3047,
			'previous_cursor_str' => '-3047',
			'total_count'         => 2,
		], $secondPage);

		// An empty page after the last cursor only points back to the previous page
		$emptyNextPage = ContactEndpointMock::ids([], 2, $secondPage['next_cursor'], 1);

		self::assertSame([
			'ids'                 => [],
			'next_cursor'         => 0,
			'next_cursor_str'     => '0',
			'previous_cursor'     => -3047,
			'previous_cursor_str' => '-3047',
			'total_count'         => 2,
		], $emptyNextPage);
	}

	public function testList(): void
	{
		$result = ContactEndpointMock::list([3045], 1, 1042);

		self::assertArrayHasKey('users', $result);
		self::assertContainsOnlyInstancesOf(User::class, $result['users']);
		self::assertCount(1, $result['users']);

		$user = $result['users'][0]->toArray();

		self::assertSame(3045, $user['id']);
		self::assertSame('3045', $user['id_str']);
		self::assertSame('Friend contact', $user['name']);
		self::assertSame('friendcontact', $user['screen_name']);
		self::assertSame('https://friendica.local/profile/friendcontact', $user['url']);
		self::assertSame(1042, $user['uid']);
		self::assertSame(3044, $user['cid']);
		self::assertSame(3045, $user['pid']);
		self::assertSame('DFRN', $user['location']);
		self::assertTrue($user['verified']);
		self::assertFalse($user['following']);

		self::assertSame(0, $result['next_cursor']);
		self::assertSame('0', $result['next_cursor_str']);
		self::assertSame(0, $result['previous_cursor']);
		self::assertSame('0', $result['previous_cursor_str']);
		self::assertSame(1, $result['total_count']);

		$emptyResult = ContactEndpointMock::list([], 0, 1042);

		self::assertSame([], $emptyResult['users']);
		self::assertSame(-1, $emptyResult['next_cursor']);
		self::assertSame('-1', $emptyResult['next_cursor_str']);
		self::assertSame(0, $emptyResult['previous_cursor']);
		self::assertSame('0', $emptyResult['previous_cursor_str']);
		self::assertSame(0, $emptyResult['total_count']);
	}
}
