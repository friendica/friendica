<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\GNUSocial\Help;

use Friendica\Module\BaseApi;

/**
 * API endpoint: /api/help/test
 */
class Test extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		if (($this->parameters['extension'] ?? '') == 'xml') {
			$ok = 'true';
		} else {
			$ok = 'ok';
		}

		$this->response->addFormattedContent('ok', ['ok' => $ok], $this->parameters['extension'] ?? null);
	}
}
