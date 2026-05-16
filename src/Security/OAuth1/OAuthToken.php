<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\OAuth1;

class OAuthToken implements \Stringable
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
	public function __construct($key, $secret)
	{
		$this->key    = $key;
		$this->secret = $secret;
	}

	/**
	 * generates the basic string serialization of a token that a server
	 * would respond to request_token and access_token calls with
	 */
	public function to_string()
	{
		return "oauth_token="
			   . OAuthUtil::urlencode_rfc3986($this->key)
			   . "&oauth_token_secret="
			   . OAuthUtil::urlencode_rfc3986($this->secret);
	}

	public function __toString(): string
	{
		return (string) $this->to_string();
	}
}
