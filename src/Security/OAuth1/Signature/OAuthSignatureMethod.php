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

namespace Friendica\Security\OAuth1\Signature;

use Friendica\Security\OAuth1\OAuthRequest;

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class OAuthSignatureMethod
{
	/**
	 * Needs to return the name of the Signature Method (ie HMAC-SHA1)
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Build up the signature
	 * NOTE: The output of this function MUST NOT be urlencoded.
	 * the encoding is handled in OAuthRequest when the final
	 * request is serialized
	 *
	 * @param OAuthRequest                             $request
	 * @param \Friendica\Security\OAuth1\OAuthConsumer $consumer
	 * @param \Friendica\Security\OAuth1\OAuthToken    $token
	 *
	 * @return string
	 */
	abstract public function build_signature(OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, \Friendica\Security\OAuth1\OAuthToken $token = null);

	/**
	 * Verifies that a given signature is correct
	 *
	 * @param OAuthRequest                             $request
	 * @param \Friendica\Security\OAuth1\OAuthConsumer $consumer
	 * @param \Friendica\Security\OAuth1\OAuthToken    $token
	 * @param string                                   $signature
	 *
	 * @return bool
	 */
	public function check_signature(OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, $signature, \Friendica\Security\OAuth1\OAuthToken $token = null)
	{
		$built = $this->build_signature($request, $consumer, $token);
		return ($built == $signature);
	}
}
