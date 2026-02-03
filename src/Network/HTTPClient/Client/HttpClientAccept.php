<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Client;

/**
 * This class contains a list of possible HTTPClient ACCEPT options.
 */
class HttpClientAccept
{
	/** @var string Default value for "Accept" header */
	public const DEFAULT = '*/*';

	/** @var string Accept all types with a preferences of ActivityStream content */
	public const AS_DEFAULT = 'application/activity+json,application/ld+json; profile="https://www.w3.org/ns/activitystreams",*/*;q=0.9';

	public const ATOM_XML  = 'application/atom+xml,text/xml;q=0.9,*/*;q=0.8';
	public const FEED_XML  = 'application/atom+xml,application/rss+xml;q=0.9,application/rdf+xml;q=0.8,text/xml;q=0.7,*/*;q=0.6';
	public const HTML      = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
	public const IMAGE     = 'image/webp,image/png,image/jpeg,image/gif,image/*;q=0.9,*/*;q=0.8'; // @todo add image/avif once our minimal supported PHP version is 8.1.0
	public const JRD_JSON  = 'application/jrd+json,application/json;q=0.9';
	public const JSON      = 'application/json,*/*;q=0.9';
	public const JSON_AS   = 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"';
	public const MAGIC     = 'application/magic-envelope+xml';
	public const MAGIC_KEY = 'application/magic-public-key';
	public const RSS_XML   = 'application/rss+xml,text/xml;q=0.9,*/*;q=0.8';
	public const TEXT      = 'text/plain,text/*;q=0.9,*/*;q=0.8';
	public const VIDEO     = 'video/mp4,video/*;q=0.9,*/*;q=0.8';
	public const XRD_XML   = 'application/xrd+xml,text/xml;q=0.9,*/*;q=0.8';
	public const XML       = 'application/xml,text/xml;q=0.9,*/*;q=0.8';
	public const HLS       = 'application/vnd.apple.mpegurl,application/x-mpegURL;q=0.9,*/*;q=0.8';
}
