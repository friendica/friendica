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

namespace Friendica\Network\HTTPClient\Client;

/**
 * This class contains a list of possible HTTPClient ACCEPT options.
 */
class HttpClientAccept
{
	/** @var string Default value for "Accept" header */
	public const DEFAULT = '*/*';

	public const ATOM_XML  = 'application/atom+xml,text/xml;q=0.9,*/*;q=0.8';
	public const FEED_XML  = 'application/atom+xml,application/rss+xml;q=0.9,application/rdf+xml;q=0.8,text/xml;q=0.7,*/*;q=0.6';
	public const HTML      = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
	public const IMAGE     = 'image/png,image/jpeg,image/gif,image/*;q=0.9,*/*;q=0.8';
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
}
