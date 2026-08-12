<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\ArrayFilterEvent;
use Friendica\Core\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class ArrayFilterEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ArrayFilterEvent('test', []);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public static function getPublicConstants(): array
	{
		return [
			[ArrayFilterEvent::APP_MENU, 'friendica.data.app_menu'],
			[ArrayFilterEvent::NAV_INFO, 'friendica.data.nav_info'],
			[ArrayFilterEvent::FEATURE_ENABLED, 'friendica.data.feature_enabled'],
			[ArrayFilterEvent::FEATURE_GET, 'friendica.data.feature_get'],
			[ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT, 'friendica.data.permission_tooltip_content'],
			[ArrayFilterEvent::DETECT_LANGUAGES, 'friendica.data.detect_languages'],
			[ArrayFilterEvent::MODERATION_USERS_TABS, 'friendica.data.moderation_users_tabs'],
			[ArrayFilterEvent::ACL_LOOKUP_END, 'friendica.data.acl_lookup_end'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW, 'friendica.data.protocol_supports_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW, 'friendica.data.protocol_supports_revoke_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE, 'friendica.data.protocol_supports_probe'],
			[ArrayFilterEvent::EVENT_CREATED, 'friendica.data.event_created'],
			[ArrayFilterEvent::EVENT_UPDATED, 'friendica.data.event_updated'],
			[ArrayFilterEvent::ADD_WORKER_TASK, 'friendica.data.add_worker_task'],
			[ArrayFilterEvent::STORAGE_CONFIG, 'friendica.data.storage_config'],
			[ArrayFilterEvent::STORAGE_INSTANCE, 'friendica.data.storage_instance'],
			[ArrayFilterEvent::DB_STRUCTURE_DEFINITION, 'friendica.data.db_structure_definition'],
			[ArrayFilterEvent::DB_VIEW_DEFINITION, 'friendica.data.db_view_definition'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ArrayFilterEvent('test', []);

		$this->assertSame('test', $event->getName());
	}

	public function testGetArrayReturnsCorrectString(): void
	{
		$data = ['original'];

		$event = new ArrayFilterEvent('test', $data);

		$this->assertSame($data, $event->getArray());
	}

	public function testSetArrayUpdatesHtml(): void
	{
		$event = new ArrayFilterEvent('test', ['original']);

		$expected = ['updated'];

		$event->setArray($expected);

		$this->assertSame($expected, $event->getArray());
	}
}
