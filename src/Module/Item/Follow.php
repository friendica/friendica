<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Network\HTTPException;

/**
 * Module for following threads
 */
class Follow extends BaseModule
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

		if (!Item::performActivity($itemId, 'follow', DI::userSession()->getLocalUserId())) {
			throw new HTTPException\BadRequestException($l10n->t('Unable to follow this item.'));
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
			'verb'    => 'follow',
			'state'   => 1,
		];

		$this->jsonExit($return);
	}
}
