<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Client;

use GuzzleHttp\RequestOptions;

/**
 * This class contains a list of possible HTTPClient request options.
 */
class HttpClientOptions
{
	/**
	 * accept_content: (array) supply Accept: header with 'accept_content' as the value
	 */
	public const ACCEPT_CONTENT = 'accept_content';
	/**
	 * timeout: (int) out in seconds, default system config value or 60 seconds
	 */
	public const TIMEOUT = RequestOptions::TIMEOUT;
	/**
	 * cookiejar: (string) path to cookie jar file
	 */
	public const COOKIEJAR = 'cookiejar';
	/**
	 * headers: (array) header array
	 */
	public const HEADERS = RequestOptions::HEADERS;
	/**
	 * header: (array) header array (legacy version)
	 */
	public const LEGACY_HEADER = 'header';
	/**
	 * content_length: (int) maximum File content length
	 */
	public const CONTENT_LENGTH = 'content_length';
	/**
	 * Request: (string) Type of request (ActivityPub, Diaspora, server discovery, ...)
	 */
	public const REQUEST = 'request';
	/**
	 * verify: (bool|string, default=true) Describes the SSL certificate
	 */
	public const VERIFY = 'verify';

	/**
	 * body: (string) Setting the body for sending data
	 */
	public const BODY = RequestOptions::BODY;
	/**
	 * form_params: (array) Associative array of form field names to values
	 */
	public const FORM_PARAMS = RequestOptions::FORM_PARAMS;
	/**
	 * auth: (array) Authentication settings for specific requests
	 */
	public const AUTH = RequestOptions::AUTH;
	/**
	 * stream: (bool) Return the response as a stream instead of a string
	 */
	public const STREAM = RequestOptions::STREAM;
}
