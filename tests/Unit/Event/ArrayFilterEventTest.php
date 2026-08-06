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
			[ArrayFilterEvent::INSERT_POST_LOCAL_START, 'friendica.data.insert_post_local_start'],
			[ArrayFilterEvent::PHOTO_UPLOAD_FORM, 'friendica.data.photo_upload_form'],
			[ArrayFilterEvent::PHOTO_UPLOAD_START, 'friendica.data.photo_upload_start'],
			[ArrayFilterEvent::PHOTO_UPLOAD, 'friendica.data.photo_upload'],
			[ArrayFilterEvent::PHOTO_UPLOAD_END, 'friendica.data.photo_upload_end'],
			[ArrayFilterEvent::NETWORK_TO_NAME, 'friendica.data.network_to_name'],
			[ArrayFilterEvent::NETWORK_CONTENT_START, 'friendica.data.network_content_start'],
			[ArrayFilterEvent::NETWORK_CONTENT_TABS, 'friendica.data.network_content_tabs'],
			[ArrayFilterEvent::PARSE_LINK, 'friendica.data.parse_link'],
			[ArrayFilterEvent::ENOTIFY_MAIL, 'friendica.data.enotify_mail'],
			[ArrayFilterEvent::DETECT_LANGUAGES, 'friendica.data.detect_languages'],
			[ArrayFilterEvent::RENDER_LOCATION, 'friendica.data.render_location'],
			[ArrayFilterEvent::CONTACT_PHOTO_MENU, 'friendica.data.contact_photo_menu'],
			[ArrayFilterEvent::PROFILE_SIDEBAR_ENTRY, 'friendica.data.profile_sidebar_entry'],
			[ArrayFilterEvent::PROFILE_SIDEBAR, 'friendica.data.profile_sidebar'],
			[ArrayFilterEvent::PROFILE_TABS, 'friendica.data.profile_tabs'],
			[ArrayFilterEvent::PROFILE_SETTINGS_FORM, 'friendica.data.profile_settings_form'],
			[ArrayFilterEvent::PROFILE_SETTINGS_POST, 'friendica.data.profile_settings_post'],
			[ArrayFilterEvent::MODERATION_USERS_TABS, 'friendica.data.moderation_users_tabs'],
			[ArrayFilterEvent::ACL_LOOKUP_END, 'friendica.data.acl_lookup_end'],
			[ArrayFilterEvent::PAGE_INFO, 'friendica.data.page_info'],
			[ArrayFilterEvent::SMILEY_LIST, 'friendica.data.smiley_list'],
			[ArrayFilterEvent::BBCODE_TO_HTML_START, 'friendica.data.bbcode_to_html_start'],
			[ArrayFilterEvent::HTML_TO_BBCODE_END, 'friendica.data.html_to_bbcode_end'],
			[ArrayFilterEvent::BBCODE_TO_MARKDOWN_END, 'friendica.data.bbcode_to_markdown_end'],
			[ArrayFilterEvent::JOT_NETWORKS, 'friendica.data.jot_networks'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW, 'friendica.data.protocol_supports_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW, 'friendica.data.protocol_supports_revoke_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE, 'friendica.data.protocol_supports_probe'],
			[ArrayFilterEvent::FOLLOW_CONTACT, 'friendica.data.follow_contact'],
			[ArrayFilterEvent::UNFOLLOW_CONTACT, 'friendica.data.unfollow_contact'],
			[ArrayFilterEvent::REVOKE_FOLLOW_CONTACT, 'friendica.data.revoke_follow_contact'],
			[ArrayFilterEvent::BLOCK_CONTACT, 'friendica.data.block_contact'],
			[ArrayFilterEvent::UNBLOCK_CONTACT, 'friendica.data.unblock_contact'],
			[ArrayFilterEvent::EDIT_CONTACT_FORM, 'friendica.data.edit_contact_form'],
			[ArrayFilterEvent::EDIT_CONTACT_POST, 'friendica.data.edit_contact_post'],
			[ArrayFilterEvent::AVATAR_LOOKUP, 'friendica.data.avatar_lookup'],
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
