<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Capabilities;

use Friendica\App\Request;
use Psr\Http\Message\ResponseInterface;

interface RequestHandler
{
	public function handleRequest(Request $request): ResponseInterface;
}
