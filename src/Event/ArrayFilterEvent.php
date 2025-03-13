<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Allow Event listener to modify an array.
 *
 * @internal
 */
final class ArrayFilterEvent extends Event
{
	public const APP_MENU = 'friendica.data.app_menu';

	public const NAV_INFO = 'friendica.data.nav_info';

	public const FEATURE_ENABLED = 'friendica.data.feature_enabled';

	public const FEATURE_GET = 'friendica.data.feature_get';

	public const POST_LOCAL_START = 'friendica.data.post_local_start';

	public const POST_LOCAL = 'friendica.data.post_local';

	public const POST_LOCAL_END = 'friendica.data.post_local_end';

	public const PHOTO_UPLOAD_FORM = 'friendica.data.photo_upload_form';

	public const NETWORK_TO_NAME = 'friendica.data.network_to_name';

	public const CONVERSATION_START = 'friendica.data.conversation_start';

	public const DISPLAY_ITEM = 'friendica.data.display_item';

	public const RENDER_LOCATION = 'friendica.data.render_location';

	public const ITEM_PHOTO_MENU = 'friendica.data.item_photo_menu';

	public const OEMBED_FETCH_END = 'friendica.data.oembed_fetch_end';

	public const PAGE_INFO = 'friendica.data.page_info';

	public const SMILEY_LIST = 'friendica.data.smiley_list';

	public const BBCODE_TO_HTML_START = 'friendica.data.bbcode_to_html_start';

	public const HTML_TO_BBCODE_END = 'friendica.data.html_to_bbcode_end';

	public const BBCODE_TO_MARKDOWN_END = 'friendica.data.bbcode_to_markdown_end';

	public const JOT_NETWORKS = 'friendica.data.jot_networks';

	public const PROTOCOL_SUPPORTS_FOLLOW = 'friendica.data.protocol_supports_follow';

	public const PROTOCOL_SUPPORTS_REVOKE_FOLLOW = 'friendica.data.protocol_supports_revoke_follow';

	public const PROTOCOL_SUPPORTS_PROBE = 'friendica.data.protocol_supports_probe';

	private array $array;

	public function __construct(string $name, array $array)
	{
		parent::__construct($name);

		$this->array = $array;
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
