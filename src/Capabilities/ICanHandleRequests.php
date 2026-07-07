<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Capabilities;

use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Psr\Http\Message\ResponseInterface;

/**
 * This interface provides the capability to handle requests from clients and returns the desired outcome
 *
 * @deprecated 2026.08 Use IRequestHandler::handleRequest() instead
 */
interface ICanHandleRequests
{
	/**
	 * @param ModuleHTTPException $httpException The special HTTPException Module in case of underlying errors
	 * @param array               $request       The $_REQUEST content (including content from the PHP input stream)
	 *
	 * @return ResponseInterface responding to the request handling
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface;
}
