<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Toggle starred items
 */
class Star extends BaseModule
{
	protected function post(array $request = [])
	{
		$l10n = DI::l10n();

		if (!DI::userSession()->isAuthenticated()) {
			throw new HttpException\ForbiddenException($l10n->t('Access denied.'));
		}

		if (empty($this->parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$itemId = intval($this->parameters['id']);


		$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['uid', 'uri-id', 'starred'], ['uid' => [0, DI::userSession()->getLocalUserId()], 'id' => $itemId]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		if ($item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($item['uri-id'], DI::userSession()->getLocalUserId(), ['post-reason' => Item::PR_ACTIVITY]);
			if (!empty($stored)) {
				$item = Post::selectFirst(['starred'], ['id' => $stored]);
				if (!DBA::isResult($item)) {
					throw new HTTPException\NotFoundException();
				}
				$itemId = $stored;
			} else {
				throw new HTTPException\NotFoundException();
			}
		}

		$starred = !(bool) $item['starred'];

		Item::update(['starred' => $starred], ['id' => $itemId]);

		// See if we've been passed a return path to redirect to
		$return_path = $_REQUEST['return'] ?? '';
		if (!empty($return_path)) {
			$rand = '_=' . time();
			if (strpos((string) $return_path, '?')) {
				$rand = "&$rand";
			} else {
				$rand = "?$rand";
			}

			DI::baseUrl()->redirect($return_path . $rand);
		}

		$return = [
			'status'  => 'ok',
			'item_id' => $itemId,
			'verb'    => 'star',
			'state'   => (int) $starred,
		];

		$this->jsonExit($return);
	}
}
