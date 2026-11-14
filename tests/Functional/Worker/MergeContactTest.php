<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Worker;

use Dice\Dice;
use Friendica\Contact\LocalRelationship\Entity\LocalRelationship;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Worker\MergeContact;
use PHPUnit\Framework\Attributes\DataProvider;

class MergeContactTest extends DatabaseTestCase
{
	private const UID = 42;

	protected function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);

		DBA::e('SET FOREIGN_KEY_CHECKS = 0');
	}

	protected function tearDown(): void
	{
		DBA::e('SET FOREIGN_KEY_CHECKS = 1');

		parent::tearDown();
	}

	/**
	 * @return array<string, array{int, int, int}>
	 */
	public static function dataRemoteSelf(): array
	{
		return [
			'nothing to keep'         => [LocalRelationship::MIRROR_DEACTIVATED, LocalRelationship::MIRROR_DEACTIVATED, LocalRelationship::MIRROR_DEACTIVATED],
			'own post from duplicate' => [LocalRelationship::MIRROR_DEACTIVATED, LocalRelationship::MIRROR_OWN_POST, LocalRelationship::MIRROR_OWN_POST],
			'reshare from duplicate'  => [LocalRelationship::MIRROR_DEACTIVATED, LocalRelationship::MIRROR_NATIVE_RESHARE, LocalRelationship::MIRROR_NATIVE_RESHARE],
			'target stays'            => [LocalRelationship::MIRROR_NATIVE_RESHARE, LocalRelationship::MIRROR_DEACTIVATED, LocalRelationship::MIRROR_NATIVE_RESHARE],
			'target wins'             => [LocalRelationship::MIRROR_OWN_POST, LocalRelationship::MIRROR_NATIVE_RESHARE, LocalRelationship::MIRROR_OWN_POST],
		];
	}

	#[DataProvider('dataRemoteSelf')]
	public function testMergeKeepsMirrorMode(int $target, int $duplicate, int $expected): void
	{
		$this->insertContact(9301, $target);
		$this->insertContact(9302, $duplicate);

		MergeContact::execute(9301, 9302, self::UID);

		self::assertSame($expected, (int) DBA::selectFirst('contact', ['remote_self'], ['id' => 9301])['remote_self']);
		self::assertFalse(DBA::exists('contact', ['id' => 9302]));
	}

	private function insertContact(int $id, int $remoteSelf): void
	{
		DBA::insert('contact', [
			'id'          => $id,
			'uid'         => self::UID,
			'url'         => 'https://example.test/' . $id,
			'nurl'        => 'https://example.test/' . $id,
			'network'     => 'feed',
			'name'        => 'feed' . $id,
			'nick'        => 'feed' . $id,
			'remote_self' => $remoteSelf,
		]);
	}
}
