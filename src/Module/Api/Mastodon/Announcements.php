<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/announcements/
 */
class Announcements extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);

		// @todo Possibly use the message from the pageheader addon for this
		$this->jsonExit([]);
	}
}
