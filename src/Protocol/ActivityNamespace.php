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

namespace Friendica\Protocol;

/**
 * Activity namespaces constants
 */
final class ActivityNamespace
{
	/**
	 * Zot is a WebMTA which provides a decentralised identity and communications protocol using HTTPS/JSON.
	 *
	 * @var string
	 * @see https://zotlabs.org/page/zotlabs/specs+zot6+home
	 */
	const ZOT             = 'http://purl.org/zot';
	/**
	 * Friendica is using ActivityStreams in version 1.0 for its activities and object types.
	 * Additional types are used for non standard activities.
	 *
	 * @var string
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams
	 */
	const DFRN            = 'http://purl.org/macgirvin/dfrn/1.0';
	/**
	 * This namespace defines an extension for expressing threaded
	 * discussions within the Atom Syndication Format [RFC4287]
	 *
	 * @see https://tools.ietf.org/rfc/rfc4685.txt
	 * @var string
	 */
	const THREAD          = 'http://purl.org/syndication/thread/1.0';
	/**
	 * This namespace adds mechanisms to the Atom Syndication Format
	 * that publishers of Atom Feed and Entry documents can use to
	 * explicitly identify Atom entries that have been removed.
	 *
	 * @see https://tools.ietf.org/html/rfc6721
	 * @var string
	 */
	const TOMB            = 'http://purl.org/atompub/tombstones/1.0';
	/**
	 * This specification details a model for representing potential and completed activities
	 * using the JSON format.
	 *
	 * @see https://www.w3.org/ns/activitystreams
	 * @var string
	 */
	const ACTIVITY2       = 'https://www.w3.org/ns/activitystreams#';
	/**
	 * Atom Activities 1.0
	 *
	 * This namespace presents an XML format that allows activities on social objects
	 * to be expressed within the Atom Syndication Format.
	 *
	 * @see http://activitystrea.ms/spec/1.0
	 * @var string
	 */
	const ACTIVITY        = 'http://activitystrea.ms/spec/1.0/';
	/**
	 * This namespace presents a base set of Object types and Verbs for use with Activity Streams.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html
	 * @var string
	 */
	const ACTIVITY_SCHEMA = 'http://activitystrea.ms/schema/1.0/';
	/**
	 * Atom Media Extensions
	 *
	 * @var string
	 */
	const MEDIA           = 'http://purl.org/syndication/atommedia';
	/**
	 * The Salmon Protocol is an open, simple, standards-based solution that lets
	 * aggregators and sources unify the conversations.
	 *
	 * @see http://www.salmon-protocol.org/salmon-protocol-summary
	 * @var string
	 */
	const SALMON_ME       = 'http://salmon-protocol.org/ns/magic-env';
	/**
	 * OStatus is a minimal specification for distributed status updates or microblogging.
	 *
	 * @see https://ostatus.github.io/spec/OStatus%201.0%20Draft%202.html
	 * @var string
	 */
	const OSTATUSSUB      = 'http://ostatus.org/schema/1.0/subscribe';
	/**
	 * GeoRSS was designed as a lightweight, community driven way to extend existing feeds with geographic information.
	 *
	 * @see http://www.georss.org/
	 * @var string
	 */
	const GEORSS          = 'http://www.georss.org/georss';
	/**
	 * The Portable Contacts specification is designed to make it easier for developers
	 * to give their users a secure way to access the address books and friends lists
	 * they have built up all over the web.
	 *
	 * @see http://portablecontacts.net/draft-spec/
	 * @var string
	 */
	const POCO            = 'http://portablecontacts.net/spec/1.0';
	/**
	 * @var string
	 */
	const FEED            = 'http://schemas.google.com/g/2010#updates-from';
	/**
	 * OStatus is a minimal specification for distributed status updates or microblogging.
	 *
	 * @see https://ostatus.github.io/spec/OStatus%201.0%20Draft%202.html
	 * @var string
	 */
	const OSTATUS         = 'http://ostatus.org/schema/1.0';
	/**
	 * @var string
	 */
	const STATUSNET       = 'http://status.net/schema/api/1/';
	/**
	 * This namespace describes the Atom Activity Streams in RDF Vocabulary (AAIR),
	 * defined as a dictionary of named properties and classes using W3C's RDF technology,
	 * and specifically a mapping of the Atom Activity Streams work to RDF.
	 *
	 * @see http://xmlns.notu.be/aair/#RFC4287
	 * @var string
	 */
	const ATOM1           = 'http://www.w3.org/2005/Atom';

	/**
	 * @var string
	 */
	const MASTODON        = 'http://mastodon.social/schema/1.0';

	/**
	 * @var string
	 */
	const LITEPUB         = 'http://litepub.social';

	/**
	 * @var string
	 */
	const PEERTUBE        = 'https://joinpeertube.org';
}
