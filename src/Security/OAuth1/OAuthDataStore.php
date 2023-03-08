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

class OAuthDataStore
{
	function lookup_consumer($consumer_key)
	{
		// implement me
	}

	function lookup_token(OAuthConsumer $consumer, $token_type, $token_id)
	{
		// implement me
	}

	function lookup_nonce(OAuthConsumer $consumer, OAuthToken $token, $nonce, int $timestamp)
	{
		// implement me
	}

	function new_request_token(OAuthConsumer $consumer, $callback = null)
	{
		// return a new token attached to this consumer
	}

	function new_access_token(OAuthToken $token, OAuthConsumer $consumer, $verifier = null)
	{
		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token
	}
}
