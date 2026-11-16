<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Contact\LocalRelationship\Entity;

use Friendica\Contact\LocalRelationship\Entity\LocalRelationship;
use Friendica\Model\Contact;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocalRelationshipTest extends TestCase
{
	private const UNKNOWN = 9;

	/**
	 * @return array<string, array{method: string, before: int, after: int}>
	 */
	public static function provideRelationChanges(): array
	{
		$expected = [
			'addFollow' => [
				Contact::NOTHING  => Contact::SHARING,
				Contact::FOLLOWER => Contact::FRIEND,
				Contact::SHARING  => Contact::SHARING,
				Contact::FRIEND   => Contact::FRIEND,
				Contact::SELF     => Contact::SHARING,
				self::UNKNOWN     => Contact::SHARING,
			],
			'removeFollow' => [
				Contact::NOTHING  => Contact::NOTHING,
				Contact::FOLLOWER => Contact::FOLLOWER,
				Contact::SHARING  => Contact::NOTHING,
				Contact::FRIEND   => Contact::FOLLOWER,
				Contact::SELF     => Contact::NOTHING,
				self::UNKNOWN     => Contact::NOTHING,
			],
			'addFollower' => [
				Contact::NOTHING  => Contact::FOLLOWER,
				Contact::FOLLOWER => Contact::FOLLOWER,
				Contact::SHARING  => Contact::FRIEND,
				Contact::FRIEND   => Contact::FRIEND,
				Contact::SELF     => Contact::FOLLOWER,
				self::UNKNOWN     => Contact::FOLLOWER,
			],
			'removeFollower' => [
				Contact::NOTHING  => Contact::NOTHING,
				Contact::FOLLOWER => Contact::NOTHING,
				Contact::SHARING  => Contact::SHARING,
				Contact::FRIEND   => Contact::SHARING,
				Contact::SELF     => Contact::NOTHING,
				self::UNKNOWN     => Contact::NOTHING,
			],
		];

		$data = [];
		foreach ($expected as $method => $changes) {
			foreach ($changes as $before => $after) {
				$data[$method . ' from ' . $before] = ['method' => $method, 'before' => $before, 'after' => $after];
			}
		}

		return $data;
	}

	#[DataProvider('provideRelationChanges')]
	public function testRelationChange(string $method, int $before, int $after): void
	{
		$localRelationship = new LocalRelationship(1, 2, rel: $before);

		$localRelationship->$method();

		self::assertSame($after, $localRelationship->rel);
	}
}
