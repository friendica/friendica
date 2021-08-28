<?php

namespace Friendica\Network;

use GuzzleHttp\RequestOptions;

/**
 * This class contains a list of possible HTTPClient request options.
 */
class HTTPClientOptions
{
	/**
	 * accept_content: (array) supply Accept: header with 'accept_content' as the value
	 */
	const ACCEPT_CONTENT = 'accept_content';
	/**
	 * timeout: (int) out in seconds, default system config value or 60 seconds
	 */
	const TIMEOUT = RequestOptions::TIMEOUT;
	/**
	 * cookiejar: (string) path to cookie jar file
	 */
	const COOKIEJAR = 'cookiejar';
	/**
	 * headers: (array) header array
	 */
	const HEADERS = RequestOptions::HEADERS;
	/**
	 * header: (array) header array (legacy version)
	 */
	const LEGACY_HEADER = 'header';
	/**
	 * content_length: (int) maximum File content length
	 */
	const CONTENT_LENGTH = 'content_length';
	/**
	 * body: (mixed) Setting the body for sending data
	 */
	const BODY = RequestOptions::BODY;
	/**
	 * auth: (array) Authentication settings for specific requests
	 */
	const AUTH = RequestOptions::AUTH;
}
