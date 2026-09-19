<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Attach;

use Friendica\Model\Attach;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * API endpoint: /api/friendica/attach/delete
 */
class Delete extends BaseApi
{
	protected function post(array $request = [])
	{
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'id' => 0, // Attachment id
		], $request);

		if (empty($request['id'])) {
			throw new BadRequestException($this->t('No id specified.'));
		}

		if (!Attach::exists(['id' => $request['id'], 'uid' => $uid])) {
			throw new BadRequestException($this->t('Attachment not available.'));
		}

		$result = Attach::delete(['id' => $request['id'], 'uid' => $uid]);

		if ($result) {
			$result = ['result' => 'deleted', 'message' => $this->t('Attachment with id `%s` has been deleted from server.', $request['id'])];
			$this->response->addFormattedContent('attach_delete', ['$result' => $result], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException($this->t('Unknown error while deleting attachment from database.'));
		}
	}
}
