<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Allow Event listener to modify an array.
 *
 * @internal
 */
final class ArrayFilterEvent extends AbstractEvent
{
	public const APP_MENU = 'friendica.data.app_menu';

	public const NAV_INFO = 'friendica.data.nav_info';

	public const FEATURE_ENABLED = 'friendica.data.feature_enabled';

	public const FEATURE_GET = 'friendica.data.feature_get';

	public const PERMISSION_TOOLTIP_CONTENT = 'friendica.data.permission_tooltip_content';

	public const INSERT_POST_LOCAL_START = 'friendica.data.insert_post_local_start';

	public const PHOTO_UPLOAD_FORM = 'friendica.data.photo_upload_form';

	public const DETECT_LANGUAGES = 'friendica.data.detect_languages';

	public const CONTACT_PHOTO_MENU = 'friendica.data.contact_photo_menu';

	public const PROFILE_SIDEBAR_ENTRY = 'friendica.data.profile_sidebar_entry';

	public const PROFILE_SIDEBAR = 'friendica.data.profile_sidebar';

	public const PROFILE_TABS = 'friendica.data.profile_tabs';

	public const PROFILE_SETTINGS_FORM = 'friendica.data.profile_settings_form';

	public const PROFILE_SETTINGS_POST = 'friendica.data.profile_settings_post';

	public const MODERATION_USERS_TABS = 'friendica.data.moderation_users_tabs';

	public const ACL_LOOKUP_END = 'friendica.data.acl_lookup_end';

	public const SMILEY_LIST = 'friendica.data.smiley_list';

	public const BBCODE_TO_HTML_START = 'friendica.data.bbcode_to_html_start';

	public const HTML_TO_BBCODE_END = 'friendica.data.html_to_bbcode_end';

	public const BBCODE_TO_MARKDOWN_END = 'friendica.data.bbcode_to_markdown_end';

	public const JOT_NETWORKS = 'friendica.data.jot_networks';

	public const PROTOCOL_SUPPORTS_FOLLOW = 'friendica.data.protocol_supports_follow';

	public const PROTOCOL_SUPPORTS_REVOKE_FOLLOW = 'friendica.data.protocol_supports_revoke_follow';

	public const PROTOCOL_SUPPORTS_PROBE = 'friendica.data.protocol_supports_probe';

	public const FOLLOW_CONTACT = 'friendica.data.follow_contact';

	public const UNFOLLOW_CONTACT = 'friendica.data.unfollow_contact';

	public const REVOKE_FOLLOW_CONTACT = 'friendica.data.revoke_follow_contact';

	public const BLOCK_CONTACT = 'friendica.data.block_contact';

	public const UNBLOCK_CONTACT = 'friendica.data.unblock_contact';

	public const EDIT_CONTACT_FORM = 'friendica.data.edit_contact_form';

	public const EDIT_CONTACT_POST = 'friendica.data.edit_contact_post';

	public const AVATAR_LOOKUP = 'friendica.data.avatar_lookup';

	public const EVENT_CREATED = 'friendica.data.event_created';

	public const EVENT_UPDATED = 'friendica.data.event_updated';

	public const ADD_WORKER_TASK = 'friendica.data.add_worker_task';

	public const STORAGE_CONFIG = 'friendica.data.storage_config';

	public const STORAGE_INSTANCE = 'friendica.data.storage_instance';

	public const DB_STRUCTURE_DEFINITION = 'friendica.data.db_structure_definition';

	public const DB_VIEW_DEFINITION = 'friendica.data.db_view_definition';

	public const ADDON_SETTINGS_POST = 'friendica.data.addon_settings_post';

	public const CONNECTOR_SETTINGS_POST = 'friendica.data.connector_settings_post';

	public const DISPLAY_SETTINGS_POST = 'friendica.data.display_settings_post';

	public const EMAILER_SEND = 'friendica.data.emailer_send';

	public const EMAILER_SEND_PREPARE = 'friendica.data.emailer_send_prepare';

	public const EMAIL_GET_MESSAGE = 'friendica.data.email_get_message';

	public const EMAIL_GET_MESSAGE_END = 'friendica.data.email_get_message_end';

	public const GENERATE_MAP = 'friendica.data.generate_map';

	public const GENERATE_NAMED_MAP = 'friendica.data.generate_named_map';

	public const GET_SITE_INFO = 'friendica.data.get_site_info';

	public const GLOBAL_DIR_UPDATE = 'friendica.data.global_dir_update';

	public const MAP_GET_COORDINATES = 'friendica.data.map_get_coordinates';

	public const NOTIFY = 'friendica.data.notify';

	public const OTHER_ENCAPSULATE = 'friendica.data.other_encapsulate';

	public const OTHER_UNENCAPSULATE = 'friendica.data.other_unencapsulate';

	public const PROBE_DETECT = 'friendica.data.probe_detect';

	public const TEMPLATE_VARS = 'friendica.data.template_vars';

	public const USER_EXPORT_OPTIONS = 'friendica.data.user_export_options';

	public function __construct(string $name, private array $array)
	{
		parent::__construct($name);
	}

	public function getArray(): array
	{
		return $this->array;
	}

	public function setArray(array $array): void
	{
		$this->array = $array;
	}
}
