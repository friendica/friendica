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

	public const DETECT_LANGUAGES = 'friendica.data.detect_languages';

	public const MODERATION_USERS_TABS = 'friendica.data.moderation_users_tabs';

	public const ACL_LOOKUP_END = 'friendica.data.acl_lookup_end';

	public const PROTOCOL_SUPPORTS_REVOKE_FOLLOW = 'friendica.data.protocol_supports_revoke_follow';

	public const PROTOCOL_SUPPORTS_PROBE = 'friendica.data.protocol_supports_probe';

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
