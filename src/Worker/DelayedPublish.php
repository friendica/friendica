<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Post;

class DelayedPublish
{
	/**
	 * Publish a post, used for delayed postings
	 *
	 * @param array  $item
	 * @param int    $notify
	 * @param array  $taglist
	 * @param array  $attachments
	 * @param int    $preparation_mode
	 * @param string $uri
	 * @return void
	 */
	public static function execute(array $item, int $notify = 0, array $taglist = [], array $attachments = [], int $preparation_mode = Post\Delayed::PREPARED, string $uri = '')
	{
		$id = Post\Delayed::publish($item, $notify, $taglist, $attachments, $preparation_mode, $uri);
		DI::logger()->notice('Post published', ['id' => $id, 'uid' => $item['uid'], 'notify' => $notify, 'unprepared' => $preparation_mode]);
	}
}
