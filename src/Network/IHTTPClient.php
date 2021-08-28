<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU APGL version 3 or any later version
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

namespace Friendica\Network;

/**
 * Interface for calling HTTP requests and returning their responses
 */
interface IHTTPClient
{
	/**
	 * Fetches the content of an URL
	 *
	 * Set the cookiejar argument to a string (e.g. "/tmp/friendica-cookies.txt")
	 * to preserve cookies from one request to the next.
	 *
	 * @param string $url             URL to fetch
	 * @param int    $timeout         Timeout in seconds, default system config value or 60 seconds
	 * @param string $accept_content  supply Accept: header with 'accept_content' as the value
	 * @param string $cookiejar       Path to cookie jar file
	 *
	 * @return string The fetched content
	 */
	public function fetch(string $url, int $timeout = 0, string $accept_content = '', string $cookiejar = '');

	/**
	 * Fetches the whole response of an URL.
	 *
	 * Inner workings and parameters are the same as @ref fetchUrl but returns an array with
	 * all the information collected during the fetch.
	 *
	 * @param string $url             URL to fetch
	 * @param int    $timeout         Timeout in seconds, default system config value or 60 seconds
	 * @param string $accept_content  supply Accept: header with 'accept_content' as the value
	 * @param string $cookiejar       Path to cookie jar file
	 *
	 * @return IHTTPResult With all relevant information, 'body' contains the actual fetched content.
	 */
	public function fetchFull(string $url, int $timeout = 0, string $accept_content = '', string $cookiejar = '');

	/**
	 * Send a HEAD to an URL.
	 *
	 * @param string $url        URL to fetch
	 * @param array  $opts       (optional parameters) associative array with:
	 *                           'accept_content' => (string array) supply Accept: header with 'accept_content' as the value
	 *                           'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                           'cookiejar' => path to cookie jar file
	 *                           'header' => header array
	 *
	 * @return CurlResult
	 */
	public function head(string $url, array $opts = []);

	/**
	 * Send a GET to an URL.
	 *
	 * @param string $url        URL to fetch
	 * @param array  $opts       (optional parameters) associative array with:
	 *                           'accept_content' => (string array) supply Accept: header with 'accept_content' as the value
	 *                           'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                           'cookiejar' => path to cookie jar file
	 *                           'header' => header array
	 *                           'content_length' => int maximum File content length
	 *
	 * @return IHTTPResult
	 */
	public function get(string $url, array $opts = []);

	/**
	 * Sends a HTTP request to a given url
	 *
	 * @param string $method A HTTP request
	 * @param string $url    Url to send to
	 * @param array  $opts   (optional parameters) associative array with:
	 *                       	 'body' => (mixed) setting the body for sending data
	 *                           'accept_content' => (string array) supply Accept: header with 'accept_content' as the value
	 *                           'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                           'cookiejar' => path to cookie jar file
	 *                           'header' => header array
	 *                           'content_length' => int maximum File content length
	 *                           'auth' => array authentication settings
	 *
	 * @return IHTTPResult
	 */
	public function request(string $method, string $url, array $opts = []);

	/**
	 * Send POST request to an URL
	 *
	 * @param string $url     URL to post
	 * @param mixed  $params  array of POST variables
	 * @param array  $headers HTTP headers
	 * @param int    $timeout The timeout in seconds, default system config value or 60 seconds
	 *
	 * @return IHTTPResult The content
	 */
	public function post(string $url, $params, array $headers = [], int $timeout = 0);

	/**
	 * Returns the original URL of the provided URL
	 *
	 * This function strips tracking query params and follows redirections, either
	 * through HTTP code or meta refresh tags. Stops after 10 redirections.
	 *
	 * @param string $url       A user-submitted URL
	 *
	 * @return string A canonical URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function finalUrl(string $url);
}
