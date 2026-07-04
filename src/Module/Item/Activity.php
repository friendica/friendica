<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Diaspora;

/**
 * Performs an activity (like, dislike, announce, attendyes, attendno, attendmaybe)
 * and optionally redirects to a return path
 */
class Activity extends BaseModule
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		if (empty($this->parameters['id']) || empty($this->parameters['verb'])) {
			throw new HTTPException\BadRequestException();
		}

		$verb    = $this->parameters['verb'];
		$itemId  = $this->parameters['id'];
		$handled = false;

		if (in_array($verb, ['announce', 'unannounce'])) {
			$item = Post::selectFirst(['network', 'uri-id'], ['id' => $itemId, 'uid' => [DI::userSession()->getLocalUserId(), 0]]);
			if ($item['network'] == Protocol::DIASPORA) {
				$quote = Post::selectFirst(['id'], ['quote-uri-id' => $item['uri-id'], 'body' => '', 'origin' => true, 'uid' => DI::userSession()->getLocalUserId()]);
				if (!empty($quote['id'])) {
					if (!Item::markForDeletionById($quote['id'])) {
						throw new HTTPException\BadRequestException();
					}
				} else {
					Diaspora::performReshare($item['uri-id'], DI::userSession()->getLocalUserId());
				}
				$handled = true;
			}
		}

		if (!$handled && !Item::performActivity($itemId, $verb, DI::userSession()->getLocalUserId())) {
			throw new HTTPException\BadRequestException();
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
			'verb'    => $verb,
			'state'   => 1,
		];

		$this->jsonExit($return);
	}
}
