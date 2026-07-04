<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Toggle pinned items
 */
class Pin extends BaseModule
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

		$item = Post::selectFirst(['uri-id', 'uid', 'featured', 'author-id'], ['id' => $itemId]);
		if (!DBA::isResult($item)) {
			throw new HTTPException\NotFoundException();
		}

		if (!in_array($item['uid'], [0, DI::userSession()->getLocalUserId()])) {
			throw new HttpException\ForbiddenException($l10n->t('Access denied.'));
		}

		$pinned = !$item['featured'];

		if ($pinned) {
			Post\Collection::add($item['uri-id'], Post\Collection::FEATURED, $item['author-id'], DI::userSession()->getLocalUserId());
		} else {
			Post\Collection::remove($item['uri-id'], Post\Collection::FEATURED, DI::userSession()->getLocalUserId());
		}

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
			'verb'    => 'pin',
			'state'   => (int) $pinned,
		];

		$this->jsonExit($return);
	}
}
