<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\OAuth1;

class OAuthDataStore
{
	public function lookup_consumer($consumer_key)
	{
		// implement me
	}

	public function lookup_token(OAuthConsumer $consumer, $token_type, $token_id)
	{
		// implement me
	}

	public function lookup_nonce(OAuthConsumer $consumer, OAuthToken $token, $nonce, int $timestamp)
	{
		// implement me
	}

	public function new_request_token(OAuthConsumer $consumer, $callback = null)
	{
		// return a new token attached to this consumer
	}

	public function new_access_token(OAuthToken $token, OAuthConsumer $consumer, $verifier = null)
	{
		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token
	}
}
