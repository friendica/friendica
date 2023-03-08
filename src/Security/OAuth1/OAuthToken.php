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

namespace Friendica\Security\OAuth1;

class OAuthToken
{
	// access tokens and request tokens
	public $key;
	public $secret;

	public $expires;
	public $scope;
	public $uid;

	/**
	 * key = the token
	 * secret = the token secret
	 *
	 * @param $key
	 * @param $secret
	 */
	function __construct($key, $secret)
	{
		$this->key    = $key;
		$this->secret = $secret;
	}

	/**
	 * generates the basic string serialization of a token that a server
	 * would respond to request_token and access_token calls with
	 */
	function to_string()
	{
		return "oauth_token=" .
			   OAuthUtil::urlencode_rfc3986($this->key) .
			   "&oauth_token_secret=" .
			   OAuthUtil::urlencode_rfc3986($this->secret);
	}

	function __toString()
	{
		return $this->to_string();
	}
}
