<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\OAuth1;

class OAuthConsumer implements \Stringable
{
	public function __construct(public $key, public $secret, public $callback_url = null) {}

	public function __toString(): string
	{
		return "OAuthConsumer[key=$this->key,secret=$this->secret]";
	}
}
