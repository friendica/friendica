<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\GNUSocial\GNUSocial;

use Friendica\Module\BaseApi;

/**
 * API endpoint: /api/gnusocial/version, /api/statusnet/version
 */
class Version extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->response->addFormattedContent('version', ['version' => '0.9.7'], $this->parameters['extension'] ?? null);
	}
}
