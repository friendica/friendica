<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Update;

use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Search\Index;

/**
 * The posts that are newer than the ones shown on the search page, used by the automatic update
 */
class Search extends Index
{
	protected function rawContent(array $request = [])
	{
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid || !isset($request['p'])) {
			System::exit();
		}

		$items = [];

		$uriids = $this->getNewUriIds($request, $uid, DI::pConfig()->get($uid, 'system', 'itemspage_network', DI::config()->get('system', 'itemspage_network')));
		if (!empty($uriids)) {
			$condition = DBA::mergeConditions(["(`uid` = ? OR (`uid` = ? AND NOT `global`))", 0, $uid], ['uri-id' => $uriids]);
			$items     = Post::toArray(Post::selectForUser($uid, Item::DISPLAY_FIELDLIST, $condition, ['order' => ['uri-id' => true]]));
		}

		System::htmlUpdateExit($items ? DI::conversationRenderer()->renderFlat($items, ConversationRenderer::MODE_SEARCH, false, $uid) : '');
	}
}
