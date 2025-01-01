<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Relationships extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'id' => [],
		], $request);

		if (empty($request['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!is_array($request['id'])) {
			$request['id'] = [$request['id']];
		}

		$relationships = [];

		foreach ($request['id'] as $id) {
			$relationships[] = DI::mstdnRelationship()->createFromContactId($id, $uid);
		}

		$this->jsonExit($relationships);
	}
}
