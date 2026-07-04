<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Module for ignoring threads or user items
 */
class Ignore extends BaseModule
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

		$dba = DI::dba();

		$thread = Post::selectFirst(['uri-id', 'uid'], ['id' => $itemId, 'gravity' => Item::GRAVITY_PARENT]);
		if (!$dba->isResult($thread)) {
			throw new HTTPException\NotFoundException();
		}

		$ignored = !Post\ThreadUser::getIgnored($thread['uri-id'], DI::userSession()->getLocalUserId());

		if (in_array($thread['uid'], [0, DI::userSession()->getLocalUserId()])) {
			Post\ThreadUser::setIgnored($thread['uri-id'], DI::userSession()->getLocalUserId(), $ignored);
		} else {
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
			'verb'    => 'ignore',
			'state'   => $ignored,
		];

		$this->jsonExit($return);
	}
}
