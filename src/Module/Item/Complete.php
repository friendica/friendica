<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Module for completing threads
 */
final class Complete extends BaseModule
{
	protected function post(array $request = [])
	{
		$l10n = DI::l10n();
		DI::logger()->debug('Complete thread requested.', ['parameters' => $this->parameters]);
		if (!DI::userSession()->isAuthenticated()) {
			throw new HttpException\ForbiddenException($l10n->t('Access denied.'));
		}

		if (!isset($this->parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$itemId = intval($this->parameters['id']);

		Worker::add(Worker::PRIORITY_MEDIUM, 'FetchMissingReplies', $itemId);

		$return = [
			'status'  => 'ok',
			'item_id' => $itemId,
			'verb'    => 'complete',
			'state'   => 1
		];

		DI::logger()->debug('Complete thread executed.', ['parameters' => $this->parameters]);
		$this->jsonExit($return);
	}
}
