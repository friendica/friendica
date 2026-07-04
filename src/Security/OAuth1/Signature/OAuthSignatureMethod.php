<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	abstract public function build_signature(OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, ?\Friendica\Security\OAuth1\OAuthToken $token = null);

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
	public function check_signature(OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, $signature, ?\Friendica\Security\OAuth1\OAuthToken $token = null)
	{
		$built = $this->build_signature($request, $consumer, $token);
		return ($built == $signature);
	}
}
