<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\OAuth1\Signature;

use Friendica\Security\OAuth1\OAuthRequest;
use Friendica\Security\OAuth1\OAuthUtil;

/**
 * The PLAINTEXT method does not provide any security protection and SHOULD only be used
 * over a secure channel such as HTTPS. It does not use the Signature Base String.
 *   - Chapter 9.4 ("PLAINTEXT")
 */
class OAuthSignatureMethod_PLAINTEXT extends OAuthSignatureMethod
{
	public function get_name()
	{
		return "PLAINTEXT";
	}

	/**
	 * oauth_signature is set to the concatenated encoded values of the Consumer Secret and
	 * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is
	 * empty. The result MUST be encoded again.
	 *   - Chapter 9.4.1 ("Generating Signatures")
	 *
	 * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
	 * OAuthRequest handles this!
	 *
	 * @param $request
	 * @param $consumer
	 * @param $token
	 *
	 * @return string
	 */
	public function build_signature(OAuthRequest $request, \Friendica\Security\OAuth1\OAuthConsumer $consumer, ?\Friendica\Security\OAuth1\OAuthToken $token = null)
	{
		$key_parts = [
			$consumer->secret,
			($token) ? $token->secret : "",
		];

		$key_parts            = OAuthUtil::urlencode_rfc3986($key_parts);
		$key                  = implode('&', $key_parts);
		$request->base_string = $key;

		return $key;
	}
}
