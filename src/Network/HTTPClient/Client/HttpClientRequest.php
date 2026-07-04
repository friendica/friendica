<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Client;

/**
 * This class contains a list of request types that are set in the user agent string
 */
class HttpClientRequest
{
	public const ACTIVITYPUB     = 'ActivityPub/1';
	public const CONTACTINFO     = 'ContactInfo/1';
	public const CONTACTDISCOVER = 'ContactDiscover/1';
	public const CONTACTVERIFIER = 'ContactVerifier/1';
	public const CONTENTTYPE     = 'ContentTypeChecker/1';
	public const DFRN            = 'DFRN/1';
	public const DIASPORA        = 'Diaspora/1';
	public const FEEDFETCHER     = 'FeedFetcher/1';
	public const MAGICAUTH       = 'MagicAuth/1';
	public const MEDIAPROXY      = 'MediaProxy/1';
	public const MEDIAVERIFIER   = 'MediaVerifier/1';
	public const OSTATUS         = 'OStatus/1';
	public const SERVERINFO      = 'ServerInfo/1';
	public const SERVERDISCOVER  = 'ServerDiscover/1';
	public const SITEINFO        = 'SiteInfo/1';
	public const PUBSUB          = 'PubSub/1';
	public const URLRESOLVER     = 'URLResolver/1';
	public const URLVERIFIER     = 'URLVerifier/1';
	public const STREAMING       = 'Streaming/1';
}
