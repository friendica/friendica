<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
	 * verify: (bool|string, default=true) Describes the SSL certificate
	 */
	const VERIFY = 'verify';

	/**
	 * body: (string) Setting the body for sending data
	 */
	const BODY = RequestOptions::BODY;
	/**
	 * form_params: (array) Associative array of form field names to values
	 */
	const FORM_PARAMS = RequestOptions::FORM_PARAMS;
	/**
	 * auth: (array) Authentication settings for specific requests
	 */
	const AUTH = RequestOptions::AUTH;
}
